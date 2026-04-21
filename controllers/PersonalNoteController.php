<?php
class PersonalNoteController
{
    private PersonalNote $model;

    public function __construct()
    {
        $this->model = new PersonalNote();
    }

    public function index(): void
    {
        Auth::requireAuth();

        $filters = [
            'status' => $_GET['status'] ?? 'all',
            'search' => $_GET['search'] ?? '',
        ];

        $page = Utils::currentPage();
        $data = $this->model->getByUser(Auth::id(), $filters, $page);
        $notes = $data['rows'];
        $pagination = $data['pagination'];

        require VIEWS_PATH . '/personal-notes/list.php';
    }

    public function create(): void
    {
        Auth::requireAuth();

        $csrfToken = Auth::generateCSRFToken();
        $errors = $_SESSION['_form_errors'] ?? [];
        $fd = $_SESSION['_form_data'] ?? [];
        unset($_SESSION['_form_errors'], $_SESSION['_form_data']);

        require VIEWS_PATH . '/personal-notes/create.php';
    }

    public function store(): void
    {
        Auth::requireAuth();
        Auth::checkCSRF();

        $errors = $this->validate($_POST);
        if ($errors) {
            $_SESSION['_form_errors'] = $errors;
            $_SESSION['_form_data'] = $_POST;
            Utils::redirect('/personal-notes/create');
        }

        $id = $this->model->create([
            'title' => Utils::sanitize($_POST['title'] ?? ''),
            'description' => Utils::sanitize($_POST['description'] ?? ''),
            'created_by' => Auth::id(),
        ]);

        Logger::log('created', 'personal_note', $id);
        Utils::flashSuccess('Note created successfully.');
        Utils::redirect('/personal-notes');
    }

    public function show(int $id): void
    {
        Auth::requireAuth();
        $note = $this->model->findById($id);
        if (!$note || $note['created_by'] !== Auth::id()) {
            $this->notFound();
        }

        require VIEWS_PATH . '/personal-notes/view.php';
    }

    public function edit(int $id): void
    {
        Auth::requireAuth();
        $note = $this->model->findById($id);
        if (!$note || $note['created_by'] !== Auth::id()) {
            $this->notFound();
        }

        $csrfToken = Auth::generateCSRFToken();
        $errors = $_SESSION['_form_errors'] ?? [];
        $fd = $_SESSION['_form_data'] ?? $note;
        unset($_SESSION['_form_errors'], $_SESSION['_form_data']);

        require VIEWS_PATH . '/personal-notes/edit.php';
    }

    public function update(int $id): void
    {
        Auth::requireAuth();
        Auth::checkCSRF();

        $note = $this->model->findById($id);
        if (!$note || $note['created_by'] !== Auth::id()) {
            $this->notFound();
        }

        $errors = $this->validate($_POST);
        if ($errors) {
            $_SESSION['_form_errors'] = $errors;
            $_SESSION['_form_data'] = $_POST;
            Utils::redirect('/personal-notes/' . $id . '/edit');
        }

        $this->model->update($id, [
            'title' => Utils::sanitize($_POST['title'] ?? ''),
            'description' => Utils::sanitize($_POST['description'] ?? ''),
        ]);

        Logger::log('updated', 'personal_note', $id);
        Utils::flashSuccess('Note updated successfully.');
        Utils::redirect('/personal-notes/' . $id);
    }

    public function toggle(int $id): void
    {
        Auth::requireAuth();
        Auth::checkCSRF();

        $note = $this->model->findById($id);
        if (!$note || $note['created_by'] !== Auth::id()) {
            $this->notFound();
        }

        $this->model->toggleCompletion($id);
        Logger::log('toggled', 'personal_note', $id);
        Utils::flashSuccess($note['is_completed'] ? 'Marked as pending.' : 'Marked as completed.');
        Utils::redirect('/personal-notes');
    }

    public function destroy(int $id): void
    {
        Auth::requireAuth();
        Auth::checkCSRF();

        $note = $this->model->findById($id);
        if (!$note || $note['created_by'] !== Auth::id()) {
            $this->notFound();
        }

        $this->model->delete($id);
        Logger::log('deleted', 'personal_note', $id);
        Utils::flashSuccess('Note deleted.');
        Utils::redirect('/personal-notes');
    }

    private function validate(array $data): array
    {
        $errors = [];
        if (empty(trim($data['title'] ?? ''))) {
            $errors['title'] = 'Title is required.';
        }
        if (empty(trim($data['description'] ?? ''))) {
            $errors['description'] = 'Description is required.';
        }
        return $errors;
    }

    private function notFound(): never
    {
        http_response_code(404);
        require VIEWS_PATH . '/errors/404.php';
        exit;
    }
}

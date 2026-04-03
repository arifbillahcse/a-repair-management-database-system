<?php
Auth::requireRole('admin');
$preType = in_array($_GET['type'] ?? '', ['customers','repairs','invoices','staff']) ? $_GET['type'] : 'customers';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Import — <?= Utils::e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
    <style>
        .import-container { max-width: 700px; margin: 0 auto; }
        .import-type-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
        .import-type-card {
            padding: 1.25rem;
            border: 2px solid var(--border);
            border-radius: var(--radius-lg);
            cursor: pointer;
            transition: all var(--transition);
            text-align: center;
        }
        .import-type-card:hover { border-color: var(--accent); background: var(--bg-tertiary); }
        .import-type-card input[type="radio"] { display: none; }
        .import-type-card input:checked + label { color: var(--accent); font-weight: 700; }
        .import-type-card input:checked ~ .import-type-icon { color: var(--accent); }
        .import-type-icon { font-size: 2rem; margin-bottom: .5rem; }
        .import-type-label { font-size: .9rem; font-weight: 600; color: var(--text-primary); margin: 0; }
        .import-type-desc { font-size: .78rem; color: var(--text-secondary); margin-top: .25rem; }
        .upload-area {
            border: 2px dashed var(--border);
            border-radius: var(--radius-lg);
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all var(--transition);
            background: var(--bg-tertiary);
        }
        .upload-area:hover { border-color: var(--accent); background: rgba(16,185,129,.05); }
        .upload-area.drag-over { border-color: var(--accent); background: rgba(16,185,129,.1); }
        .upload-area svg { width: 48px; height: 48px; margin-bottom: 1rem; color: var(--accent); }
        .upload-area p { margin: 0; color: var(--text-secondary); font-size: .9rem; }
        .template-links {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: .75rem;
            margin-top: 1rem;
        }
        .template-link {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .5rem .75rem;
            background: var(--bg-tertiary);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            font-size: .82rem;
            color: var(--accent);
            text-decoration: none;
            transition: all var(--transition);
        }
        .template-link:hover { background: var(--bg-secondary); border-color: var(--accent); }
    </style>
</head>
<body>
<?php require VIEWS_PATH . '/layouts/header.php'; ?>
<?php require VIEWS_PATH . '/layouts/sidebar.php'; ?>

<div class="layout-wrapper">
    <main class="main-content">
        <div class="page-header">
            <div>
                <h1 class="page-title">Import Data</h1>
                <p class="page-subtitle">Bulk import customers, repairs, invoices, or staff</p>
            </div>
        </div>

        <div class="import-container">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Step 1: Select Data Type</h2>
                </div>
                <div class="card-body">
                    <div class="import-type-grid">
                        <div class="import-type-card">
                            <input type="radio" id="type-customers" name="import_type" value="customers" <?= $preType==='customers'?'checked':'' ?>>
                            <label for="type-customers"></label>
                            <div class="import-type-icon">👥</div>
                            <p class="import-type-label">Customers</p>
                            <p class="import-type-desc">Import customer data</p>
                        </div>

                        <div class="import-type-card">
                            <input type="radio" id="type-repairs" name="import_type" value="repairs" <?= $preType==='repairs'?'checked':'' ?>>
                            <label for="type-repairs"></label>
                            <div class="import-type-icon">🔧</div>
                            <p class="import-type-label">Repairs</p>
                            <p class="import-type-desc">Import repair jobs</p>
                        </div>

                        <div class="import-type-card">
                            <input type="radio" id="type-invoices" name="import_type" value="invoices">
                            <label for="type-invoices"></label>
                            <div class="import-type-icon">📄</div>
                            <p class="import-type-label">Invoices</p>
                            <p class="import-type-desc">Import invoice records</p>
                        </div>

                        <div class="import-type-card">
                            <input type="radio" id="type-staff" name="import_type" value="staff">
                            <label for="type-staff"></label>
                            <div class="import-type-icon">👔</div>
                            <p class="import-type-label">Staff</p>
                            <p class="import-type-desc">Import staff members</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Step 2: Download Template</h2>
                </div>
                <div class="card-body">
                    <p style="color: var(--text-secondary); margin-bottom: 1rem; font-size: .9rem;">
                        Download the CSV template matching your data type, fill in your data, and upload it back.
                    </p>
                    <div class="template-links" id="templateLinks">
                        <a href="<?= BASE_URL ?>/import/template/customers" class="template-link" data-type="customers">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                            Customers Template
                        </a>
                        <a href="<?= BASE_URL ?>/import/template/repairs" class="template-link" data-type="repairs" style="display: none;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                            Repairs Template
                        </a>
                        <a href="<?= BASE_URL ?>/import/template/invoices" class="template-link" data-type="invoices" style="display: none;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                            Invoices Template
                        </a>
                        <a href="<?= BASE_URL ?>/import/template/staff" class="template-link" data-type="staff" style="display: none;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                            Staff Template
                        </a>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Step 3: Upload CSV File</h2>
                </div>
                <div class="card-body">
                    <form id="importForm" method="POST" action="<?= BASE_URL ?>/import/upload" enctype="multipart/form-data" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= Utils::e($csrfToken) ?>">

                        <label class="form-label">CSV File</label>
                        <div class="upload-area" id="uploadArea">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                <polyline points="17 8 12 3 7 8"/>
                                <line x1="12" y1="3" x2="12" y2="15"/>
                            </svg>
                            <p><strong>Click to upload</strong> or drag and drop</p>
                            <p style="margin-top: .3rem;">CSV file up to 50 MB</p>
                            <input type="file" id="csvFile" name="csv_file" accept=".csv,.txt" style="display: none;" required>
                        </div>
                        <div id="fileName" style="margin-top: .5rem; font-size: .85rem; color: var(--text-secondary);"></div>

                        <div class="form-actions" style="justify-content: space-between;">
                            <a href="<?= BASE_URL ?>/" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                                Upload & Import
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const uploadArea = document.getElementById('uploadArea');
    const csvFile = document.getElementById('csvFile');
    const fileName = document.getElementById('fileName');
    const submitBtn = document.getElementById('submitBtn');
    const importForm = document.getElementById('importForm');
    const typeRadios = document.querySelectorAll('input[name="import_type"]');
    const templateLinks = document.querySelectorAll('.template-link');

    // Type selector
    typeRadios.forEach(radio => {
        radio.addEventListener('change', function () {
            templateLinks.forEach(link => link.style.display = 'none');
            document.querySelector('.template-link[data-type="' + this.value + '"]').style.display = 'inline-flex';

            // Set form input
            const hidden = importForm.querySelector('input[name="import_type"]');
            if (!hidden) {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'import_type';
                importForm.appendChild(hiddenInput);
            }
            importForm.querySelector('input[name="import_type"]').value = this.value;
        });
    });

    // File upload
    uploadArea.addEventListener('click', () => csvFile.click());
    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.classList.add('drag-over');
    });
    uploadArea.addEventListener('dragleave', () => uploadArea.classList.remove('drag-over'));
    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('drag-over');
        csvFile.files = e.dataTransfer.files;
        handleFileSelect();
    });

    csvFile.addEventListener('change', handleFileSelect);

    function handleFileSelect() {
        if (csvFile.files.length > 0) {
            const file = csvFile.files[0];
            fileName.textContent = '✓ Selected: ' + file.name + ' (' + (file.size / 1024 / 1024).toFixed(2) + ' MB)';
            submitBtn.disabled = false;
        } else {
            fileName.textContent = '';
            submitBtn.disabled = true;
        }
    }

    // Show correct template link on load
    templateLinks.forEach(link => link.style.display = 'none');
    const initial = document.querySelector('input[name="import_type"]:checked');
    if (initial) document.querySelector('.template-link[data-type="' + initial.value + '"]').style.display = 'inline-flex';

    // Set initial import type in form
    const initialType = document.querySelector('input[name="import_type"]:checked').value;
    const hidden = document.createElement('input');
    hidden.type = 'hidden';
    hidden.name = 'import_type';
    hidden.value = initialType;
    importForm.appendChild(hidden);
});
</script>
</body>
</html>

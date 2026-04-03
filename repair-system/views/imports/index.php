<?php
$pageTitle = 'Import Data';
$preType   = in_array($_GET['type'] ?? '', ['customers','repairs','invoices','staff'])
             ? $_GET['type'] : 'customers';
require VIEWS_PATH . '/layouts/header.php';
?>
<style>
.import-container { max-width: 700px; }
.import-type-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 0; }
.import-type-card {
    padding: 1.25rem;
    border: 2px solid var(--border);
    border-radius: var(--radius-lg);
    cursor: pointer;
    transition: all var(--transition);
    text-align: center;
}
.import-type-card:hover { border-color: var(--accent); background: var(--bg-tertiary); }
.import-type-card.selected { border-color: var(--accent); background: var(--accent-dim); }
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
.upload-area:hover, .upload-area.drag-over { border-color: var(--accent); background: rgba(16,185,129,.06); }
.upload-area svg { width: 48px; height: 48px; margin-bottom: 1rem; stroke: var(--accent); }
.upload-area p { margin: 0; color: var(--text-secondary); font-size: .9rem; }
.template-links { display: flex; gap: .75rem; flex-wrap: wrap; margin-top: .5rem; }
.template-link {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .45rem .85rem;
    background: var(--bg-tertiary);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    font-size: .82rem;
    color: var(--accent);
    text-decoration: none;
    transition: all var(--transition);
}
.template-link:hover { background: var(--bg-secondary); border-color: var(--accent); }
.template-link svg { width: 14px; height: 14px; }
</style>

<!-- Page header -->
<div class="page-header">
    <div>
        <h1 class="page-title">Import Data</h1>
        <p class="page-subtitle">Bulk import customers, repairs, invoices, or staff via CSV</p>
    </div>
</div>

<div class="import-container">

    <!-- Step 1: Data type -->
    <div class="card">
        <div class="card-header"><h2 class="card-title">Step 1 — Select Data Type</h2></div>
        <div class="card-body">
            <div class="import-type-grid" id="typeGrid">
                <div class="import-type-card <?= $preType==='customers'?'selected':'' ?>" data-type="customers">
                    <div class="import-type-icon">👥</div>
                    <p class="import-type-label">Customers</p>
                    <p class="import-type-desc">Name, email, phone, address</p>
                </div>
                <div class="import-type-card <?= $preType==='repairs'?'selected':'' ?>" data-type="repairs">
                    <div class="import-type-icon">🔧</div>
                    <p class="import-type-label">Repairs</p>
                    <p class="import-type-desc">Device, issue, status, amount</p>
                </div>
                <div class="import-type-card <?= $preType==='invoices'?'selected':'' ?>" data-type="invoices">
                    <div class="import-type-icon">📄</div>
                    <p class="import-type-label">Invoices</p>
                    <p class="import-type-desc">Amount, status, due date</p>
                </div>
                <div class="import-type-card <?= $preType==='staff'?'selected':'' ?>" data-type="staff">
                    <div class="import-type-icon">👔</div>
                    <p class="import-type-label">Staff</p>
                    <p class="import-type-desc">Name, role, contact info</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 2: Download template -->
    <div class="card">
        <div class="card-header"><h2 class="card-title">Step 2 — Download Template</h2></div>
        <div class="card-body">
            <p style="color:var(--text-secondary);margin-bottom:1rem;font-size:.88rem">
                Download the template for your selected type, fill in your data in Excel or Google Sheets, save as CSV, then upload below.
            </p>
            <div class="template-links">
                <?php foreach (['customers','repairs','invoices','staff'] as $t): ?>
                <a href="<?= BASE_URL ?>/import/template/<?= $t ?>"
                   class="template-link" data-tmpl="<?= $t ?>"
                   style="<?= $preType !== $t ? 'display:none' : '' ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="7 10 12 15 17 10"/>
                        <line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                    Download <?= ucfirst($t) ?> Template
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Step 3: Upload -->
    <div class="card">
        <div class="card-header"><h2 class="card-title">Step 3 — Upload CSV File</h2></div>
        <div class="card-body">
            <form id="importForm" method="POST" action="<?= BASE_URL ?>/import/upload" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= Utils::e($csrfToken) ?>">
                <input type="hidden" name="import_type" id="hiddenType" value="<?= Utils::e($preType) ?>">

                <div class="upload-area" id="uploadArea">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="17 8 12 3 7 8"/>
                        <line x1="12" y1="3" x2="12" y2="15"/>
                    </svg>
                    <p><strong>Click to browse</strong> or drag &amp; drop your CSV file here</p>
                    <p style="margin-top:.3rem;font-size:.78rem">Supports .csv files up to 50 MB</p>
                    <input type="file" id="csvFile" name="csv_file" accept=".csv,.txt" style="display:none" required>
                </div>
                <div id="fileName" style="margin-top:.6rem;font-size:.84rem;color:var(--text-secondary)"></div>

                <div class="form-actions" style="justify-content:space-between">
                    <a href="javascript:history.back()" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="17 8 12 3 7 8"/>
                            <line x1="12" y1="3" x2="12" y2="15"/>
                        </svg>
                        Upload &amp; Import
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<?php $inlineJs = <<<'JS'
(function () {
    var selectedType = document.getElementById('hiddenType').value;

    // Type cards
    document.getElementById('typeGrid').addEventListener('click', function (e) {
        var card = e.target.closest('[data-type]');
        if (!card) return;
        document.querySelectorAll('.import-type-card').forEach(c => c.classList.remove('selected'));
        card.classList.add('selected');
        selectedType = card.dataset.type;
        document.getElementById('hiddenType').value = selectedType;
        // Update template links
        document.querySelectorAll('.template-link').forEach(l => l.style.display = 'none');
        var tmpl = document.querySelector('.template-link[data-tmpl="' + selectedType + '"]');
        if (tmpl) tmpl.style.display = 'inline-flex';
    });

    // File upload area
    var uploadArea = document.getElementById('uploadArea');
    var csvFile    = document.getElementById('csvFile');
    var fileName   = document.getElementById('fileName');
    var submitBtn  = document.getElementById('submitBtn');

    uploadArea.addEventListener('click', function (e) {
        if (e.target !== csvFile) csvFile.click();
    });
    uploadArea.addEventListener('dragover', function (e) {
        e.preventDefault(); uploadArea.classList.add('drag-over');
    });
    uploadArea.addEventListener('dragleave', function () {
        uploadArea.classList.remove('drag-over');
    });
    uploadArea.addEventListener('drop', function (e) {
        e.preventDefault(); uploadArea.classList.remove('drag-over');
        csvFile.files = e.dataTransfer.files;
        handleFile();
    });
    csvFile.addEventListener('change', handleFile);

    function handleFile() {
        if (csvFile.files.length > 0) {
            var f = csvFile.files[0];
            fileName.textContent = '✓ ' + f.name + ' (' + (f.size / 1024 / 1024).toFixed(2) + ' MB)';
            submitBtn.disabled = false;
        } else {
            fileName.textContent = '';
            submitBtn.disabled = true;
        }
    }
})();
JS;
require VIEWS_PATH . '/layouts/footer.php';
?>

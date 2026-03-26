    </main><!-- /.main-content -->
</div><!-- /.layout-wrapper -->

<!-- ══════════════════════════════════════════════════════════════
     FOOTER BAR
══════════════════════════════════════════════════════════════ -->
<footer class="app-footer" role="contentinfo">
    <span>&copy; <?= date('Y') ?> <?= Utils::e(APP_NAME) ?> &mdash; v<?= APP_VERSION ?></span>
    <?php if (APP_DEBUG && isset($db)): ?>
    <span class="footer-debug">
        <?= count(Database::getInstance()->getQueryLog()) ?> queries
    </span>
    <?php endif; ?>
</footer>

<!-- App scripts -->
<script src="<?= BASE_URL ?>/js/main.js" defer></script>
<script src="<?= BASE_URL ?>/js/form-validation.js" defer></script>

<!-- Inline page-specific scripts (optional, set $inlineJs in view) -->
<?php if (!empty($inlineJs)): ?>
<script><?= $inlineJs ?></script>
<?php endif; ?>

</body>
</html>

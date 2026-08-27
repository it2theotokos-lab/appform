<footer class="mt-auto py-3 px-4 border-top" style="border-color:var(--color-border)!important;" role="contentinfo">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <p class="mb-0" style="font-size:0.75rem;color:var(--color-text-muted);">
            <?php $ver = \App\Services\VersionService::getVersionData(); ?>
            &copy; <?= date('Y') ?> AppForm v<?= htmlspecialchars($ver['version']) ?> (Build <?= htmlspecialchars((string)$ver['build']) ?>) &mdash; R&D by DVlachonatsios for Theotokos I.T. Department
        </p>
        <p class="mb-0" style="font-size:0.75rem;color:var(--color-text-muted);">
            <span id="footer-env" style="display:none;" class="badge-status-draft badge" title="<?= __('System') ?>">dev</span>
        </p>
    </div>
</footer>


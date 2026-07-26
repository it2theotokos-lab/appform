<footer class="mt-auto py-3 px-4 border-top" style="border-color:var(--color-border)!important;" role="contentinfo">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <p class="mb-0" style="font-size:0.75rem;color:var(--color-text-muted);">
            &copy; <?= date('Y') ?> AppForm v<?= \App\Services\VersionService::getVersionString() ?> &mdash; R&D by DVlachonatsios for Theotokos I.T. Department
        </p>
        <p class="mb-0" style="font-size:0.75rem;color:var(--color-text-muted);">
            <span id="footer-env" style="display:none;" class="badge-status-draft badge" title="Περιβάλλον">dev</span>
        </p>
    </div>
</footer>

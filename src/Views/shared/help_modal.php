<!-- Global Help Documentation Modal component -->
<div id="global-help-modal" class="app-modal" role="dialog" aria-modal="true" aria-labelledby="global-help-title" style="display:none;">
    <div class="app-modal-backdrop" onclick="closeGlobalHelp()"></div>
    <div class="app-modal-dialog bg-dark border border-secondary rounded p-4" style="max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto; position: relative; z-index: 2;">
        
        <div class="app-modal-header d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom border-secondary">
            <h4 id="global-help-title" class="text-white class-heading m-0"><i class="fa-solid fa-circle-info text-primary me-2"></i> <?= __('Usage Guide') ?></h4>
            <button type="button" class="btn-close btn-close-white" id="btn-close-global-help" aria-label="<?= __('Close') ?>"></button>
        </div>

        <div class="mb-3">
            <input type="text" id="global-help-search" class="form-control form-control-sm" placeholder="<?= __('Search help instructions…') ?>">
        </div>

        <div id="global-help-content" class="text-white-50">
            <!-- Dynamic help HTML template will be loaded here via Ajax -->
        </div>

        <div class="d-flex justify-content-end gap-2 mt-4 pt-2 border-top border-secondary">
            <button type="button" class="btn btn-secondary btn-sm" id="btn-close-global-help-footer"><?= __('Close') ?></button>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const helpModal = document.getElementById('global-help-modal');
    const helpContent = document.getElementById('global-help-content');
    const searchInput = document.getElementById('global-help-search');

    window.openGlobalHelp = function(context) {
        if (!helpModal || !helpContent) return;

        // Fetch context documentation template using Ajax
        fetch(`/help/context/${context}`)
            .then(r => r.json())
            .then(res => {
                if (res.success && res.html) {
                    helpContent.innerHTML = res.html;
                } else {
                    helpContent.innerHTML = '<p class="text-muted">Δεν υπάρχει διαθέσιμο περιεχόμενο βοήθειας για αυτή τη σελίδα.</p>';
                }
                helpModal.style.display = 'flex';
                helpModal.classList.add('is-visible');
            })
            .catch(e => {
                helpContent.innerHTML = '<p class="text-muted">Δεν υπάρχει διαθέσιμο περιεχόμενο βοήθειας για αυτή τη σελίδα.</p>';
                helpModal.style.display = 'flex';
                helpModal.classList.add('is-visible');
            });
    };

    window.closeGlobalHelp = function() {
        if (helpModal) {
            helpModal.style.display = 'none';
            helpModal.classList.remove('is-visible');
        }
    }

    if (document.getElementById('btn-close-global-help')) {
        document.getElementById('btn-close-global-help').addEventListener('click', window.closeGlobalHelp);
    }
    if (document.getElementById('btn-close-global-help-footer')) {
        document.getElementById('btn-close-global-help-footer').addEventListener('click', window.closeGlobalHelp);
    }

    // Close modal on Escape key press
    window.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            window.closeGlobalHelp();
        }
    });

    // Basic help text filter search
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = searchInput.value.toLowerCase();
            const paragraphs = helpContent.querySelectorAll('p, li, h5');
            paragraphs.forEach(p => {
                if (p.innerText.toLowerCase().includes(query)) {
                    p.style.display = '';
                } else {
                    p.style.display = 'none';
                }
            });
        });
    }
});
</script>

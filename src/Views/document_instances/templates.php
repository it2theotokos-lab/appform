<div class="row mb-4 align-items-center">
    <div class="col-md-8">
        <h1 class="h3 m-0 text-white">
            <i class="fa-solid fa-file-signature me-2 text-primary" aria-hidden="true"></i> 
            Νέο Έγγραφο
        </h1>
        <p class="text-muted small mb-0">Επιλέξτε ένα από τα διαθέσιμα πρότυπα εγγράφων για να ξεκινήσετε τη συμπλήρωση.</p>
    </div>
</div>

<div class="row">
    <?php if (empty($templates)): ?>
        <div class="col-12 text-center py-5">
            <div class="card p-5 max-width-md mx-auto bg-dark">
                <i class="fa-solid fa-file-invoice-dollar fa-3x text-muted mb-3" aria-hidden="true"></i>
                <h5 class="text-white mb-2">Δεν υπάρχουν διαθέσιμα πρότυπα</h5>
                <p class="text-muted small">Αυτή τη στιγμή δεν υπάρχει κανένα δημοσιευμένο πρότυπο εγγράφου έτοιμο προς συμπλήρωση.</p>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($templates as $tmpl): ?>
            <div class="col-md-4 mb-4">
                <div class="card p-3 h-100 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="badge bg-success small">Έκδοση <?= (int)$tmpl['version_number'] ?></span>
                            <span class="text-muted small"><i class="fa-solid fa-file-lines me-1"></i> Σελίδες: <?= (int)$tmpl['page_count'] ?></span>
                        </div>
                        <h5 class="text-white mb-2"><?= \App\Core\View::escape($tmpl['title']) ?></h5>
                        <?php if (!empty($tmpl['description'])): ?>
                            <p class="text-muted small"><?= \App\Core\View::escape($tmpl['description']) ?></p>
                        <?php else: ?>
                            <p class="text-muted small font-italic">Δεν υπάρχει περιγραφή.</p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mt-3">
                        <form action="/documents/templates/<?= (int)$tmpl['id'] ?>/start" method="POST" class="js-confirm-action" data-confirm-title="Έναρξη Εγγράφου" data-confirm-text="Θέλετε να ξεκινήσετε τη συμπλήρωση νέου εγγράφου για αυτό το πρότυπο;">
                            <?= \App\Core\Csrf::field() ?>
                            <button type="submit" class="btn btn-premium w-100">
                                <i class="fa-solid fa-play me-1" aria-hidden="true"></i> Συμπλήρωση Εγγράφου
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h1 class="h3 m-0 text-soft"><i class="fa-solid fa-sitemap me-2 text-primary"></i> Οργανωτική Δομή</h1>
        <p class="text-muted m-0">Προβολή της ιεραρχίας και των σχέσεων αναφοράς των χρηστών.</p>
    </div>
    <div class="col-md-6 text-end">
        <a href="/admin/users" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i> Επιστροφή στους Χρήστες
        </a>
    </div>
</div>

<div class="card p-4">
    <div class="mb-3 d-flex gap-2">
        <button class="btn btn-outline-primary btn-sm" onclick="expandAll()">Ανάπτυξη Όλων</button>
        <button class="btn btn-outline-secondary btn-sm" onclick="collapseAll()">Σύμπτυξη Όλων</button>
    </div>

    <div class="tree-container text-soft">
        <?php
        // Simple recursive tree renderer function
        function renderTreeBranch($parentId, $users) {
            echo '<ul class="list-group list-group-flush ms-3 border-start ps-2" style="border-color: var(--color-border) !important;">';
            foreach ($users as $u) {
                if ((int)$u['manager_id'] === $parentId) {
                    echo '<li class="list-group-item bg-transparent text-soft border-0 py-1">';
                    echo '<i class="fa-solid fa-user text-primary me-2"></i>';
                    echo '<strong>' . htmlspecialchars($u['full_name']) . '</strong> ';
                    echo '<span class="text-muted">(@' . htmlspecialchars($u['username']) . ' &mdash; ' . htmlspecialchars($u['role_name']) . ')</span>';
                    renderTreeBranch((int)$u['id'], $users);
                    echo '</li>';
                }
            }
            echo '</ul>';
        }

        // Render root managers (users without manager_id set)
        echo '<ul class="list-group list-group-flush">';
        foreach ($users as $u) {
            if ($u['manager_id'] === null) {
                echo '<li class="list-group-item bg-transparent text-soft border-0 py-2">';
                echo '<i class="fa-solid fa-user-tie text-success me-2"></i>';
                echo '<strong>' . htmlspecialchars($u['full_name']) . '</strong> ';
                echo '<span class="text-muted">(@' . htmlspecialchars($u['username']) . ' &mdash; ' . htmlspecialchars($u['role_name']) . ')</span>';
                renderTreeBranch((int)$u['id'], $users);
                echo '</li>';
            }
        }
        echo '</ul>';
        ?>
    </div>
</div>

<script>
function expandAll() {
    // Basic expand functionality placeholder
}
function collapseAll() {
    // Basic collapse functionality placeholder
}
</script>

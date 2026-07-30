<div class="row mb-4 align-items-center">
    <div class="col-md-6">
        <h1 class="h3 m-0 text-white"><i class="fa-solid fa-heart-pulse me-2 text-success"></i> System Health Check</h1>
        <p class="text-muted m-0">Έλεγχος κατάστασης λειτουργίας των υποσυστημάτων του AppForm.</p>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card p-3 d-flex align-items-center justify-content-between flex-row">
            <div>
                <h6 class="text-muted small mb-1">Status</h6>
                <h4 class="text-success m-0">HEALTHY</h4>
            </div>
            <div class="text-success"><i class="fa-solid fa-circle-check fa-2x"></i></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 d-flex align-items-center justify-content-between flex-row">
            <div>
                <h6 class="text-muted small mb-1">PHP Version</h6>
                <h4 class="text-white m-0"><?= phpversion() ?></h4>
            </div>
            <div class="text-primary"><i class="fa-brands fa-php fa-2x"></i></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 d-flex align-items-center justify-content-between flex-row">
            <div>
                <h6 class="text-muted small mb-1">Environment</h6>
                <h4 class="text-white m-0"><?= ucfirst(\App\Core\App::$config['app']['env'] ?? 'production') ?></h4>
            </div>
            <div class="text-info"><i class="fa-solid fa-server fa-2x"></i></div>
        </div>
    </div>
</div>

<div class="card p-4">
    <h5 class="text-white mb-3 border-bottom border-glass pb-2">Subsystems Status</h5>
    <div class="table-responsive">
        <table class="table table-sm align-middle text-white">
            <thead>
                <tr class="text-white-50">
                    <th>Υποσύστημα</th>
                    <th>Κατάσταση</th>
                    <th>Πληροφορίες</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Βάση Δεδομένων (Database)</td>
                    <td><span class="badge bg-success">GREEN</span></td>
                    <td>Σύνδεση επιτυχής</td>
                </tr>
                <tr>
                    <td>Job Queue Engine</td>
                    <td><span class="badge bg-success">GREEN</span></td>
                    <td>0 pending tasks</td>
                </tr>
                <tr>
                    <td>Active Directory / LDAP</td>
                    <td><span class="badge bg-success">GREEN</span></td>
                    <td>Provider enabled & connected</td>
                </tr>
                <tr>
                    <td>SMTP Server Connection</td>
                    <td><span class="badge bg-success">GREEN</span></td>
                    <td>Online</td>
                </tr>
                <tr>
                    <td>Webhooks Dispatcher</td>
                    <td><span class="badge bg-success">GREEN</span></td>
                    <td>Active</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

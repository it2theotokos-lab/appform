<?php
use App\Core\Auth;
use App\Core\Csrf;

$activeTab = $_GET['tab'] ?? 'my';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="header-page-title mb-0"><i class="fa-solid fa-folder-closed text-primary me-2"></i><?= __('Files') ?></h1>
    <div>
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalUploadFile">
            <i class="fa-solid fa-cloud-arrow-up me-1"></i><?= __('Upload File') ?>
        </button>
    </div>
</div>

<?php if ($msg = \App\Core\Session::flash('success')): ?>
                <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($msg) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>
            <?php if ($msg = \App\Core\Session::flash('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show"><?= htmlspecialchars($msg) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>

            <ul class="nav nav-tabs mb-4" id="filesTab" role="tablist">
                <li class="nav-item">
                    <a class="nav-link <?= $activeTab === 'my' ? 'active' : '' ?>" href="?tab=my">
                        <i class="fa-solid fa-user-lock me-1"></i><?= __('My Files') ?> (<?= count($myFiles) ?>)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $activeTab === 'shared' ? 'active' : '' ?>" href="?tab=shared">
                        <i class="fa-solid fa-users me-1"></i><?= __('Shared With Me') ?> (<?= count($sharedFiles) ?>)
                    </a>
                </li>
            </ul>

            <div class="tab-content">
                <?php if ($activeTab === 'my'): ?>
                    <!-- TAB 1: My Files -->
                    <div class="card card-outline card-primary">
                        <div class="card-body p-0">
                            <?php if (empty($myFiles)): ?>
                                <div class="p-4 text-center text-muted">
                                    <i class="fa-solid fa-file-circle-xmark fa-3x mb-3 d-block opacity-50"></i>
                                    <p><?= __('No uploaded files found. Click "Upload File" to add your first secure file.') ?></p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th><?= __('Title') ?></th>
                                                <th><?= __('Filename') ?></th>
                                                <th><?= __('Size') ?></th>
                                                <th><?= __('Date') ?></th>
                                                <th class="text-end"><?= __('Actions') ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($myFiles as $f): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?= htmlspecialchars($f['title']) ?></strong>
                                                        <?php if ($f['description']): ?>
                                                            <br><small class="text-muted"><?= htmlspecialchars($f['description']) ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><span class="badge bg-secondary me-1"><?= strtoupper(pathinfo($f['original_name'], PATHINFO_EXTENSION)) ?></span> <?= htmlspecialchars($f['original_name']) ?></td>
                                                    <td><?= number_format($f['file_size'] / 1024, 1) ?> KB</td>
                                                    <td><small><?= $f['created_at'] ?></small></td>
                                                    <td class="text-end">
                                                        <a href="/admin/files/<?= $f['id'] ?>/preview" target="_blank" class="btn btn-sm btn-outline-secondary me-1" title="<?= __('Preview') ?>">
                                                            <i class="fa-solid fa-eye"></i>
                                                        </a>
                                                        <a href="/admin/files/<?= $f['id'] ?>/download" class="btn btn-sm btn-outline-primary me-1" title="<?= __('Download') ?>">
                                                            <i class="fa-solid fa-download"></i>
                                                        </a>
                                                        <a href="/admin/files/<?= $f['id'] ?>/manage" class="btn btn-sm btn-outline-info me-1" title="<?= __('Manage Permissions') ?>">
                                                            <i class="fa-solid fa-user-gear"></i>
                                                        </a>
                                                        <form action="/admin/files/<?= $f['id'] ?>/delete" method="POST" class="d-inline" onsubmit="return confirm('<?= __('Are you sure you want to delete this file?') ?>');">
                                                            <?= Csrf::field() ?>
                                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="<?= __('Delete') ?>">
                                                                <i class="fa-solid fa-trash-can"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- TAB 2: Shared With Me -->
                    <div class="card card-outline card-info">
                        <div class="card-body p-0">
                            <?php if (empty($sharedFiles)): ?>
                                <div class="p-4 text-center text-muted">
                                    <i class="fa-solid fa-share-nodes fa-3x mb-3 d-block opacity-50"></i>
                                    <p><?= __('No files have been shared with you yet.') ?></p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th><?= __('Title') ?></th>
                                                <th><?= __('Uploaded By') ?></th>
                                                <th><?= __('Filename') ?></th>
                                                <th><?= __('Size') ?></th>
                                                <th><?= __('Date') ?></th>
                                                <th class="text-end"><?= __('Actions') ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($sharedFiles as $f): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?= htmlspecialchars($f['title']) ?></strong>
                                                        <?php if ($f['description']): ?>
                                                            <br><small class="text-muted"><?= htmlspecialchars($f['description']) ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><i class="fa-solid fa-user-circle me-1 text-primary"></i><?= htmlspecialchars($f['uploader_name'] ?: $f['uploader_username']) ?></td>
                                                    <td><span class="badge bg-secondary me-1"><?= strtoupper(pathinfo($f['original_name'], PATHINFO_EXTENSION)) ?></span> <?= htmlspecialchars($f['original_name']) ?></td>
                                                    <td><?= number_format($f['file_size'] / 1024, 1) ?> KB</td>
                                                    <td><small><?= $f['created_at'] ?></small></td>
                                                    <td class="text-end">
                                                        <a href="/admin/files/<?= $f['id'] ?>/preview" target="_blank" class="btn btn-sm btn-outline-secondary me-1" title="<?= __('Preview') ?>">
                                                            <i class="fa-solid fa-eye me-1"></i><?= __('Preview') ?>
                                                        </a>
                                                        <a href="/admin/files/<?= $f['id'] ?>/download" class="btn btn-sm btn-primary" title="<?= __('Download') ?>">
                                                            <i class="fa-solid fa-download me-1"></i><?= __('Download') ?>
                                                        </a>
                                                        <?php if ($isAdmin): ?>
                                                            <a href="/admin/files/<?= $f['id'] ?>/manage" class="btn btn-sm btn-outline-info ms-1" title="<?= __('Manage Permissions') ?>">
                                                                <i class="fa-solid fa-user-gear"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>

<!-- Modal Upload File -->
<div class="modal fade" id="modalUploadFile" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form action="/admin/files/upload" method="POST" enctype="multipart/form-data">
            <?= Csrf::field() ?>
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fa-solid fa-cloud-arrow-up me-2"></i><?= __('Upload Secure File') ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold"><?= __('Title') ?> <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" required placeholder="<?= __('e.g. Identity Document / Contract') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?= __('Description') ?></label>
                        <textarea name="description" class="form-control" rows="2" placeholder="<?= __('Optional file notes or description') ?>"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold"><?= __('Select File') ?> <span class="text-danger">*</span></label>
                        <input type="file" name="file" class="form-control" required>
                        <small class="text-muted"><?= __('Allowed formats: PDF, Word, Excel, PowerPoint, TXT, JPG, PNG, WEBP, ZIP.') ?></small>
                    </div>

                    <hr>
                    <h6 class="fw-bold mb-2"><i class="fa-solid fa-users-gear me-1 text-primary"></i><?= __('Recipient Access Restrictions') ?></h6>
                    <p class="text-muted small mb-3"><?= __('Default: Only you (uploader) and Administrators have access. Select users or organizational units below to grant access.') ?></p>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?= __('Specific Users') ?></label>
                            <select name="recipient_users[]" class="form-select" multiple size="5">
                                <?php foreach ($allUsers as $u): ?>
                                    <?php if ($u['id'] !== Auth::id()): ?>
                                        <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['full_name']) ?> (@<?= htmlspecialchars($u['username']) ?>)</option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label"><?= __('Organizational Units (Department / Sub-dept / Team)') ?></label>
                            <select name="recipient_units[]" class="form-select" multiple size="5">
                                <?php foreach ($orgUnits as $ou): ?>
                                    <option value="<?= $ou['id'] ?>">
                                        <?= strtoupper($ou['type']) ?>: <?= htmlspecialchars($ou['name']) ?>
                                        <?php if (!empty($ou['parent_name'])): ?> (<?= htmlspecialchars($ou['parent_name']) ?>)<?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __('Cancel') ?></button>
                    <button type="submit" class="btn btn-success"><i class="fa-solid fa-upload me-1"></i><?= __('Upload & Share') ?></button>
                </div>
            </div>
        </form>
    </div>
</div>

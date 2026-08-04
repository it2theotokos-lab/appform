<div class="mb-4">
    <a href="/admin/users" class="btn btn-outline-secondary btn-sm mb-3"><i class="fa-solid fa-arrow-left"></i> <?= __('Back') ?></a>
    <h3 class="font-heading text-white"><?= __('Add New User') ?></h3>
</div>

<div class="glass-panel p-4" style="max-width: 600px;">
    <form action="/admin/users/create" method="POST">
        <?= \App\Core\Csrf::field() ?>

        <div class="mb-3">
            <label for="username" class="form-label"><?= __('Username') ?></label>
            <input type="text" class="form-control" id="username" name="username" required>
        </div>

        <div class="mb-3">
            <label for="email" class="form-label"><?= __('Email') ?></label>
            <input type="email" class="form-control" id="email" name="email" required>
        </div>

        <div class="mb-3">
            <label for="full_name" class="form-label"><?= __('Full Name') ?></label>
            <input type="text" class="form-control" id="full_name" name="full_name" required>
        </div>

        <div class="mb-3">
            <label for="role_id" class="form-label"><?= __('Role') ?></label>
            <select class="form-select" id="role_id" name="role_id" required>
                <?php foreach ($roles as $r): ?>
                    <option value="<?= $r['id'] ?>"><?= \App\Core\View::escape($r['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-4">
            <label for="password" class="form-label"><?= __('Password') ?></label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>

        <button type="submit" class="btn btn-premium w-100"><?= __('Create User') ?> <i class="fa-solid fa-user-plus ms-2"></i></button>
    </form>
</div>

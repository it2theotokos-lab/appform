<div class="auth-card">
    <div class="auth-logo mb-4">
        <i class="fa-solid fa-cubes-stacked"></i>
        <span>App<span>Form</span></span>
    </div>
    
    <h1 class="auth-title"><?= __('Login to the System') ?></h1>
    <p class="auth-subtitle"><?= __('Please enter your credentials to log in.') ?></p>

    <?php if ($error = \App\Core\Session::flash('error')): ?>
        <div class="alert alert-danger" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i>
            <?= \App\Core\View::escape($error) ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['timeout'])): ?>
        <div class="alert alert-warning" role="alert">
            <i class="fa-solid fa-circle-exclamation me-2"></i>
            <?= __('Your session has expired due to inactivity. Please log in again.') ?>
        </div>
    <?php endif; ?>

    <form action="/login" method="POST">
        <?= \App\Core\Csrf::field() ?>
        
        <div class="mb-3">
            <label for="username" class="form-label"><?= __('Username or Email') ?></label>
            <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                <input type="text" class="form-control" id="username" name="username" 
                       value="<?= \App\Core\View::escape(\App\Core\Session::flash('old')['username'] ?? '') ?>" 
                       placeholder="<?= __('Username...') ?>" required autofocus>
            </div>
            <?php if ($err = (\App\Core\Session::flash('errors')['username'] ?? null)): ?>
                <small class="text-danger d-block mt-1"><i class="fa-solid fa-circle-xmark me-1"></i><?= $err[0] ?></small>
            <?php endif; ?>
        </div>
        
        <div class="mb-4">
            <label for="password" class="form-label"><?= __('Password') ?></label>
            <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                <input type="password" class="form-control" id="password" name="password" placeholder="<?= __('Password...') ?>" required>
            </div>
            <?php if ($err = (\App\Core\Session::flash('errors')['password'] ?? null)): ?>
                <small class="text-danger d-block mt-1"><i class="fa-solid fa-circle-xmark me-1"></i><?= $err[0] ?></small>
            <?php endif; ?>
        </div>

        <button type="submit" class="btn btn-premium w-100 mb-4 py-2">
            <?= __('Sign In') ?> <i class="fa-solid fa-arrow-right-to-bracket ms-2"></i>
        </button>

    </form>
</div>

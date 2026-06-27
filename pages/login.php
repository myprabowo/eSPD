<div class="login-container">
    <div class="login-card">
        <div class="login-logo">
            <svg width="56" height="56" viewBox="0 0 56 56" fill="none">
                <rect width="56" height="56" rx="16" fill="url(#lg)"/>
                <path d="M14 18h28M14 28h20M14 38h28" stroke="white" stroke-width="3" stroke-linecap="round"/>
                <defs><linearGradient id="lg" x1="0" y1="0" x2="56" y2="56"><stop stop-color="#6366f1"/><stop offset="1" stop-color="#8b5cf6"/></linearGradient></defs>
            </svg>
            <h1>eSPD</h1>
            <p>Surat Perintah Perjalanan Dinas</p>
        </div>

        <?php if (!empty($loginError)): ?>
        <div class="login-error"><?= h($loginError) ?></div>
        <?php endif; ?>

        <form class="login-form" method="POST" action="index.php">
            <input type="hidden" name="action" value="login">
            <div class="form-group">
                <label class="form-label" for="login-user">Username</label>
                <input class="form-input" type="text" id="login-user" name="username" placeholder="Masukkan username" autocomplete="username" required autofocus>
            </div>
            <div class="form-group">
                <label class="form-label" for="login-pass">Password</label>
                <input class="form-input" type="password" id="login-pass" name="password" placeholder="Masukkan password" autocomplete="current-password" required>
            </div>
            <div class="form-group" style="margin-top:1.25rem;">
                <button type="submit" class="btn btn-primary">Masuk</button>
            </div>
        </form>
    </div>
</div>

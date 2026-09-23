<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= html_escape($title ?? 'Dashboard GM Portal') ?></title>
    <style>
        :root{
            --bg:#eef4fb;
            --surface:#ffffff;
            --surface-soft:#f7fbff;
            --ink:#102033;
            --muted:#5f7287;
            --line:#d7e2ee;
            --brand:#176b87;
            --accent:#7c9a42;
            --shadow:0 18px 44px rgba(16,32,51,.12);
        }
        *{box-sizing:border-box}
        body{
            margin:0;
            min-height:100vh;
            font-family:Segoe UI,Arial,Helvetica,sans-serif;
            color:var(--ink);
            background:
                radial-gradient(circle at top left, rgba(23,107,135,.20), transparent 30%),
                radial-gradient(circle at bottom right, rgba(124,154,66,.16), transparent 26%),
                linear-gradient(180deg,#f8fbff 0,var(--bg) 100%);
        }
        .wrap{
            min-height:100vh;
            display:grid;
            place-items:center;
            padding:24px 16px;
        }
        .card{
            width:min(760px,100%);
            border:1px solid rgba(215,226,238,.95);
            border-radius:18px;
            background:rgba(255,255,255,.9);
            box-shadow:var(--shadow);
            backdrop-filter:blur(8px);
            overflow:hidden;
        }
        .card-head{
            padding:26px 26px 18px;
            border-bottom:1px solid rgba(215,226,238,.75);
            background:linear-gradient(180deg,rgba(255,255,255,.98),rgba(247,251,255,.96));
        }
        .brand{
            display:flex;
            align-items:center;
            gap:14px;
        }
        .brand-logo{
            width:58px;
            height:58px;
            flex:0 0 58px;
            border-radius:0;
            object-fit:contain;
            background:transparent;
            border:0;
            box-shadow:none;
        }
        .brand-copy{
            min-width:0;
        }
        .eyebrow{
            display:inline-flex;
            align-items:center;
            gap:8px;
            padding:8px 12px;
            border-radius:999px;
            background:rgba(23,107,135,.08);
            color:var(--brand);
            font-size:12px;
            font-weight:900;
            letter-spacing:.04em;
            text-transform:uppercase;
        }
        .eyebrow i{
            width:8px;
            height:8px;
            border-radius:999px;
            background:var(--accent);
            display:inline-block;
        }
        h1{
            margin:16px 0 10px;
            font-size:clamp(28px,3.8vw,42px);
            line-height:1.05;
            letter-spacing:-.03em;
        }
        .lead{
            margin:0;
            color:var(--muted);
            font-size:clamp(14px,1.15vw,17px);
            line-height:1.7;
            font-weight:600;
        }
        .card-body{
            padding:22px 26px 26px;
            display:grid;
            gap:16px;
        }
        .actions{
            display:flex;
            gap:10px;
            flex-wrap:wrap;
        }
        .btn{
            appearance:none;
            border:0;
            border-radius:12px;
            height:46px;
            padding:0 18px;
            font-size:14px;
            font-weight:900;
            text-decoration:none;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            cursor:pointer;
            transition:transform .15s ease, box-shadow .15s ease, background .15s ease;
        }
        .btn:hover{transform:translateY(-1px)}
        .btn.primary{
            background:linear-gradient(180deg,var(--brand),#0f5268);
            color:#fff;
            box-shadow:0 14px 28px rgba(23,107,135,.22);
        }
        .btn.secondary{
            background:#fff;
            color:var(--brand);
            border:1px solid var(--line);
        }
        .login-panel{
            display:none;
            border:1px solid var(--line);
            border-radius:16px;
            background:linear-gradient(180deg,#fff,#f8fbfe);
            padding:18px;
            gap:12px;
        }
        .login-panel.open{display:grid}
        .login-panel h2{
            margin:0;
            font-size:15px;
            font-weight:900;
            text-transform:uppercase;
            letter-spacing:.04em;
        }
        .field{
            display:grid;
            gap:6px;
        }
        .field label{
            color:var(--muted);
            font-size:12px;
            font-weight:850;
            text-transform:uppercase;
            letter-spacing:.03em;
        }
        .field input{
            height:44px;
            border:1px solid var(--line);
            border-radius:10px;
            background:#fff;
            padding:0 12px;
            font-size:14px;
            color:var(--ink);
            outline:none;
        }
        .field input:focus{
            border-color:rgba(23,107,135,.6);
            box-shadow:0 0 0 3px rgba(23,107,135,.10);
        }
        .message{
            min-height:18px;
            font-size:12px;
            font-weight:700;
            color:var(--muted);
        }
        .message.error{color:#b91c1c}
        .message.ok{color:#166534}
        .footer{
            padding:0 26px 24px;
            color:var(--muted);
            font-size:12px;
            line-height:1.55;
            font-weight:600;
        }
        @media (max-width: 560px){
            .card-head,.card-body,.footer{padding-left:18px;padding-right:18px}
            .brand{align-items:flex-start}
            .brand-logo{width:48px;height:48px;flex-basis:48px}
            .actions,.login-actions{flex-direction:column;align-items:stretch}
            .btn{width:100%}
        }
    </style>
</head>
<body>
    <div class="wrap">
        <main class="card">
            <div class="card-head">
                <div class="brand">
                    <img class="brand-logo" src="<?= html_escape(base_url('web/Assets/img/unnamed.png')) ?>" alt="Logo Dashboard GM">
                    <div class="brand-copy">
                        <div class="eyebrow"><i></i> Dashboard GM Portal</div>
                        <h1>Portal Dashboard Garment</h1>
                        <p class="lead">
                            Selamat Datang di portal dashboard garment. Silakan masuk untuk mengakses menu manajemen, atau langsung menuju dashboard.
                        </p>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div class="actions">
                    <a class="btn primary" href="<?= html_escape($dashboard_url ?? '#') ?>">Dashboard Publik</a>
                    <button type="button" class="btn secondary" id="showLoginButton">Login Admin</button>
                </div>

                <section class="login-panel" id="loginPanel" aria-hidden="true">
                    <h2>Login Manajemen</h2>
                    <p class="lead" style="font-size:13px;margin:0;">
                        Dipakai untuk membuka menu kalender kerja, riwayat QTY, dan analytics display.
                    </p>
                    <div class="field">
                        <label for="portalUsername">Username</label>
                        <input id="portalUsername" type="text" autocomplete="username" placeholder="Masukkan username">
                    </div>
                    <div class="field">
                        <label for="portalPassword">Password</label>
                        <input id="portalPassword" type="password" autocomplete="current-password" placeholder="Masukkan password">
                    </div>
                    <div class="actions">
                        <button type="button" class="btn primary" id="portalLoginButton">Masuk</button>
                        <a class="btn secondary" href="<?= html_escape($dashboard_url ?? '#') ?>">Lewati</a>
                    </div>
                    <div class="message" id="portalMessage"></div>
                </section>
            </div>

            <div class="footer" hidden>
                Setelah login berhasil, sesi akan dipakai oleh dashboard heat yang sama, jadi menu manajemen bisa dibuka tanpa login ulang.
            </div>
        </main>
    </div>

    <script>
        const portalLoginUrl = <?= json_encode($portal_login_url ?? '') ?>;
        const dashboardUrl = <?= json_encode($dashboard_url ?? '') ?>;
        const adminUrl = <?= json_encode($admin_url ?? '') ?>;

        const showLoginButton = document.getElementById('showLoginButton');
        const loginPanel = document.getElementById('loginPanel');
        const usernameInput = document.getElementById('portalUsername');
        const passwordInput = document.getElementById('portalPassword');
        const messageBox = document.getElementById('portalMessage');
        const loginButton = document.getElementById('portalLoginButton');

        function setMessage(text, kind = '') {
            messageBox.textContent = text || '';
            messageBox.className = kind ? `message ${kind}` : 'message';
        }

        function openLoginPanel() {
            loginPanel.classList.add('open');
            loginPanel.setAttribute('aria-hidden', 'false');
            setTimeout(() => usernameInput.focus(), 0);
        }

        async function submitLogin() {
            const username = usernameInput.value.trim();
            const password = passwordInput.value;

            if (!username || !password) {
                setMessage('Username dan password perlu diisi.', 'error');
                return;
            }

            loginButton.disabled = true;
            setMessage('Login sedang diproses...');

            try {
                const response = await fetch(portalLoginUrl, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({username, password})
                });
                const result = await response.json();

                if (!response.ok || !result.ok) {
                    throw new Error(result.message || 'Login gagal.');
                }

                setMessage('Login berhasil. Mengarahkan ke admin panel...', 'ok');
                window.location.href = adminUrl || dashboardUrl;
            } catch (error) {
                setMessage(error.message || 'Login gagal.', 'error');
            } finally {
                loginButton.disabled = false;
            }
        }

        showLoginButton.addEventListener('click', openLoginPanel);
        loginButton.addEventListener('click', submitLogin);
        passwordInput.addEventListener('keydown', event => {
            if (event.key === 'Enter') {
                submitLogin();
            }
        });
    </script>
</body>
</html>

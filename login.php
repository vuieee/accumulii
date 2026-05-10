<?php
require 'config.php';

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_login'])) {
    header('Content-Type: application/json');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['theme']    = $user['theme'];
        echo json_encode(['status' => 'success']);
    } else {
        $u = htmlspecialchars($username);
        echo json_encode(['status' => 'error', 'message' => "ERR: Authentication failed for '{$u}'."]);
    }
    exit;
}

if (isLoggedIn()) { header('Location: index.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>accumulii — auth</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/theme.css">
</head>
<body class="theme-dark">

    <div class="login-terminal">

        <!-- ASCII logo row -->
        <div class="login-logo">
█████╗  ██████╗  ██████╗ ██╗   ██╗███╗   ███╗██╗   ██╗██╗     ██╗██╗
██╔══██╗██╔════╝ ██╔════╝██║   ██║████╗ ████║██║   ██║██║     ██║██║
███████║██║      ██║     ██║   ██║██╔████╔██║██║   ██║██║     ██║██║
██╔══██║██║      ██║     ██║   ██║██║╚██╔╝██║██║   ██║██║     ██║██║
██║  ██║╚██████╗ ╚██████╗╚██████╔╝██║ ╚═╝ ██║╚██████╔╝███████╗██║██║
╚═╝  ╚═╝ ╚═════╝  ╚═════╝ ╚═════╝ ╚═╝     ╚═╝ ╚═════╝ ╚══════╝╚═╝╚═╝</div>

        <!-- System hint row -->
        <div class="login-hint">System Protocol: Enter username, or type <span class="c-cyan">register</span> to initialize a new instance.</div>

        <!-- Scrollable output history -->
        <div class="login-output" id="output"></div>

        <!-- Pinned input row -->
        <div class="login-input-row" id="input-container">
            <div class="prompt-wrap">
                <span class="prompt-body" id="prompt-text">login</span>
                <span class="prompt-arrow"></span>
            </div>
            <div class="input-text-area">
                <input type="text" id="cmd-input" autocomplete="off" spellcheck="false" autofocus>
                <span class="typed-text" id="typed-text"></span>
                <span class="cursor"></span>
            </div>
        </div>

    </div>

    <script>
    (function () {
        const input      = document.getElementById('cmd-input');
        const typedSpan  = document.getElementById('typed-text');
        const promptLabel= document.getElementById('prompt-text');
        const output     = document.getElementById('output');
        const inputRow   = document.getElementById('input-container');

        let step = 0, username = '', password = '';

        document.addEventListener('click', () => input.focus());

        input.addEventListener('input', () => {
            typedSpan.textContent = step === 1
                ? '·'.repeat(input.value.length)
                : input.value;
        });

        function appendLine(html, cls) {
            const d = document.createElement('div');
            d.className = 'boot-row' + (cls ? ' ' + cls : '');
            d.innerHTML = html;
            output.appendChild(d);
            output.scrollTop = output.scrollHeight;
        }

        function appendEcho(label, text) {
            const row = document.createElement('div');
            row.className = 'cmd-echo';
            row.innerHTML =
                `<div class="prompt-wrap">` +
                    `<span class="prompt-body">${label}</span>` +
                    `<span class="prompt-arrow"></span>` +
                `</div>` +
                `<span class="cmd-echo-text">${escHtml(text)}</span>`;
            output.appendChild(row);
            output.scrollTop = output.scrollHeight;
        }

        function escHtml(s) {
            return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
        }

        const delay = ms => new Promise(r => setTimeout(r, ms));

        async function runBootSequence(authMs) {
            inputRow.style.visibility = 'hidden';

            const logs = [
                `Authenticating <span class="c-cyan">${escHtml(username)}</span> … <span class="boot-ok">[OK]</span> <span style="color:var(--dim)">${authMs}ms</span>`,
                `Loading personal and system profiles…`,
                `Establishing secure connection to Accumulii Core… <span class="boot-ok">[OK]</span>`,
                `Resolving user registry and fetching layout…`
            ];

            for (const msg of logs) {
                appendLine(msg);
                await delay(Math.random() * 350 + 180);
            }

            /* progress bar */
            const barEl = document.createElement('div');
            barEl.className = 'boot-bar';
            output.appendChild(barEl);

            let pct = 0;
            while (pct < 100) {
                pct = Math.min(100, pct + Math.floor(Math.random() * 15 + 5));
                const filled = Math.floor(pct / 5);
                barEl.textContent = `Syncing: [${'█'.repeat(filled)}${'░'.repeat(20 - filled)}] ${pct}%`;
                output.scrollTop = output.scrollHeight;
                await delay(pct < 60 ? Math.random() * 180 + 80 : Math.random() * 40 + 15);
            }

            await delay(260);
            appendLine(`Session established — transferring control…`, 'boot-done');
            await delay(500);
            window.location.href = 'index.php';
        }

        input.addEventListener('keydown', async (e) => {
            if (e.key !== 'Enter') return;
            const val = input.value.trim();

            if (step === 0) {
                if (!val) return;
                if (val.toLowerCase() === 'register') {
                    window.location.href = 'register.php';
                    return;
                }
                username = val;
                appendEcho('login', username);
                step = 1;
                promptLabel.textContent = 'password';
                input.value = '';
                typedSpan.textContent = '';

            } else if (step === 1) {
                password = val;
                appendEcho('password', '·'.repeat(password.length));
                input.value = '';
                typedSpan.textContent = '';
                input.disabled = true;

                const fd = new FormData();
                fd.append('ajax_login', '1');
                fd.append('username', username);
                fd.append('password', password);

                try {
                    const loginStart = performance.now();
                    const res  = await fetch('login.php', { method: 'POST', body: fd });
                    const data = await res.json();
                    const loginMs = Math.round(performance.now() - loginStart);

                    if (data.status === 'success') {
                        await runBootSequence(loginMs);
                    } else {
                        appendLine(`<span style="color:var(--red)">${escHtml(data.message)}</span>`);
                        step = 0;
                        promptLabel.textContent = 'login';
                        input.disabled = false;
                        input.focus();
                    }
                } catch {
                    appendLine(`<span style="color:var(--red)">ERR: Server connection refused.</span>`);
                    step = 0;
                    promptLabel.textContent = 'login';
                    input.disabled = false;
                    input.focus();
                }
            }
        });
    })();
    </script>
</body>
</html>
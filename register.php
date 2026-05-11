<?php
require 'config.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

// AJAX registration — returns JSON; the frontend drives the success animation.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_register'])) {
    header('Content-Type: application/json');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (strlen($username) < 3 || strlen($password) < 4) {
        echo json_encode(['status' => 'error', 'message' => "ERR: Username requires 3+ chars, password requires 4+ chars."]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        $u = htmlspecialchars($username);
        echo json_encode(['status' => 'error', 'message' => "ERR: Identity '{$u}' is already registered."]);
        exit;
    }

    $hash  = password_hash($password, PASSWORD_DEFAULT);
    $email = strtolower($username) . '@accumulii.local';

    $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    if ($stmt->execute([$username, $email, $hash])) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => "ERR: Registry write failed. Core system error."]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>accumulii — register</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/theme.css">
</head>
<body class="theme-dark">

    <div class="login-terminal">
        <div class="login-logo">
█████╗  ██████╗  ██████╗ ██╗   ██╗███╗   ███╗██╗   ██╗██╗     ██╗██╗
██╔══██╗██╔════╝ ██╔════╝██║   ██║████╗ ████║██║   ██║██║     ██║██║
███████║██║      ██║     ██║   ██║██╔████╔██║██║   ██║██║     ██║██║
██╔══██║██║      ██║     ██║   ██║██║╚██╔╝██║██║   ██║██║     ██║██║
██║  ██║╚██████╗ ╚██████╗╚██████╔╝██║ ╚═╝ ██║╚██████╔╝███████╗██║██║
╚═╝  ╚═╝ ╚═════╝  ╚═════╝ ╚═════╝ ╚═╝     ╚═╝ ╚═════╝ ╚══════╝╚═╝╚═╝</div>

        <div class="login-hint">Registration Protocol: Follow prompts to initialize new instance, or type <span class="c-cyan">exit</span> to abort.</div>
        <div class="login-output" id="output"></div>

        <div class="login-input-row" id="input-container">
            <div class="prompt-wrap">
                <span class="prompt-body" id="prompt-text">username</span>
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
        const input       = document.getElementById('cmd-input');
        const typedSpan   = document.getElementById('typed-text');
        const promptLabel = document.getElementById('prompt-text');
        const output      = document.getElementById('output');
        const inputRow    = document.getElementById('input-container');

        // step 0 = username, step 1 = password, step 2 = confirm password
        let step = 0, username = '', password = '';

        // preventScroll avoids the layout jump caused by the browser auto-scrolling
        // the page when focus() is called on mobile or in certain scroll positions.
        document.addEventListener('click', () => {
            if (window.getSelection().toString() === '') {
                input.focus({ preventScroll: true });
            }
        });
        input.focus({ preventScroll: true });

        // Mask mirror text with dots during password and confirm steps.
        input.addEventListener('input', () => {
            typedSpan.textContent = (step === 1 || step === 2)
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

        /**
         * Plays the post-registration confirmation animation, then redirects
         * to login.php so the user can authenticate with their new credentials.
         */
        async function runRegistrationSequence() {
            inputRow.style.visibility = 'hidden';

            const logs = [
                `Validating credentials… <span class="boot-ok">[OK]</span>`,
                `Allocating registry space for <span class="c-cyan">${escHtml(username)}</span>…`,
                `Generating generic Accumulii environment… <span class="boot-ok">[OK]</span>`,
                `Writing to secure database…`
            ];

            for (const msg of logs) {
                appendLine(msg);
                await delay(Math.random() * 300 + 150);
            }

            await delay(400);
            appendLine(`Registration successful. Rerouting to authentication gate…`, 'boot-done');
            await delay(800);
            window.location.href = 'login.php';
        }

        input.addEventListener('keydown', async (e) => {
            if (e.key !== 'Enter') return;
            const val = input.value.trim();
            if (!val) return;

            if (val.toLowerCase() === 'exit') {
                appendEcho(promptLabel.textContent, val);
                appendLine(`<span style="color:var(--dim)">Aborting registration. Returning to login…</span>`);
                input.disabled = true;
                await delay(400);
                window.location.href = 'login.php';
                return;
            }

            if (step === 0) {
                username = val;
                appendEcho('username', username);
                step = 1;
                promptLabel.textContent = 'password';
                input.value = '';
                typedSpan.textContent = '';

            } else if (step === 1) {
                password = val;
                appendEcho('password', '·'.repeat(password.length));
                step = 2;
                promptLabel.textContent = 'confirm';
                input.value = '';
                typedSpan.textContent = '';

            } else if (step === 2) {
                const confirmPassword = val;
                appendEcho('confirm', '·'.repeat(confirmPassword.length));
                input.value = '';
                typedSpan.textContent = '';
                input.disabled = true;

                if (password !== confirmPassword) {
                    appendLine(`<span style="color:var(--red)">ERR: Passwords do not match. Resetting protocol.</span>`);
                    step = 0;
                    username = '';
                    password = '';
                    promptLabel.textContent = 'username';
                    input.disabled = false;
                    input.focus({ preventScroll: true });
                    return;
                }

                const fd = new FormData();
                fd.append('ajax_register', '1');
                fd.append('username', username);
                fd.append('password', password);

                try {
                    const res  = await fetch('register.php', { method: 'POST', body: fd });
                    const data = await res.json();

                    if (data.status === 'success') {
                        await runRegistrationSequence();
                    } else {
                        appendLine(`<span style="color:var(--red)">${escHtml(data.message)}</span>`);
                        step = 0;
                        promptLabel.textContent = 'username';
                        input.disabled = false;
                        input.focus({ preventScroll: true });
                    }
                } catch {
                    appendLine(`<span style="color:var(--red)">ERR: Server connection refused.</span>`);
                    step = 0;
                    promptLabel.textContent = 'username';
                    input.disabled = false;
                    input.focus({ preventScroll: true });
                }
            }
        });
    })();
    </script>
</body>
</html>

<?php
require 'config.php';
requireAuth();
$theme = $_SESSION['theme'] ?? 'dark';
$user  = htmlspecialchars($_SESSION['username']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>accumulii — <?php echo $user; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/theme.css">
</head>
<body class="theme-<?php echo htmlspecialchars($_SESSION['theme'] ?? 'dark'); ?>" data-user="<?php echo $user; ?>">

    <!--
        LAYOUT: CSS Grid  →  row 1 = #output (scrolls)
                             row 2 = #input-row (pinned)
        RULE: #output appends only. Never re-rendered.
    -->
    <div id="terminal">
        <div id="output"></div>

        <div id="input-row">
            <div class="prompt-wrap">
                <span class="prompt-body">~ <?php echo $user; ?></span>
                <span class="prompt-arrow"></span>
            </div>
            <div class="input-text-area">
                <input type="text" id="cmd-input" autocomplete="off" spellcheck="false" autofocus>
                <span class="typed-text" id="typed-text"></span>
                <span class="cursor" id="cursor"></span>
            </div>
        </div>
    </div>

    <script src="js/api.js"></script>
    <script src="js/commands.js"></script>
    <script>
    (function () {
        const input     = document.getElementById('cmd-input');
        const output    = document.getElementById('output');
        const typedSpan = document.getElementById('typed-text');
        const user      = document.body.dataset.user;

        let history = [], histIdx = -1;

        /* ─── focus trap ─────────────────────────── */
        document.addEventListener('click', () => input.focus());
        input.focus();

        /* ─── mirror typing ──────────────────────── */
        input.addEventListener('input', () => {
            typedSpan.textContent = input.value;
        });

        /* ─── append helpers (never re-render) ───── */

        /**
         * Append a command-echo row: prompt badge + text
         */
        function appendEcho(cmdStr) {
            const row = document.createElement('div');
            row.className = 'cmd-echo';
            row.innerHTML =
                `<div class="prompt-wrap">` +
                    `<span class="prompt-body">~ ${user}</span>` +
                    `<span class="prompt-arrow"></span>` +
                `</div>` +
                `<span class="cmd-echo-text">${escHtml(cmdStr)}</span>`;
            output.appendChild(row);
        }

        /**
         * Append an HTML result block (from API or command)
         */
        function appendResult(html, extraClass) {
            if (!html && html !== 0) return;
            const block = document.createElement('div');
            block.className = 'result-block' + (extraClass ? ' ' + extraClass : '');
            block.innerHTML = html;
            output.appendChild(block);
        }

        /**
         * Scroll output to bottom
         */
        function scrollBottom() {
            output.scrollTop = output.scrollHeight;
        }

        function escHtml(s) {
            return String(s)
                .replace(/&/g,'&amp;')
                .replace(/</g,'&lt;')
                .replace(/>/g,'&gt;');
        }

        /* ─── auto-fetch on load ─────────────────── */
        (async () => {
            const res = await apiCall('fetchme');
            if (res.output) {
                output.insertAdjacentHTML('beforeend', res.output);
                // Hint row after fetchme
                const hint = document.createElement('div');
                hint.className = 'banner-row';
                hint.innerHTML = `Type <span style="color:var(--cyan)">help</span> to see available commands.`;
                output.appendChild(hint);
                scrollBottom();
            }
        })();

        /* ─── keyboard handler ───────────────────── */
        input.addEventListener('keydown', async (e) => {
            if (e.key === 'Enter') {
                const cmd = input.value;
                input.value = '';
                typedSpan.textContent = '';

                if (cmd.trim()) {
                    history.push(cmd);
                    histIdx = history.length;
                }

                appendEcho(cmd);

                const result = await processCommand(cmd, { appendResult, scrollBottom });

                if (result !== null && result !== undefined) {
                    appendResult(result);
                }

                scrollBottom();

            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (histIdx > 0) {
                    histIdx--;
                    input.value = history[histIdx];
                    typedSpan.textContent = input.value;
                }
            } else if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (histIdx < history.length - 1) {
                    histIdx++;
                    input.value = history[histIdx];
                    typedSpan.textContent = input.value;
                } else {
                    histIdx = history.length;
                    input.value = '';
                    typedSpan.textContent = '';
                }
            }
        });
    })();
    </script>
</body>
</html>
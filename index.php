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
        let lastCommandType = '';

        // Allow text selection for copy-paste while still routing all other
        // clicks back to the hidden input so typing works immediately.
        document.addEventListener('click', () => {
            if (window.getSelection().toString() === '') {
                input.focus();
            }
        });
        input.focus();

        // Mirror keystrokes into the visible span so the real input can stay hidden.
        input.addEventListener('input', () => {
            typedSpan.textContent = input.value;
        });

        /**
         * Appends a command-echo row (prompt chip + typed text) to the output.
         * Returns the element so the caller can scroll to it.
         *
         * @param {string} cmdStr - The raw command string to display.
         * @returns {HTMLElement}
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
            return row;
        }

        /**
         * Appends an HTML result block to the output area.
         *
         * @param {string} html       - HTML string produced by a command handler.
         * @param {string} extraClass - Optional additional CSS class.
         */
        function appendResult(html, extraClass) {
            if (!html && html !== 0) return;
            const block = document.createElement('div');
            block.className = 'result-block' + (extraClass ? ' ' + extraClass : '');
            block.innerHTML = html;
            output.appendChild(block);
        }

        function scrollBottom() {
            output.scrollTop = output.scrollHeight;
        }

        function escHtml(s) {
            return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
        }

        /**
         * Scrolls the output so that the given row is at the top of the viewport.
         * Exposed globally so async command handlers can call it after DOM updates.
         *
         * @param {HTMLElement} rowElement
         */
        window.forceScrollToRow = function(rowElement) {
            rowElement.scrollIntoView({ behavior: 'auto', block: 'start' });
        };

        // Fetch and render the splash screen on initial page load.
        (async () => {
            const res = await apiCall('fetchme');
            if (res.output) {
                output.insertAdjacentHTML('beforeend', res.output);
                const hint = document.createElement('div');
                hint.className = 'banner-row';
                hint.innerHTML = `Type <span style="color:var(--cyan)">help</span> to see available commands.`;
                output.appendChild(hint);
                scrollBottom();
            }
        })();

        input.addEventListener('keydown', async (e) => {
            if (e.key === 'Enter') {
                const cmd = input.value;
                input.value = '';
                typedSpan.textContent = '';

                if (cmd.trim()) {
                    history.push(cmd);
                    histIdx = history.length;
                }

                const parts   = cmd.trim().split(/\s+/);
                const baseCmd = (parts[0] || '').toLowerCase();

                // Scroll strategy: jump to the echoed command row after execution
                // so the user always sees their prompt at the top. Exceptions:
                //   clear      — no output row exists to scroll to.
                //   profile × 2 — avoid jarring jumps when rapidly re-running profile.
                let shouldScrollToTop = true;
                if (baseCmd === 'clear') {
                    shouldScrollToTop = false;
                } else if (baseCmd === 'profile' && lastCommandType === 'profile') {
                    shouldScrollToTop = false;
                }

                const activeRow = appendEcho(cmd);
                const result    = await processCommand(cmd, { appendResult, scrollBottom });

                if (result !== null && result !== undefined) {
                    appendResult(result);
                }

                if (shouldScrollToTop && window.forceScrollToRow) {
                    window.forceScrollToRow(activeRow);
                } else {
                    scrollBottom();
                }

                lastCommandType = baseCmd;

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

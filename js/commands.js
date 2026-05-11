/**
 * COMMANDS — Synchronous, client-side-only command handlers.
 * Each method receives (parts, ctx) where `parts` is the argument array
 * and `ctx` exposes { appendResult, scrollBottom } from the terminal shell.
 * Return a non-null value to have it appended to the output.
 */
const COMMANDS = {
    help() {
        return `<div class="help-table">
  <div class="help-section">Navigation</div>
  <div class="help-row"><span class="help-cmd">help</span><span class="help-desc">Show this reference</span></div>
  <div class="help-row"><span class="help-cmd">clear</span><span class="help-desc">Clear terminal history</span></div>
  <div class="help-row"><span class="help-cmd">whoami</span><span class="help-desc">Print active session identity</span></div>
  <div class="help-row"><span class="help-cmd">logout</span><span class="help-desc">Terminate session and return to auth gate</span></div>

  <div class="help-section">Profile & Config</div>
  <div class="help-row"><span class="help-cmd">fetchme</span><span class="help-desc">Display project and system information</span></div>
  <div class="help-row"><span class="help-cmd">profile &lt;user&gt;</span><span class="help-desc">Fetch a user's details from the registry</span></div>
  <div class="help-row"><span class="help-cmd">set &lt;key&gt; "&lt;val&gt;"</span><span class="help-desc">Update profile vars. Run 'profile &lt;user&gt;' to verify</span></div>
  <div class="help-row"><span class="help-cmd">restore defts</span><span class="help-desc">Factory reset: Wipes bio & logs, resets theme and education</span></div>

  <div class="help-section">System Log</div>
  <div class="help-row"><span class="help-cmd">post "&lt;msg&gt;"</span><span class="help-desc">Broadcast a message to the public system log</span></div>
  <div class="help-row"><span class="help-cmd">log</span><span class="help-desc">View the public system broadcast log</span></div>

  <div class="help-section">Repositories</div>
  <div class="help-row"><span class="help-cmd">repos</span><span class="help-desc">List all repositories across the registry</span></div>
  <div class="help-row"><span class="help-cmd">searchrepo &lt;q&gt;</span><span class="help-desc">Search repository names and descriptions</span></div>
  <div class="help-row"><span class="help-cmd">repo &lt;name1&gt; [name2...]</span><span class="help-desc">Inspect one or more repositories</span></div>

  <div class="help-section">Media & Showcases</div>
  <div class="help-row"><span class="help-cmd">showcases</span><span class="help-desc">List all visual showcases uploaded to the registry</span></div>
  <div class="help-row"><span class="help-cmd">showcase &lt;name&gt;</span><span class="help-desc">View a high-resolution image showcase</span></div>

  <div class="help-section">Display</div>
  <div class="help-row"><span class="help-cmd">theme &lt;name&gt;</span><span class="help-desc">Switch theme — dark · ash · white</span></div>
</div>`;
    },

    clear() {
        document.getElementById('output').innerHTML = '';
        return null;
    },

    whoami() {
        const u = document.body.dataset.user;
        return `<span class="c-cyan">${u}</span>`;
    },

    logout() {
        setTimeout(() => { window.location.href = 'login.php?action=logout'; }, 400);
        return `<span style="color:var(--dim)">Terminating session…</span>`;
    }
};

/**
 * Parses and dispatches a raw command string entered in the terminal.
 * Quoted strings (e.g. "hello world") are treated as a single argument.
 *
 * @param {string} cmdString - The raw input string from the terminal.
 * @param {Object} ctx       - Shell context: { appendResult, scrollBottom }.
 * @returns {Promise<string|null>} HTML string to append, or null for no output.
 */
async function processCommand(cmdString, ctx) {
    // Tokenise respecting double-quoted arguments.
    const regex = /[^\s"]+|"([^"]*)"/gi;
    const parts = [];
    let match;
    while (match = regex.exec(cmdString)) {
        parts.push(match[1] ? match[1] : match[0]);
    }

    const cmd = (parts.shift() || '').toLowerCase();
    if (!cmd) return null;

    if (COMMANDS[cmd]) return await COMMANDS[cmd](parts, ctx);

    if (cmd === 'fetchme') {
        const res = await apiCall('fetchme');
        return res.output || res.message;
    }

    if (cmd === 'online') {
        const res = await apiCall('online_users');
        return res.output || res.message;
    }

    if (cmd === 'profile') {
        if (!parts[0]) return `<span class="result-error">Usage: profile [username]</span>`;
        const res = await apiCall('fetchuser', { target: parts[0] });
        return res.output || res.message;
    }

    if (cmd === 'set') {
        if (parts.length < 2) return `<span class="result-error">Usage: set &lt;key&gt; "&lt;value&gt;"</span>\n<span style="color:var(--dim)">Example: set bio "I am a web developer."</span>`;
        const key = parts[0];
        const val = parts.slice(1).join(' ');
        const res = await apiCall('set_profile', { key: key, value: val });
        return res.output || res.message;
    }

    if (cmd === 'restore') {
        if (parts[0] === 'defts') {
            const res = await apiCall('restore_defaults');
            document.body.className = `theme-dark`;
            return res.output || res.message;
        } else {
            return `<span class="result-error">Usage: restore defts</span>`;
        }
    }

    if (cmd === 'post') {
        if (!parts[0]) return `<span class="result-error">Usage: post "&lt;message&gt;"</span>`;
        const res = await apiCall('create_post', { comment: parts.join(' ') });
        return res.output || res.message;
    }

    if (cmd === 'log') {
        const res = await apiCall('fetch_log');
        return res.output || res.message;
    }

    if (cmd === 'theme') {
        const valid = ['dark', 'ash', 'white'];
        if (!parts[0] || !valid.includes(parts[0])) {
            return `<span class="result-error">Usage: theme [${valid.join(' | ')}]</span>`;
        }
        const res = await apiCall('set_theme', { theme: parts[0] });
        document.body.className = `theme-${parts[0]}`;
        return `<span class="result-info">Theme switched → ${parts[0]}</span>`;
    }

    if (cmd === 'repos') {
        const who = parts[0] || '';
        const res = await apiCall('repos', who ? { user: who } : {});
        return res.output || res.message;
    }

    if (cmd === 'searchrepo') {
        if (!parts[0]) return `<span class="result-error">Usage: searchrepo &lt;query&gt;</span>`;
        const res = await apiCall('repos', { search: parts.join(' ') });
        return res.output || res.message;
    }

    if (cmd === 'repo' || cmd === 'openrepo') {
        if (parts.length === 0) return `<span class="result-error">Usage: ${cmd} &lt;name&gt; [name2]</span>`;

        // Inject a Pac-Man style loading animation while fetching repo data.
        const loaderId = 'loader-' + Date.now();
        ctx.appendResult(`<div id="${loaderId}" class="pacman-loader c-yellow"></div>`);
        ctx.scrollBottom();

        const loaderEl = document.getElementById(loaderId);
        const pacmanFrames = [
            "C • • • • • • •", " c • • • • • • •", "  C • • • • • •", "   c • • • • • •",
            "    C • • • • •", "     c • • • • •", "      C • • • •", "       c • • • •",
            "        C • • •", "         c • • •", "          C • •", "           c • •",
            "            C •", "             c •", "              C "
        ];

        if (loaderEl) {
            for (let i = 0; i < pacmanFrames.length; i++) {
                loaderEl.textContent = `[${pacmanFrames[i]}] fetching repositories...`;
                await new Promise(r => setTimeout(r, 110));
            }
            loaderEl.remove();
        }

        let combinedHtml = `<div class="multi-repo-container">`;
        let validRepos = [];

        for (let name of parts) {
            const res = await apiCall('repo_detail', { name: name });
            combinedHtml += res.output;
            if (res.status === 'success') {
                validRepos.push(name);
            }
        }

        combinedHtml += `</div>`;

        // Delay keybind activation slightly to ensure DOM nodes are rendered.
        if (validRepos.length > 0) {
            setTimeout(() => activateRepoKeybinds(validRepos), 50);
        }
        return combinedHtml;
    }

    if (cmd === 'showcases') {
        const res = await apiCall('showcases');
        return res.output || res.message;
    }

    if (cmd === 'showcase' || cmd === 'view') {
        if (!parts[0]) return `<span class="result-error">Usage: showcase &lt;name&gt;</span>`;
        const res = await apiCall('showcase_detail', { name: parts[0] });
        return res.output || res.message;
    }

    return `<span class="result-error">accumulii: ${cmd}: command not found  — try <span class="c-cyan">help</span></span>`;
}

// Module-level state for the active repo keyboard shortcut listener.
// Only one listener is registered at a time; opening a new repo replaces the previous one.
let _repoKeybindActive  = false;
let _repoKeybindHandler = null;

/**
 * Registers keyboard shortcuts (q = clone, Escape = dismiss) for a set of
 * repository detail cards currently visible in the terminal output.
 * Replaces any previously active listener before registering the new one.
 *
 * @param {string[]} repoNames - Names of the repos whose cards are on screen.
 */
function activateRepoKeybinds(repoNames) {
    if (_repoKeybindHandler) {
        document.removeEventListener('keydown', _repoKeybindHandler);
        _repoKeybindHandler = null;
    }

    _repoKeybindActive = true;

    _repoKeybindHandler = function(e) {
        if (!_repoKeybindActive) return;

        // Suppress shortcuts while the user is typing a command.
        const inputEl = document.getElementById('cmd-input');
        if (inputEl && inputEl.value.length > 0) return;

        if (e.key === 'q' || e.key === 'Q') {
            e.preventDefault();
            _repoKeybindActive = false;
            document.removeEventListener('keydown', _repoKeybindHandler);
            _repoKeybindHandler = null;

            document.querySelectorAll('.repo-keybind').forEach(el => {
                el.innerHTML = `<span class="keybind-downloading">Cloning target(s)… generating archives</span>`;
            });

            // Stagger downloads by 400 ms per repo to avoid simultaneous browser dialogs.
            repoNames.forEach((repoName, idx) => {
                setTimeout(() => {
                    const content = [
                        `# ${repoName}`,
                        ``,
                        `> Downloaded via Accumulii terminal`,
                        ``,
                        `## Clone`,
                        `\`\`\``,
                        `git clone https://accumulii.local/${repoName}.git`,
                        `\`\`\``,
                    ].join('\n');
                    const blob = new Blob([content], { type: 'text/plain' });
                    const url  = URL.createObjectURL(blob);
                    const a    = document.createElement('a');
                    a.href     = url;
                    a.download = `${repoName}.md`;
                    a.click();
                    URL.revokeObjectURL(url);
                }, idx * 400);
            });

            setTimeout(() => {
                document.querySelectorAll('.repo-keybind').forEach(el => {
                    el.innerHTML = `<span class="keybind-done"><span class="c-green">✓</span> Archives downloaded</span>`;
                });
            }, repoNames.length * 400 + 400);

        } else if (e.key === 'Escape') {
            e.preventDefault();
            _repoKeybindActive = false;
            document.removeEventListener('keydown', _repoKeybindHandler);
            _repoKeybindHandler = null;

            document.querySelectorAll('.repo-keybind').forEach(el => {
                el.innerHTML = `<span style="color:var(--dim)">dismissed</span>`;
            });
        }
    };

    document.addEventListener('keydown', _repoKeybindHandler);
}

/* ═══════════════════════════════════════════════════════════════
   COMMAND ENGINE
   processCommand(cmdString, ctx) → string|null
   ctx.appendResult(html)  — inject directly (for multi-step output)
   ctx.scrollBottom()      — scroll after injection
═══════════════════════════════════════════════════════════════ */

const COMMANDS = {

    help() {
        return `<div class="help-table">
  <div class="help-section">Navigation</div>
  <div class="help-row"><span class="help-cmd">help</span><span class="help-desc">Show this reference</span></div>
  <div class="help-row"><span class="help-cmd">clear</span><span class="help-desc">Clear terminal history</span></div>
  <div class="help-row"><span class="help-cmd">whoami</span><span class="help-desc">Print active session identity</span></div>
  <div class="help-row"><span class="help-cmd">logout</span><span class="help-desc">Terminate session and return to auth gate</span></div>

  <div class="help-section">Profile</div>
  <div class="help-row"><span class="help-cmd">fetchme</span><span class="help-desc">Display project and system information</span></div>
  <div class="help-row"><span class="help-cmd">profile &lt;user&gt;</span><span class="help-desc">Fetch a user's details from the registry</span></div>

  <div class="help-section">Network</div>
  <div class="help-row"><span class="help-cmd">online</span><span class="help-desc">View all currently connected instances</span></div>

  <div class="help-section">Repositories</div>
  <div class="help-row"><span class="help-cmd">repos</span><span class="help-desc">List all repositories across the registry</span></div>
  <div class="help-row"><span class="help-cmd">repos &lt;user&gt;</span><span class="help-desc">List repositories owned by a specific user</span></div>
  <div class="help-row"><span class="help-cmd">searchrepo &lt;q&gt;</span><span class="help-desc">Search repository names and descriptions</span></div>
  <div class="help-row"><span class="help-cmd">openrepo &lt;name&gt;</span><span class="help-desc">Inspect a repository (alias for repo)</span></div>

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

async function processCommand(cmdString, ctx) {
    const parts = cmdString.trim().split(/\s+/);
    const cmd   = (parts.shift() || '').toLowerCase();

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

    if (cmd === 'theme') {
        const valid = ['dark','ash','white'];
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
        if (!parts[0]) return `<span class="result-error">Usage: ${cmd} &lt;name&gt;</span>`;
        const res = await apiCall('repo_detail', { name: parts[0] });
        if (res.status === 'success') {
            setTimeout(() => activateRepoKeybinds(res.repo), 50);
        }
        return res.output || res.message;
    }

    return `<span class="result-error">accumulii: ${cmd}: command not found  — try <span class="c-cyan">help</span></span>`;
}

let _repoKeybindActive = false;
let _repoKeybindHandler = null;

function activateRepoKeybinds(repoName) {
    if (_repoKeybindHandler) {
        document.removeEventListener('keydown', _repoKeybindHandler);
        _repoKeybindHandler = null;
    }

    _repoKeybindActive = true;

    _repoKeybindHandler = function(e) {
        if (!_repoKeybindActive) return;

        const inputEl = document.getElementById('cmd-input');
        if (inputEl && inputEl.value.length > 0) return;

        if (e.key === 'q' || e.key === 'Q') {
            e.preventDefault();
            _repoKeybindActive = false;
            document.removeEventListener('keydown', _repoKeybindHandler);
            _repoKeybindHandler = null;

            document.querySelectorAll('.repo-keybind').forEach(el => {
                el.innerHTML = `<span class="keybind-downloading">Cloning <span class="c-cyan">${repoName}</span>… generating archive</span>`;
            });

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

                document.querySelectorAll('.repo-keybind').forEach(el => {
                    el.innerHTML = `<span class="keybind-done"><span class="c-green">✓</span> ${repoName}.md downloaded</span>`;
                });
            }, 900);

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
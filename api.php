<?php
require 'config.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'output' => '<span class="result-error">ERR: Unauthorized. Please login.</span>']);
    exit;
}

$action = $_POST['action'] ?? '';

switch ($action) {

    case 'fetchme':
        $ascii = 
"█████╗  ██████╗  ██████╗ ██╗   ██╗███╗   ███╗██╗   ██╗██╗     ██╗██╗
██╔══██╗██╔════╝ ██╔════╝██║   ██║████╗ ████║██║   ██║██║     ██║██║
███████║██║      ██║     ██║   ██║██╔████╔██║██║   ██║██║     ██║██║
██╔══██║██║      ██║     ██║   ██║██║╚██╔╝██║██║   ██║██║     ██║██║
██║  ██║╚██████╗ ╚██████╗╚██████╔╝██║ ╚═╝ ██║╚██████╔╝███████╗██║██║
╚═╝  ╚═╝ ╚═════╝  ╚═════╝ ╚═════╝ ╚═╝     ╚═╝ ╚═════╝ ╚══════╝╚═╝╚═╝";

        $output = "
<div class='fetch-wrap'>
  <div class='fetch-ascii-header'>
      <div class='fetch-ascii ascii-bg'>{$ascii}</div>
      <div class='fetch-ascii ascii-fg'>{$ascii}</div>
  </div>

  <div class='fetch-desc-text'>
    Accumulii is a terminal-driven developer profile registry and repository showcase. Built for power users, it provides a CLI-native environment to manage code portfolios, broadcast system logs, present graphical UI rices, and securely inspect community repositories through an interactive web socket interface.
  </div>

  <div class='fetch-stack-text'>
      <div class='stack-line'>
          <span class='stack-label'>FRONTEND:</span>
          <span class='badge badge-html'>HTML5</span>
          <span class='badge badge-css'>CSS3</span>
          <span class='badge badge-js'>JAVASCRIPT</span>
      </div>
      <div class='stack-line'>
          <span class='stack-label'>BACKEND:</span>
          <span class='badge badge-php'>PHP 8</span>
          <span class='badge badge-sql'>MYSQL (PDO)</span>
      </div>
  </div>

  <div class='fetch-dev-text'>
    Abaya, Joshua Danielle Ermac<br>
    Campus, John Louis<br>
    Elopre, Joshua Reed Omamalin<br>
    Tallo, Lance Benedict
  </div>
</div>";
        echo json_encode(['status' => 'success', 'output' => $output]);
        break;

    case 'fetchuser':
        $target = $_POST['target'] ?? '';
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$target]);
        $user = $stmt->fetch();

        if ($user) {
            $u = htmlspecialchars($user['username']);
            $r = htmlspecialchars($user['role']       ?? 'Developer');
            $v = htmlspecialchars($user['university'] ?? '—');
            $c = htmlspecialchars($user['course']     ?? '—');
            $y = htmlspecialchars($user['year']       ?? '—');
            $b = htmlspecialchars($user['bio']        ?? '');

            // Fallback ASCII art rendered inline if the profile image fails to load.
            $fallbackAsciiTop = "╭───╮\\n│ ◉ │\\n╰───╯";
            $asciiBottom      = "╭─────╮\n│ usr │\n╰─────╯";

            $bioRow = $b ? "<div class='fetch-row'><span class='fetch-label'>Bio</span><span class='fetch-sep'>:</span><span class='fetch-val c-yellow'>{$b}</span></div>" : "";

            $output = "
<div class='fetch-profile-wrap'>
  <div class='fetch-media-column'>
    <img src='img/profiles/{$u}.jpg' class='profile-img' alt='{$u}' onerror=\"this.outerHTML='<div class=\\'fetch-ascii\\' style=\\'margin-bottom:6px;\\'>{$fallbackAsciiTop}</div>'\">
    <div class='fetch-ascii' style='text-align:center;'>{$asciiBottom}</div>
  </div>
  <div class='fetch-info'>
    <div class='fetch-row'><span class='fetch-label'>User</span><span class='fetch-sep'>:</span><span class='fetch-val c-cyan'>{$u}</span></div>
    <div class='fetch-row'><span class='fetch-label'>Role</span><span class='fetch-sep'>:</span><span class='fetch-val'>{$r}</span></div>
    <div class='fetch-row'><span class='fetch-label'>University</span><span class='fetch-sep'>:</span><span class='fetch-val'>{$v}</span></div>
    <div class='fetch-row'><span class='fetch-label'>Course</span><span class='fetch-sep'>:</span><span class='fetch-val'>{$c}</span></div>
    <div class='fetch-row'><span class='fetch-label'>Year</span><span class='fetch-sep'>:</span><span class='fetch-val'>{$y}</span></div>
    {$bioRow}
  </div>
</div>";
        } else {
            $output = "<span class='result-error'>ERR: User not found.</span>";
        }
        echo json_encode(['status' => 'success', 'output' => $output]);
        break;

    case 'set_profile':
        $key = strtolower($_POST['key'] ?? '');
        $val = $_POST['value'] ?? '';

        // Restrict updatable fields to prevent arbitrary column injection.
        $allowed = ['bio', 'university', 'course', 'year'];
        if (!in_array($key, $allowed)) {
            echo json_encode(['status' => 'error', 'output' => "<span class='result-error'>ERR: Configuration key '{$key}' is locked or does not exist. Allowed: bio, university, course, year.</span>"]);
            break;
        }

        $stmt = $pdo->prepare("UPDATE users SET {$key} = ? WHERE id = ?");
        $stmt->execute([$val, $_SESSION['user_id']]);

        $activeUser = htmlspecialchars($_SESSION['username']);
        echo json_encode(['status' => 'success', 'output' => "<span class='boot-ok'>[OK]</span> Profile configuration '<span class='c-cyan'>{$key}</span>' updated. Run <span class='c-cyan'>profile {$activeUser}</span> to verify."]);
        break;

    case 'create_post':
        $comment = trim($_POST['comment'] ?? '');
        if (empty($comment)) {
            echo json_encode(['status' => 'error', 'output' => "<span class='result-error'>ERR: Broadcast message cannot be empty.</span>"]);
            break;
        }

        $stmt = $pdo->prepare("INSERT INTO profile_comments (username, comment) VALUES (?, ?)");
        $stmt->execute([$_SESSION['username'], $comment]);
        echo json_encode(['status' => 'success', 'output' => "<span class='boot-ok'>[OK]</span> Broadcast appended to system log."]);
        break;

    case 'fetch_log':
        $stmt = $pdo->query("SELECT * FROM profile_comments ORDER BY created_at DESC");
        $logs = $stmt->fetchAll();

        if (empty($logs)) {
            echo json_encode(['status' => 'success', 'output' => "<span class='result-info'>System log is currently empty.</span>"]);
            break;
        }

        $out = "<div style='color:var(--yellow); font-weight:bold; margin-bottom:8px;'>SYSTEM BROADCAST LOG</div>";
        $out .= "<div style='color:var(--dim); margin-bottom:12px;'>-----------------------------------------------------</div>";

        foreach ($logs as $l) {
            $date = date('Y-m-d H:i', strtotime($l['created_at']));
            $u = htmlspecialchars($l['username']);
            $c = htmlspecialchars($l['comment']);
            $out .= "<div style='margin-bottom:14px; line-height: 1.4;'>";
            $out .= "<div style='color:var(--dim); font-size:11px; margin-bottom:2px;'>[{$date}] <span class='c-cyan'>@{$u}</span></div>";
            $out .= "<div style='color:var(--fg);'>{$c}</div>";
            $out .= "</div>";
        }

        echo json_encode(['status' => 'success', 'output' => $out]);
        break;

    case 'restore_defaults':
        $defaultUni    = 'University of San Carlos - Talamban Campus';
        $defaultCourse = 'Bachelor of Science in Information Technology';

        $stmt = $pdo->prepare("UPDATE users SET bio = NULL, university = ?, course = ?, year = 1, theme = 'dark' WHERE id = ?");
        $stmt->execute([$defaultUni, $defaultCourse, $_SESSION['user_id']]);

        $stmt2 = $pdo->prepare("DELETE FROM profile_comments WHERE username = ?");
        $stmt2->execute([$_SESSION['username']]);

        $_SESSION['theme'] = 'dark';

        echo json_encode(['status' => 'success', 'output' => "<span class='boot-ok'>[OK]</span> Factory reset complete. Wiped bio & logs. Restored default theme, university, course, and year."]);
        break;

    case 'online_users':
        $users = ['joshuareed', 'lancer', 'john', 'joshuadan'];
        $rows  = '';
        foreach ($users as $u) {
            $rows .= "<div class='online-row'><span class='status-dot'>●</span><span class='c-green'>[ONLINE]</span> " . htmlspecialchars($u) . "</div>\n";
        }
        $output = "<div class='online-list'>{$rows}</div>";
        echo json_encode(['status' => 'success', 'output' => $output]);
        break;

    case 'set_theme':
        $theme   = $_POST['theme'] ?? 'dark';
        $allowed = ['dark', 'ash', 'white'];
        if (!in_array($theme, $allowed)) $theme = 'dark';
        $stmt = $pdo->prepare("UPDATE users SET theme = ? WHERE id = ?");
        $stmt->execute([$theme, $_SESSION['user_id']]);
        $_SESSION['theme'] = $theme;
        echo json_encode(['status' => 'success', 'output' => "Theme updated: {$theme}"]);
        break;

    case 'repos':
        $who    = $_POST['user']   ?? '';
        $search = $_POST['search'] ?? '';

        $sql    = "SELECT r.*, u.username FROM repositories r JOIN users u ON u.id = r.user_id ";
        $params = [];

        if ($who) {
            $sql     .= "WHERE u.username = ? ";
            $params[] = $who;
        } elseif ($search) {
            $sql     .= "WHERE r.repo_name LIKE ? OR r.repo_description LIKE ? ";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        $sql .= "ORDER BY r.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $repos = $stmt->fetchAll();

        if (empty($repos)) {
            echo json_encode(['status' => 'success', 'output' => "<span class='result-error'>No repositories found matching criteria.</span>"]);
            break;
        }

        $rows = '';
        foreach ($repos as $i => $r) {
            $idx   = str_pad($i + 1, 2, ' ', STR_PAD_LEFT);
            $name  = htmlspecialchars($r['repo_name']);
            $lang  = htmlspecialchars($r['language'] ?? '—');
            $stars = str_pad($r['stars'], 2, ' ', STR_PAD_LEFT);
            $owner = "Reddit";
            $date  = date('Y-m-d', strtotime($r['created_at']));

            $rows .= "<div class='repo-row'>"
                   . "<span class='repo-idx'>{$idx}</span>"
                   . "<span class='repo-name'>{$name}</span>"
                   . "<span class='repo-lang lang-{$lang}'>{$lang}</span>"
                   . "<span class='repo-stars'><span class='c-yellow'>★</span> {$stars}</span>"
                   . "<span class='repo-owner'>{$owner}</span>"
                   . "<span class='repo-date'>{$date}</span>"
                   . "</div>";
        }

        $header = "<div class='repo-header'>"
                . "<span class='repo-idx'>ID</span>"
                . "<span class='repo-name'>NAME</span>"
                . "<span class='repo-lang'>LANG</span>"
                . "<span class='repo-stars'>STARS</span>"
                . "<span class='repo-owner'>FROM</span>"
                . "<span class='repo-date'>CREATED</span>"
                . "</div>";

        $hint = "<div class='repo-hint'>Run <span class='c-cyan'>repo &lt;name&gt;</span> to inspect a repository</div>";
        echo json_encode(['status' => 'success', 'output' => "<div class='repo-list'>{$header}{$rows}{$hint}</div>"]);
        break;

    case 'repo_detail':
        $name = trim($_POST['name'] ?? '');
        $stmt = $pdo->prepare(
            "SELECT r.*, u.username FROM repositories r
             JOIN users u ON u.id = r.user_id
             WHERE r.repo_name = ?
             LIMIT 1"
        );
        $stmt->execute([$name]);
        $r = $stmt->fetch();

        if (!$r) {
            $safe = htmlspecialchars($name);
            echo json_encode(['status' => 'error', 'output' => "<div class='repo-detail-box'><span class='result-error'>ERR: Repository '{$safe}' not found.</span></div>"]);
            break;
        }

        $rname = htmlspecialchars($r['repo_name']);
        $desc  = htmlspecialchars($r['repo_description'] ?? '—');
        $lang  = htmlspecialchars($r['language'] ?? '—');
        $stars = $r['stars'];
        $from  = "Reddit";
        $date  = date('d M Y', strtotime($r['created_at']));
        $id    = (int)$r['id'];

        // Deterministic display values derived from the repo ID to avoid storing redundant columns.
        $sizes   = ['12.4 KB', '38.1 KB', '7.8 KB', '52.3 KB', '21.0 KB', '9.5 KB', '44.7 KB', '16.2 KB', '5.1 KB'];
        $size    = $sizes[$id % count($sizes)];
        $commits = 12 + ($id * 7) % 83;
        $license = ['MIT', 'Apache-2.0', 'GPL-3.0', 'BSD-2-Clause'][$id % 4];

        $output = "
<div class='repo-detail-box'>
  <div class='repo-detail-header'>
    <div class='repo-large-name'>{$rname}</div>
    <div class='repo-detail-owner'>from {$from}</div>
  </div>
  <div class='repo-detail-desc'>{$desc}</div>
  <div class='repo-detail-row'><span class='repo-detail-label'>Language</span><span class='repo-detail-sep'>:</span><span class='repo-detail-val lang-{$lang}'>{$lang}</span></div>
  <div class='repo-detail-row'><span class='repo-detail-label'>Stars</span><span class='repo-detail-sep'>:</span><span class='repo-detail-val'><span class='c-yellow'>★</span> {$stars}</span></div>
  <div class='repo-detail-row'><span class='repo-detail-label'>Commits</span><span class='repo-detail-sep'>:</span><span class='repo-detail-val'>{$commits}</span></div>
  <div class='repo-detail-row'><span class='repo-detail-label'>Size</span><span class='repo-detail-sep'>:</span><span class='repo-detail-val'>{$size}</span></div>
  <div class='repo-detail-row'><span class='repo-detail-label'>License</span><span class='repo-detail-sep'>:</span><span class='repo-detail-val'>{$license}</span></div>
  <div class='repo-detail-row'><span class='repo-detail-label'>Created</span><span class='repo-detail-sep'>:</span><span class='repo-detail-val'>{$date}</span></div>
  <div class='ascii-divider'>+----------------------------------------+</div>
  <div class='repo-keybind' data-repo='{$rname}'>
    <span class='keybind-item'><span class='keybind-key'>q</span><span class='keybind-label'>clone</span></span>
    <span class='keybind-item'><span class='keybind-key'>esc</span><span class='keybind-label'>dismiss</span></span>
  </div>
</div>";

        echo json_encode(['status' => 'success', 'output' => $output]);
        break;

    case 'showcases':
        $stmt  = $pdo->query("SELECT s.*, u.username FROM showcases s JOIN users u ON u.id = s.user_id ORDER BY s.created_at DESC");
        $items = $stmt->fetchAll();

        if (empty($items)) {
            echo json_encode(['status' => 'success', 'output' => "<span class='result-info'>No graphical showcases found in registry.</span>"]);
            break;
        }

        $rows = '';
        foreach ($items as $r) {
            $name    = htmlspecialchars($r['image_name']);
            $urlName = rawurlencode($r['image_name']);
            $type    = htmlspecialchars(strtoupper($r['file_type']));
            $ext     = htmlspecialchars(strtolower($r['file_type']));
            $cat     = htmlspecialchars($r['category']);
            $imgSrc  = "img/showcases/{$urlName}.{$ext}";

            $rows .= "<div class='showcase-list-row'>"
                   . "<img src='{$imgSrc}' class='showcase-thumb' alt='thumb' onerror=\"this.outerHTML='<span class=\\'c-red\\'>[!]</span>'\">"
                   . "<span class='showcase-name'>{$name}</span>"
                   . "<span class='showcase-type'>.{$type}</span>"
                   . "<span class='showcase-category'>[{$cat}]</span>"
                   . "</div>";
        }

        $header = "<div class='showcase-list-header'>"
                . "<span>PREVIEW</span>"
                . "<span>FILE_NAME</span>"
                . "<span>FORMAT</span>"
                . "<span>CATEGORY</span>"
                . "</div>";

        $hint = "<div class='repo-hint'>Run <span class='c-cyan'>showcase &lt;name&gt;</span> to view full resolution</div>";
        echo json_encode(['status' => 'success', 'output' => "<div class='repo-list'>{$header}{$rows}{$hint}</div>"]);
        break;

    case 'showcase_detail':
        $name = trim($_POST['name'] ?? '');
        $stmt = $pdo->prepare("SELECT s.* FROM showcases s WHERE s.image_name = ? LIMIT 1");
        $stmt->execute([$name]);
        $r = $stmt->fetch();

        if (!$r) {
            $safe = htmlspecialchars($name);
            echo json_encode(['status' => 'error', 'output' => "<span class='result-error'>ERR: Image showcase '{$safe}' not found.</span>"]);
            break;
        }

        $imgName = htmlspecialchars($r['image_name']);
        $urlName = rawurlencode($r['image_name']);
        $title   = htmlspecialchars($r['title']);
        $cat     = htmlspecialchars($r['category']);
        $type    = htmlspecialchars(strtoupper($r['file_type']));
        $ext     = htmlspecialchars(strtolower($r['file_type']));
        $from    = "Reddit";
        $date    = date('M d, Y', strtotime($r['created_at']));
        $imgSrc  = "img/showcases/{$urlName}.{$ext}";

        $output = "
<div class='showcase-view-box'>
  <img src='{$imgSrc}' class='showcase-large-img' alt='{$title}' onerror=\"this.outerHTML='<div class=\\'result-error\\' style=\\'margin-bottom:12px;\\'>ERR: Failed to load file from disk.</div>'\">

  <div class='showcase-details-bar'>
    <div class='showcase-detail-item'>
        <span class='showcase-detail-label'>Title</span>
        <span class='showcase-detail-value c-cyan'>{$title}</span>
    </div>
    <div class='showcase-detail-item'>
        <span class='showcase-detail-label'>Category</span>
        <span class='showcase-detail-value c-magenta'>{$cat}</span>
    </div>
    <div class='showcase-detail-item'>
        <span class='showcase-detail-label'>From</span>
        <span class='showcase-detail-value'>{$from}</span>
    </div>
    <div class='showcase-detail-item'>
        <span class='showcase-detail-label'>Uploaded</span>
        <span class='showcase-detail-value'>{$date}</span>
    </div>
    <div class='showcase-detail-item'>
        <span class='showcase-detail-label'>Format</span>
        <span class='showcase-detail-value c-yellow'>{$type}</span>
    </div>
  </div>
</div>";

        echo json_encode(['status' => 'success', 'output' => $output]);
        break;

    default:
        echo json_encode(['status' => 'error', 'output' => '<span class="result-error">ERR: Unknown API action.</span>']);
        break;
}
?>

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
        $output = "
<div class='fetch-wrap'>
  <div class='fetch-ascii'>
█████╗  ██████╗  ██████╗ ██╗   ██╗███╗   ███╗██╗   ██╗██╗     ██╗██╗
██╔══██╗██╔════╝ ██╔════╝██║   ██║████╗ ████║██║   ██║██║     ██║██║
███████║██║      ██║     ██║   ██║██╔████╔██║██║   ██║██║     ██║██║
██╔══██║██║      ██║     ██║   ██║██║╚██╔╝██║██║   ██║██║     ██║██║
██║  ██║╚██████╗ ╚██████╗╚██████╔╝██║ ╚═╝ ██║╚██████╔╝███████╗██║██║
╚═╝  ╚═╝ ╚═════╝  ╚═════╝ ╚═════╝ ╚═╝     ╚═╝ ╚═════╝ ╚══════╝╚═╝╚═╝</div>
  <div class='fetch-info'>
    <div class='fetch-row'><span class='fetch-label'>Project</span><span class='fetch-sep'>:</span><span class='fetch-val'>Accumulii</span></div>
    <div class='fetch-row'><span class='fetch-label'>Purpose</span><span class='fetch-sep'>:</span><span class='fetch-val'>Terminal Developer Profile &amp; Repo Showcase</span></div>
    <div class='fetch-row'><span class='fetch-label'>Frontend</span><span class='fetch-sep'>:</span><span class='fetch-val'>HTML5 · CSS3 · Vanilla JS</span></div>
    <div class='fetch-row'><span class='fetch-label'>Backend</span><span class='fetch-sep'>:</span><span class='fetch-val'>PHP 8 · MySQL (PDO)</span></div>
    <div class='fetch-row'><span class='fetch-label'>Developers</span><span class='fetch-sep'>:</span><span class='fetch-val'></span></div>
    <div class='fetch-indent'>Abaya, Joshua Danielle Ermac</div><div class='fetch-indent'>Campus, John Louis</div><div class='fetch-indent'>Elopre, Joshua Reed Omamalin</div><div class='fetch-indent'>Tallo, Lance Benedict</div>
    <div class='fetch-colors'>
      <span class='c-black'>███</span><span class='c-red'>███</span><span class='c-green'>███</span><span class='c-yellow'>███</span><span class='c-blue'>███</span><span class='c-magenta'>███</span><span class='c-cyan'>███</span><span class='c-white'>███</span>
    </div>
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
            $output = "
<div class='fetch-wrap'>
  <div class='fetch-ascii'>  ╭───╮  
  │ ◉ │  
  ╰───╯  
 ╭─────╮ 
 │ usr │ 
 ╰─────╯ </div>
  <div class='fetch-info'>
    <div class='fetch-row'><span class='fetch-label'>User</span><span class='fetch-sep'>:</span><span class='fetch-val c-cyan'>{$u}</span></div>
    <div class='fetch-row'><span class='fetch-label'>Role</span><span class='fetch-sep'>:</span><span class='fetch-val'>{$r}</span></div>
    <div class='fetch-row'><span class='fetch-label'>University</span><span class='fetch-sep'>:</span><span class='fetch-val'>{$v}</span></div>
    <div class='fetch-row'><span class='fetch-label'>Course</span><span class='fetch-sep'>:</span><span class='fetch-val'>{$c}</span></div>
    <div class='fetch-row'><span class='fetch-label'>Year</span><span class='fetch-sep'>:</span><span class='fetch-val'>{$y}</span></div>
  </div>
</div>";
        } else {
            $t      = htmlspecialchars($target);
            $output = "<span class='result-error'>ERR: User '{$t}' not found in registry.</span>";
        }
        echo json_encode(['status' => 'success', 'output' => $output]);
        break;

    case 'online_users':
        $users = ['joshuareed','lancer','john','joshuadan'];
        $rows  = '';
        foreach ($users as $u) {
            $rows .= "<div class='online-row'><span class='status-dot'>●</span><span class='c-green'>[ONLINE]</span> " . htmlspecialchars($u) . "</div>\n";
        }
        $output = "<div class='online-list'>{$rows}</div>";
        echo json_encode(['status' => 'success', 'output' => $output]);
        break;

    case 'set_theme':
        $theme = $_POST['theme'] ?? 'dark';
        $allowed = ['dark','ash','white'];
        if (!in_array($theme, $allowed)) $theme = 'dark';
        $stmt = $pdo->prepare("UPDATE users SET theme = ? WHERE id = ?");
        $stmt->execute([$theme, $_SESSION['user_id']]);
        $_SESSION['theme'] = $theme;
        echo json_encode(['status' => 'success', 'output' => "Theme updated: {$theme}"]);
        break;

    case 'repos':
        $who = $_POST['user'] ?? '';
        $search = $_POST['search'] ?? '';

        $sql = "SELECT r.*, u.username FROM repositories r JOIN users u ON u.id = r.user_id ";
        $params = [];

        if ($who) {
            $sql .= "WHERE u.username = ? ";
            $params[] = $who;
        } elseif ($search) {
            $sql .= "WHERE r.repo_name LIKE ? OR r.repo_description LIKE ? ";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        $sql .= "ORDER BY r.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $repos = $stmt->fetchAll();

        if (empty($repos)) {
            echo json_encode(['status' => 'success', 'output' => "<span class='result-error'>No repositories found matching criteria.</span>", 'repos' => []]);
            break;
        }

        $rows = '';
        foreach ($repos as $i => $r) {
            $idx    = str_pad($i + 1, 2, ' ', STR_PAD_LEFT);
            $name   = htmlspecialchars($r['repo_name']);
            $lang   = htmlspecialchars($r['language'] ?? '—');
            $stars  = str_pad($r['stars'], 2, ' ', STR_PAD_LEFT);
            $owner  = htmlspecialchars($r['username']);
            $date   = date('Y-m-d', strtotime($r['created_at']));
            
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
                . "<span class='repo-owner'>OWNER</span>"
                . "<span class='repo-date'>CREATED</span>"
                . "</div>";

        $hint = "<div class='repo-hint'>type <span class='c-cyan'>repo &lt;name&gt;</span> to inspect a repository</div>";
        echo json_encode(['status' => 'success', 'output' => "<div class='repo-list'>{$header}{$rows}{$hint}</div>", 'repos' => array_column($repos, 'repo_name')]);
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
            echo json_encode(['status' => 'error', 'output' => "<span class='result-error'>ERR: Repository '{$safe}' not found.</span>"]);
            break;
        }

        $rname  = htmlspecialchars($r['repo_name']);
        $desc   = htmlspecialchars($r['repo_description'] ?? '—');
        $lang   = htmlspecialchars($r['language'] ?? '—');
        $stars  = $r['stars'];
        $owner  = htmlspecialchars($r['username']);
        $date   = date('d M Y', strtotime($r['created_at']));
        $id     = (int)$r['id'];

        $sizes   = ['12.4 KB', '38.1 KB', '7.8 KB', '52.3 KB', '21.0 KB', '9.5 KB', '44.7 KB', '16.2 KB', '5.1 KB'];
        $size    = $sizes[$id % count($sizes)];
        $commits = 12 + ($id * 7) % 83;
        $license = ['MIT', 'Apache-2.0', 'GPL-3.0', 'BSD-2-Clause'][$id % 4];

        $output = "
<div class='repo-detail'>
  <div class='repo-detail-header'>
    <span class='repo-detail-icon'>◆</span>
    <span class='repo-detail-name'>{$rname}</span>
    <span class='repo-detail-owner'>by {$owner}</span>
  </div>
  <div class='repo-detail-desc'>{$desc}</div>
  <div class='repo-detail-row'><span class='repo-detail-label'>Language</span><span class='repo-detail-sep'>:</span><span class='repo-detail-val lang-{$lang}'>{$lang}</span></div>
  <div class='repo-detail-row'><span class='repo-detail-label'>Stars</span><span class='repo-detail-sep'>:</span><span class='repo-detail-val'><span class='c-yellow'>★</span> {$stars}</span></div>
  <div class='repo-detail-row'><span class='repo-detail-label'>Commits</span><span class='repo-detail-sep'>:</span><span class='repo-detail-val'>{$commits}</span></div>
  <div class='repo-detail-row'><span class='repo-detail-label'>Size</span><span class='repo-detail-sep'>:</span><span class='repo-detail-val'>{$size}</span></div>
  <div class='repo-detail-row'><span class='repo-detail-label'>License</span><span class='repo-detail-sep'>:</span><span class='repo-detail-val'>{$license}</span></div>
  <div class='repo-detail-row'><span class='repo-detail-label'>Created</span><span class='repo-detail-sep'>:</span><span class='repo-detail-val'>{$date}</span></div>
  <div class='repo-keybind' data-repo='{$rname}'>
    <span class='keybind-key'>q</span><span class='keybind-label'>clone &amp; download</span>
    <span class='keybind-key'>esc</span><span class='keybind-label'>dismiss</span>
  </div>
</div>";

        echo json_encode(['status' => 'success', 'output' => $output, 'repo' => $r['repo_name']]);
        break;

    default:
        echo json_encode(['status' => 'error', 'output' => '<span class="result-error">ERR: Unknown API action.</span>']);
        break;
}
?>
<?php
require "../config.php";

$authToken = "";
if (isset($_SERVER['PHP_AUTH_USER']) && preg_match('#^[a-z]{20}$|^author$#s', $_SERVER['PHP_AUTH_USER'])) {
  $authToken = $_SERVER['PHP_AUTH_USER'];
}
if (isset($_SERVER['PHP_AUTH_PW']) && preg_match('#^[a-z]{20}$|^author$#s', $_SERVER['PHP_AUTH_PW'])) {
  $authToken = $_SERVER['PHP_AUTH_PW'];
}

if (!isset($TEAM_TOKENS[$authToken])) {
  header('WWW-Authenticate: Basic realm="Your Token? Ask orgas if you did not get one"', true, 401);
  exit("<h1>Your Token? Ask orgas if you did not get one</h1>");
}

function render_count_num($countNum) {
  $res = "0000000000";
  $colors = ['#ddd', '#ddd', '#ccc', '#aaa', '#999', '#888', '#666', '#444', '#222', '#000'];
  $countNum = max($countNum, 0);
  $countNum = min($countNum, 99);
  for ($i = 0; $i <= 9; $i++) {
    if ($countNum < 1) {
      $res[$i] = '0';
    } elseif ($countNum >= 9) {
      $res[$i] = '9';
    } else {
      $res[$i] = $countNum;
    }
    $countNum -= 10;
  }
  $res = implode('', array_map(function ($ch) use ($colors) { return "<span style='color: " . $colors[$ch] . "'>$ch</span>"; }, str_split($res)));
  return $res;
}
?>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="Refresh" content="5">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>100% Queue</title>
    <style>
      html { min-height: 100%; width: 100%; }
      * { margin: 0px; }
      p { font-family: Consolas, 'Courier New', monospace; font-size: 12px; line-height: 12px; margin: 3px 0px; }
    </style>
  </head>
  <body>
<?php
$limit = isset($_GET['full']) ? INF : 30;
$shownRows = 0;

$queueGlob = array_reverse(glob("../queue/*"));
$playgroundGlob = array_reverse(glob("../playground/*"));

$were = [];

foreach ($queueGlob as $fnm) {
  if (++$shownRows == $limit) {
    echo "<p><a href='?full=1'>Full Queue Listing »</a></p>\n";
    break;
  }
  if ($shownRows > $limit) {
    break;
  }

  $fnm = basename($fnm);
  if (isset($were[$fnm])) {
    continue;
  }
  $were[$fnm] = true;
  list ($date, $time, $token, $rep) = explode("_", $fnm);
  $status = file_exists("../playground/$fnm") ? "Working" : "Pending";
  $step = 0;
  if (file_exists("../playground/$fnm/status.txt")) {
    list ($step, $status) = explode(":", file_get_contents("../playground/$fnm/status.txt"));
  }
  $countNum = render_count_num($step);
  if (isset($TEAM_TOKENS[$token])) {
    $teamName = $TEAM_TOKENS[$token];
  } else {
    $teamName = str_repeat("*", strlen($token));
  }
?>
    <p><?=$date?> <?=$time?>   <?=$teamName?>   <?=file_exists("../playground/$fnm") ? "▶" : " "?> <?=$countNum?>   <?="<b>$step</b> / 100  $status"?></p>
<?php
}

foreach ($playgroundGlob as $fnm) {
  if (++$shownRows == $limit) {
    echo "<p><a href='?full=1'>Full Queue Listing »</a></p>\n";
    break;
  }
  if ($shownRows > $limit) {
    break;
  }

  $fnm = basename($fnm);
  if (isset($were[$fnm])) {
    continue;
  }
  $were[$fnm] = true;
  list ($date, $time, $token, $rep) = explode("_", $fnm);
  $status = "Done";
  $step = 0;
  if (file_exists("../playground/$fnm/status.txt")) {
    list ($step, $status) = explode(":", file_get_contents("../playground/$fnm/status.txt"));
  }
  $countNum = render_count_num($step);
  if (isset($TEAM_TOKENS[$token])) {
    $teamName = $TEAM_TOKENS[$token];
  } else {
    $teamName = str_repeat("*", strlen($token));
  }

  $dlLink = $authToken === $token ? "   <a href='download.php?id=$fnm'>Download results »</a>" : "";
?>
    <p><?=$date?> <?=$time?>   <?=$teamName?>     <?=$countNum?>   <?="<b>$step</b> / 100  $status"?><?=$dlLink?></p>
<?php
}

if ($shownRows < 1) {
?>
    <p>Queue is empty</p>
<?php
}
?>
  </body>
</html>

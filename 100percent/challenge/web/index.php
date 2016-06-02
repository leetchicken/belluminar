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

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>100%</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <style>
      .head-100p { background-image: url(../img/bg.jpg); background-position: center; color: #fff; }
      .head-100p img { margin: 20px 0px; }
      img { max-width: 100%; }
      p { font-size: 17px; }
      iframe { border: 0px; min-height: 200px; }
      .newsection { margin-top: 10px; }
      .slogans h2 { margin-top: 10%; }
      .slogans p { font-style: italic; }
      .alert { padding: 8px 16px; margin-top: 16px; }
    </style>
  </head>
  <body>
    <!-- fuck yeeah, ilyahov -->
    <div class="container-fluid head-100p">
      <div class="container">
        <div class="row">
          <div class="col-xs-6 col-md-4 col-md-offset-2"><img src="img/100p.png" /></div>
          <div class="slogans hidden-xs col-sm-6">
            <h2>Putting the R into ASLR</h2>
            <p>We provide 100% unpwnable mitigation.<br/>Can pwn it 100% of time? Have that flag.</p>
          </div>
          <div class="slogans visible-xs col-xs-6">
            <h2>Putting R into ASLR</h2>
            <p>100% reliable exploit? Have that flag.</p>
          </div>
        </div>
      </div>
    </div>
    <div class="container">
      <div class="row newsection">
        <div class="col-xs-12 text-center"><h2>100% Pwn2Flag Scoreboard</h2></div>
      </div>
      <div class="row">
        <div class="col-xs-12 col-sm-10 col-sm-offset-1 col-md-8 col-md-offset-2">
          <table class="table table-condensed table-striped text-center">
            <thead><tr><th>#</th><th>Team</th><th>Attempts</th><th>Last attempt</th><th>Max Result</th></tr></thead>
<?php
$scoreboard = unserialize(file_get_contents("../data/scoreboard.txt"));
if (!is_array($scoreboard)) {
  $scoreboard = [];
  foreach ($TEAM_TOKENS as $token => $nul) {
    $scoreboard[$token] = [$token == "author" ? -1 : 0, 0, 0];
  }
  file_put_contents("../data/scoreboard.txt", serialize($scoreboard));
}

uasort($scoreboard, function ($a, $b) { return $b[0] != $a[0] ? $b[0] - $a[0] : $a[2] - $b[2]; });
$pos = 1;
foreach ($scoreboard as $token => $info) {
  list ($score, $attempts, $lastAttempt) = $info;
  $lastAttempt = $lastAttempt ? date('r', $lastAttempt) : "—";
?>
            <tr><td><?=$pos > 3 ? $pos++ : "<b>" . $pos++ . "</b>"?></b></td><td><?=$token == $authToken ? "<b>" . $TEAM_TOKENS[$token] . "</b>" : $TEAM_TOKENS[$token]?></td><?=$score == -1 ? "<td colspan=3>Not Eligible</td>" : "<td>$attempts</td><td>$lastAttempt</td><td><b>$score</b> / 100</td>"?></tr>
<?php
}
?>
          </table>
        </div>
      </div>

      <div class="row newsection">
        <div class="col-xs-12 text-center"><h2>Your Submission</h2></div>
      </div>
      <div class="row text-center">
        <div class="col-xs-12 col-sm-10 col-sm-offset-1 col-md-8 col-md-offset-2">
          <form class="form-inline" method="POST" enctype="multipart/form-data">
            <label>Upload your Input File</label>  <label class="btn btn-default btn-file">Browse...<input type="file" name="input" style="display: none" required="required" /></label>   <input class="form-control btn btn-primary" type="submit" value="Submit »" />
          </form>
        </div>
      </div>
<?php
if (isset($_FILES['input'])) {
  $status = false;

  do {
    $f = fopen("../data/lock-$authToken", "c+");
    if (! $f) {
      $status = [false, "Can't acquire lock. Ask orgas"];
      break;
    }

    do {
      if (!flock($f, LOCK_EX | LOCK_NB)) {
        $status = [false, "Can't acquire lock. Ask orgas"];
        break;
      }

      $inputData = file_get_contents($_FILES['input']['tmp_name']);
      if (strlen($inputData) < 1) {
        $status = [false, "Empty input"];
        break;
      }
      if (strlen($inputData) > 65536) {
        $status = [false, "Haha, anyone can pwn with > 64K.<br/>Fit your input in 64KB"];
        break;
      }

      $oldQueueId = stream_get_contents($f);
      rewind($f);

      if ($oldQueueId && file_exists("../queue/$oldQueueId")) {
        $status = [false, "Your already have a pending submission.<br/><small>If it hangs for too long, ask orgas</small>"];
        break;
      }

      $queueId = date('Y-m-d_H-i-s') . "_$authToken" . "_";
      for ($i = "aaa"; file_exists("../queue/$queueId$i"); $i++) { }
      $queueId .= $i;

      if (!mkdir("../queue/$queueId")) {
        $status = [false, "Can't create dir. Ask orgas"];
        break;
      }

      if (!file_put_contents("../queue/$queueId/input.txt", $inputData)) {
        $status = [false, "Can't create input file. Ask orgas"];
        break;
      }

      fwrite($f, $queueId);
      ftruncate($f, ftell($f));

      $status = [true, "Your submission will be processed shortly.<br/><small>If it doesn't happen, ask orgas</small>"];
    } while (false);

    fclose($f);
  } while (false);

  if ($status) {
?>
      <div class="row text-center"><div class="alert col-xs-10 col-xs-offset-1 col-sm-8 col-sm-offset-2 col-md-4 col-md-offset-4 <?=$status[0] ? "bg-success" : "bg-danger"?>"><?=$status[1]?></div></div>
<?php
  }
}
?>

      <div class="row newsection">
        <div class="col-xs-12 text-center"><h2>Queue</h2></div>
      </div>
      <div class="row">
        <div class="col-xs-12"><iframe class="col-xs-12 col-sm-10 col-sm-offset-1 col-md-8 col-md-offset-2" src="queue.php"></iframe></div>
      </div>
    </div>
  </body>
</html>

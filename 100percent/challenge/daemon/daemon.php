<?php
require "../config.php";

function update_status($sess, $i, $status) {
  file_put_contents("../playground/$sess/status.txt", "$i:$status");
  file_put_contents("../status/$sess/log.txt", date('r') . "  ($i / 100) $status\n", FILE_APPEND);
  echo date('r') . "  $sess - $i / 100 - $status\n";
}

function set_scoreboard($authToken, $score, $addAttempt = false) {
  global $TEAM_TOKENS;

  $scoreboard = unserialize(file_get_contents("../data/scoreboard.txt"));
  if (!is_array($scoreboard)) {
    $scoreboard = [];
    foreach ($TEAM_TOKENS as $token => $nul) {
      $scoreboard[$token] = [$token == "author" ? -1 : 0, 0, 0];
    }
  }
  if ($scoreboard[$authToken][0] != -1) {
    $scoreboard[$authToken][0] = max($score, $scoreboard[$authToken][0]);
  }
  if ($addAttempt) {
    $scoreboard[$authToken][1]++;
  }
  $scoreboard[$authToken][2] = time();
  file_put_contents("../data/scoreboard.txt", serialize($scoreboard));
}

while (true) {
  $g = glob("../queue/*");
  if (empty($g)) {
    echo '.';
    sleep(1);
    continue;
  }

  $sess = basename(array_shift($g));
  echo "\n" . date('r') . "  processing $sess\n";

  list (, , $authToken) = explode("_", $sess);

  if (!is_dir("../playground/$sess")) {
    if (!file_exists("../queue/$sess/input.txt")) {
      echo "Waiting for input.txt";
      while (!file_exists("../queue/$sess/input.txt")) {
        usleep(200000);
        echo '.';
      }
      echo "\n";
    }

    mkdir("../playground/$sess");
    mkdir("../status/$sess");
    link("stub/Adobe.Reader.Dependencies.manifest", "../status/$sess/Adobe.Reader.Dependencies.manifest");
    link("stub/msvcp100.dll", "../status/$sess/msvcp100.dll");
    link("stub/msvcr100.dll", "../status/$sess/msvcr100.dll");

    $beginStep = 0;
    update_status($sess, 0, "Starting");
    copy("../queue/$sess/input.txt", "../status/$sess/input.txt");
    set_scoreboard($authToken, 0, true);
  } else {
    list ($beginStep) = explode(":", file_get_contents("../playground/$sess/status.txt"));
    update_status($sess, $beginStep, "Resuming");
  }

  $input = file_get_contents("../queue/$sess/input.txt");

  for ($i = $beginStep; $i < 100; $i++) {
    update_status($sess, $i, "Building binary");
    set_scoreboard($authToken, $i);
    mkdir("../playground/$sess/$i");

    chdir("linker");
    $objs = glob("..\\stub\\*.o");
    shuffle($objs);
    $out = [];
    $ret = false;
    exec("link /OUT:..\\..\\playground\\$sess\\$i\\killit.exe /INCREMENTAL:NO /SUBSYSTEM:CONSOLE /OPT:REF /OPT:ICF /LTCG /DYNAMICBASE /NXCOMPAT /MACHINE:X86 msvcrt.lib ws2_32.lib " . implode(" ", $objs), $out, $ret);
    chdir("..");

    if (!file_exists("../playground/$sess/$i/killit.exe")) {
      update_status($sess, $i, "Build error, contact orgas if happens again");
      break;
    }

    $adobeDllNum = mt_rand(1, 7);
    link("stub/AcroRd32_$adobeDllNum.dll", "../playground/$sess/$i/AcroRd32.dll");
    link("stub/Adobe.Reader.Dependencies.manifest", "../playground/$sess/$i/Adobe.Reader.Dependencies.manifest");
    link("stub/msvcp100.dll", "../playground/$sess/$i/msvcp100.dll");
    link("stub/msvcr100.dll", "../playground/$sess/$i/msvcr100.dll");
    @unlink("../status/$sess/AcroRd32.dll");
    link("../playground/$sess/$i/AcroRd32.dll", "../status/$sess/AcroRd32.dll");
    @unlink("../status/$sess/binary.exe");
    link("../playground/$sess/$i/killit.exe", "../status/$sess/binary.exe");
    system("icacls ..\\playground\\$sess\\$i\\*.dll /grant \"*S-1-15-2-1:(R,RX)\" >nul");
    system("icacls ..\\playground\\$sess\\$i\\Adobe.Reader.Dependencies.manifest /grant \"*S-1-15-2-1:(R,RX)\" >nul");

    $token = md5(microtime() . mt_rand() . "97abea13bc13fff186d5ff3119030041");

    if (!file_put_contents("../playground/$sess/$i/token.txt", $token)) {
      update_status($sess, $i, "Token error, contact orgas if happens again");
      break;
    }

    for ($repeats = 0; $repeats < 5; $repeats++) {
      update_status($sess, $i, "Running binary");

      $desc = [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']];
      $pipes = [];
      $proc = proc_open("runner\\AppJailLauncher.exe /key:..\\playground\\$sess\\$i\\token.txt /port:2345 ..\\playground\\$sess\\$i\\killit.exe", $desc, $pipes, NULL, NULL, ['bypass_shell' => true]);

      $everythingOk = true;
      do {
        for ($tries = 1; $tries <= 10; $tries++) {
          $s = fsockopen("127.0.0.1", 2345, $nul, $nul, 2);
          if (! $s) {
            usleep($tries * 100000);
            continue;
          }
          stream_set_timeout($s, 3);
          $banner = fgets($s);
          if (trim($banner) != "hello") {
            fclose($s);
            $s = false;
            usleep($tries * 100000);
            continue;
          }
          break;
        }
        if (! $s) {
          update_status($sess, $i, "Can't connect to socket, contact orgas if happens again");
          $everythingOk = false;
          break;
        }

        fwrite($s, $input);

        $buf = "";
        $startTime = microtime(true);
        while (!feof($s)) {
          if (microtime(true) - $startTime > 10) {
            break;
          }
          $buf .= fgetc($s);
        }
        file_put_contents("../playground/$sess/$i/output.txt", $buf);
        if (!strstr($buf, $token)) {
          update_status($sess, $i, "Nope, didn't have .\\token.txt contents ('$token') in stdout");
          $everythingOk = false;
          break;
        }
      } while (false);

      proc_terminate($proc);
      proc_close($proc);

      system("taskkill /f /im killit.exe /im AppJailLauncher.exe 2>nul");
      system("runner\\AppJailLauncher.exe /uninstall /key:..\\playground\\$sess\\$i\\token.txt ..\\playground\\$sess\\$i\\killit.exe >nul");

      echo "\n";

      if ($everythingOk) {
        break;
      }
    }

    if (! $everythingOk) {
      update_status($sess, $i, "Nope, you didn't pwn this build");
      break;
    }

    if ($i == 25 || $i == 75) {
      update_status($sess, $i + 1, "Step $i finished -> rebooting to shake up kernel32 ASLR!");
      system("shutdown /r /t 0");
      exit;
    }
  }

  set_scoreboard($authToken, $i);
  if ($i == 100) {
    update_status($sess, $i, "Congratz, got flag ;)");
    file_put_contents("../status/$sess/flag.txt", $FLAG);
  }

  echo "Deleting queue/$sess\n\n";
  system("rd /s /q ..\\queue\\$sess");
}

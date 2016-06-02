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

if (!isset($_GET['id'])) {
  exit("No id. Ask orgas if you were not fuzzing");
}

$id = $_GET['id'];
list ($date, $time, $token, $seq) = explode("_", $id, 4);
if (!preg_match('#^\d\d\d\d-\d\d-\d\d$#s', $date)) {
  exit("Wrong date. Ask orgas if you were not fuzzing");
}
if (!preg_match('#^\d\d-\d\d-\d\d$#s', $time)) {
  exit("Wrong time. Ask orgas if you were not fuzzing");
}
if (!preg_match('#^[a-z]{20}$|^author$#s', $token)) {
  exit("Wrong token. Ask orgas if you were not fuzzing");
}
if (!preg_match('#^[a-z]{3}$#s', $seq)) {
  exit("Wrong seq. Ask orgas if you were not fuzzing");
}

if ($token !== $authToken) {
  exit("Trying to download other's results. Ask orgas if you were not fuzzing");
}

if (!file_exists("../status/$id")) {
  exit("Trying to download unexistent results. Ask orgas if you were not fuzzing");
}

if (file_exists("../queue/$id")) {
  exit("Trying to download pending results. Ask orgas if you were not fuzzing");
}

if (!file_exists("archives/$id.tar.gz")) {
  shell_exec("archives\\tar czf archives/$id.tar.gz ../status/$id");
  if (!file_exists("archives/$id.tar.gz")) {
    exit("Couldn't create the archive for $id. Ask orgas if you were not fuzzing");
  }
}

header("Location: archives/$id.tar.gz", true, 302);

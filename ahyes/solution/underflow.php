<?php
function dfgets($s) {
  $str = fgets($s);
  fwrite(STDERR, "<<< $str");
  return $str;
}

function dfread($s, $len) {
  $buf = "";
  for ($i = 0; $i < $len; $i++) {
    $buf .= $ch = fgetc($s);
    fwrite(STDERR, $ch);
  }
  return $buf;
}

function dfwrite($s, $str) {
  fwrite(STDERR, ">>> $str");
  return fwrite($s, $str);
}

function d_read_until($s, $what) {
  $buf = "";
  while (substr($buf, -strlen($what)) !== $what) {
    $buf .= $ch = fgetc($s);
    fwrite(STDERR, $ch);
  }
  return $buf;
}

$s = fsockopen("192.168.192.136", 10120);
d_read_until($s, "> ");

dfwrite($s, "128\n");
d_read_until($s, "> ");
dfwrite($s, "flag\n");
$flag = d_read_until($s, "\n");
d_read_until($s, "> ");


dfwrite($s, "128\n");
d_read_until($s, "> ");

$plaintext = md5(microtime() . mt_rand()) . md5(microtime() . mt_rand()) . md5(microtime() . mt_rand()) . md5(microtime() . mt_rand())
           . md5(microtime() . mt_rand()) . md5(microtime() . mt_rand()) . md5(microtime() . mt_rand()) . md5(microtime() . mt_rand())
           . md5(microtime() . mt_rand()) . md5(microtime() . mt_rand()) . md5(microtime() . mt_rand()) . md5(microtime() . mt_rand())
           . md5(microtime() . mt_rand()) . md5(microtime() . mt_rand()) . md5(microtime() . mt_rand()) . md5(microtime() . mt_rand())
           . md5(microtime() . mt_rand()) . md5(microtime() . mt_rand()) . md5(microtime() . mt_rand()) . md5(microtime() . mt_rand())
           . md5(microtime() . mt_rand()) . md5(microtime() . mt_rand()) . md5(microtime() . mt_rand()) . md5(microtime() . mt_rand());

dfwrite($s, "put\n");
d_read_until($s, "Message: ");
dfwrite($s, "$plaintext\n");
d_read_until($s, "Encrypted: ");
$ciph10 = d_read_until($s, "\n");
d_read_until($s, "> ");

dfwrite($s, "192\n");
d_read_until($s, "> ");

dfwrite($s, "put\n");
d_read_until($s, "Message: ");
stream_socket_shutdown($s, STREAM_SHUT_WR);
d_read_until($s, "Encrypted: ");
$ciph12 = d_read_until($s, "\n");

file_put_contents("flag.txt", hex2bin(strtr(trim($flag), "AHYES!", "abcdef")));
file_put_contents("plaintext.txt", $plaintext);
file_put_contents("ciph10.txt", hex2bin(strtr(trim($ciph10), "AHYES!", "abcdef")));
file_put_contents("ciph12.txt", hex2bin(strtr(trim($ciph12), "AHYES!", "abcdef")));

<?php
$FLAG = "CTF{wh0a_H3ll!_AppJailLaunch3r_L00k5_l1KE_t3h_M0s7_buGgy_p1ec3_0f_s0ftw4re_ev4h_Gl4d_i_f1nalLY_h4nd3d_0ver_th1s_chaLL}";

$TEAM_TOKENS = [
  'cyhkzslhdancvjidpmny' => '!SpamAndHex',
  'ddvjhmapzsnvdhzjyqwp' => 'Blue-Lotus',
  'jplhlztukykdywemggvu' => 'CyKor',
  'vcrffvoswasherehbxge' => 'DCUA',
  'lrqodwmlvxciuvkodpci' => 'DragonSector',
  'azsmcnamgriebsbqxetx' => 'GoN',
  'xfohtqnchbmbdhjdyadu' => 'HITCON',
  'sntiqivpdlnydtxydjjs' => 'KeyResolve',
  'author' => 'More Smoked Leet Chicken',
  'besxfdeafpgihqsljffl' => '0ops',
];

/* unused, use to regenerate if you want */
function generate_team_token() {
  return implode("", array_map(function () { return "qwertyuiopasdfghjklzxcvbnm"[mt_rand(0, 25)]; }, range(1, 20)));
}

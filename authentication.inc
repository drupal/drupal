<?

if ((isset($aid)) && (isset($pwd)) && ($op == "login")) {
  $admin = base64_encode("$aid:$pwd");
  setcookie("admin","$admin",time()+2592000); // 1 mo is 2592000
}

if (isset($admin)) {
  $admin = base64_decode($admin);
  $admin = explode(":", $admin);
  $aid = "$admin[0]";
  $pwd = "$admin[1]";
  dbconnect();
  if (mysql_num_rows(mysql_query("SELECT * FROM authors WHERE aid = '$aid' AND pwd = '$pwd'")) == 1) $admin = 1;
  else $admin = 0;
} else {
  $admin = 0;
}

?>
<?php

$timer = 0;

function timer_print() {
  global $timer;
  $stop = explode(" ", microtime());
  $diff = $stop[0] - $timer[0];
  print "<PRE>execution time: $diff ms</PRE>";
}

function timer_start() {
  global $timer;
  $timer = explode(" ", microtime());
}

?>
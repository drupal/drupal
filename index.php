<?php

include_once "includes/common.inc";

if (variable_get(dev_timing, 0)) timer_start();

$result = db_query("SELECT nid FROM node WHERE promote = '1' AND status = '$status[posted]' AND timestamp <= ". ($date > 0 ? $date : time()) ." ". ($category ? "AND cid = '$category'" : "") ." ". ($topic ? "AND tid = '$topic'" : "") ."  ORDER BY timestamp DESC LIMIT ". ($user->nodes ? $user->nodes : variable_get(default_nodes_main, 10)));

$theme->header();
while ($node = db_fetch_object($result)) {
  node_view(node_get_object("nid", $node->nid), 1);
}
$theme->footer();

if (variable_get(dev_timing, 0)) timer_print();

?>

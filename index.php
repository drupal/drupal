<?php

include_once "includes/common.inc";

page_header();

$result = db_query("SELECT nid, type FROM node WHERE ". ($meta ? "attributes LIKE '%". check_input($meta) ."%' AND " : "") ." promote = '1' AND status = '". node_status("posted") ."' AND timestamp <= '". ($date > 0 ? check_input($date) : time()) ."' ORDER BY timestamp DESC LIMIT ". ($user->nodes ? $user->nodes : variable_get(default_nodes_main, 10)));

$theme->header();
while ($node = db_fetch_object($result)) {
  node_view(node_get_object(array("nid" => $node->nid, "type" => $node->type)), 1);
}
$theme->footer();

page_footer();

?>

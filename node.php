<?php

include "includes/common.inc";

function node_update($node) {
}

function node_history($node) {
}

function node_refers($node) {
}

$node = ($title ? node_get_object(title, check_input($title)) : node_get_object(nid, check_input($id)));

if ($node && node_visible($node)) {
  switch ($op) {
    case "update":
//      node_update($node);
//      break;
    case "history":
//      node_history($node);
//      break;
    case "referers":
//      node_referers($node);
//      break;
    default:
      node_view($node);
  }
}
else {
  $theme->header();
  $theme->box(t("Warning: not found"), t("The content or data you requested does not exist or is not accessible."));
  $theme->footer();
}

?>
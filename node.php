<?php

include_once "includes/common.inc";

page_header();

function node_render($node) {
  global $id, $cid, $op, $moderate, $pid, $subject, $comment, $theme, $mode, $order, $threshold, $PHP_SELF;

  if ($node->comment) {
    switch($op) {
      case t("Preview comment"):
        $theme->header();
        comment_preview(check_input($pid), check_input($id), $subject, $comment);
        $theme->footer();
        break;
      case t("Post comment"):
        comment_post(check_input($pid), check_input($id), check_input($subject), check_input($comment));
        $theme->header();
        node_view($node);
        comment_render($id, $cid);
        $theme->footer();
        break;
      case t("Add comment"):
        $theme->header();
        comment_reply(check_input($cid), check_input($id));
        $theme->footer();
        break;
      case "reply":
        $theme->header();
        comment_reply(check_input($pid), check_input($id));
        $theme->footer();
        break;
      case t("Update settings"):
        comment_settings(check_input($mode), check_input($order), check_input($threshold));
        $theme->header();
        node_view($node);
        comment_render($id, $cid);
        $theme->footer();
        break;
      case t("Moderate comments"):
        comment_moderate($moderate);
        $theme->header();
        node_view($node);
        comment_render($id, $cid);
        $theme->footer();
        break;
      default:
        $theme->header();
        node_view($node);
        comment_render($id, $cid);
        $theme->footer();
    }
  }
  else {
    $theme->header();
    node_view($node);
    $theme->footer();
  }
}

function node_failure() {
  global $theme;
  $theme->header();
  $theme->box(t("Not found"), t("The node you are looking for does not exist yet or is no longer accessible:") ."<UL><LI><A HREF=\"search.php\">". t("search node") ."</A></LI><LI><A HREF=\"submit.php\">". t("add node") ."</A></LI></UL>\n");
  $theme->footer();
}

function node_history($node) {
  global $status;
  if ($node->status == $status[expired] || $node->status == $status[posted]) {
    $output .= "<DT><B>". format_date($node->timestamp) ." by ". format_username($node->userid) .":</B></DT><DD>". check_output($node->log, 1) ."<P></DD>";
  }
  if ($node->pid) {
    $output .= node_history(node_get_object(array("nid" => $node->pid)));
  }
  return $output;
}

$number = ($title ? db_result(db_query("SELECT COUNT(nid) FROM node WHERE title = '$title' AND status = $status[posted]")) : 1);

if ($number > 1) {
  $result = db_query("SELECT n.*, u.userid FROM node n LEFT JOIN users u ON n.author = u.id WHERE n.title = '$title'");

  while ($node = db_fetch_object($result)) {
    if (node_visible($node)) {
      $output .= "<P><B><A HREF=\"node.php?id=$node->nid\">". check_output($node->title) ."</A></B><BR><SMALL>$node->type - ". format_username($node->userid) ." - ". format_date($node->timestamp, "small") ."</SMALL></P>";
    }
  }

  $theme->header();
  $theme->box(t("Result"), $output);
  $theme->footer();
}
elseif ($number) {
  $node = ($title ? node_get_object(array("title" => check_input($title))) : node_get_object(nid, check_input($id)));
  if ($node && node_visible($node)) {
    switch ($op) {
      case "history":
        $theme->header();
        $theme->box(t("History"), node_control($node) ."<DL>". node_history($node) ."</DL>");
        $theme->footer();
        break;
      default:
        user_rehash();
        node_render($node);
    }
  }
  else {
    node_failure();
  }
}
else {
  node_failure();
}

page_footer();

?>
<?php

function theme_init() {
  global $user, $themes;

  if ($user->theme && file_exists($themes[$user->theme][0])) {
    include_once $themes[$user->theme][0];
  }
  else {
    include_once $themes[variable_get(theme_default, key($themes))][0];
  }
  return new Theme();
}

function theme_link($separator = " | ") {
  global $repository;
  $links = array("<A HREF=\"index.php\">". t("home") ."</A>",
                 "<A HREF=\"search.php\">". t("search") ."</A>",
                 "<A HREF=\"submit.php\">". t("submit") ."</A>");
  if (is_array($repository[diary]))
    $links[] = "<A HREF=\"module.php?mod=diary\">". t("diary") ."</A>";
  $links[] = "<A HREF=\"account.php\">". t("account") ."</A>";
  if (is_array($repository[diary]))
    $links[] = "<A HREF=\"module.php?mod=book\">". t("handbook") ."</A>";
  return implode($separator, $links);
}

function theme_menu($name, $module) {
  global $menu;
  if ($module["menu"]) $menu = ($menu) ? array_merge($menu, $module["menu"]()) : $module["menu"]();
}

function theme_account($theme) {
  global $user, $links, $menu;

  if ($user->id) {


    module_iterate("theme_menu");

    // Display account settings:
    $content .= "<LI><A HREF=\"account.php?op=track&topic=comments\">". t("track your comments") ."</A></LI>\n";
    $content .= "<LI><A HREF=\"account.php?op=track&topic=nodes\">". t("track your nodes") ."</A></LI>\n";
    $content .= "<LI><A HREF=\"account.php?op=track&topic=site\">". strtr(t("track %a"), array("%a" => variable_get(site_name, "drupal"))) ."</A></LI>\n";
    $content .= "<P>\n";
    $content .= "<LI><A HREF=\"account.php?op=edit&topic=user\">". t("edit your information") ."</A></LI>\n";
    $content .= "<LI><A HREF=\"account.php?op=edit&topic=site\">". t("edit your preferences") ."</A></LI>\n";
    $content .= "<LI><A HREF=\"account.php?op=edit&topic=content\">". t("edit your content") ."</A></LI>\n";
    $content .= "<P>\n";

    if (user_access($user)) {
      $content .= "<LI><A HREF=\"admin.php\">administer ". variable_get(site_name, "drupal") ."</A></LI>\n";
      $content .= "<P>\n";
    }

    if ($menu) {
      foreach ($menu as $link) $content .= "<LI>$link</LI>\n";
      $content .= "<P>\n";
    }

    $content .= "<LI><A HREF=\"account.php?op=logout\">". t("logout") ."</A></LI>\n";

    $theme->box(strtr(t("%a's configuration"), array("%a" => $user->userid)), "$content");
  }
  else {
    $output .= "<DIV ALIGN=\"center\">\n";
    $output .= " <FORM ACTION=\"account.php?op=login\" METHOD=\"post\">\n";
    $output .= "  <B>". t("Username") .":</B><BR><INPUT NAME=\"userid\" SIZE=\"15\"><P>\n";
    $output .= "  <B>". t("Password") .":</B><BR><INPUT NAME=\"passwd\" SIZE=\"15\" TYPE=\"password\"><BR>\n";
    $output .= "  <INPUT TYPE=\"submit\" VALUE=\"". t("Login") ."\"><BR>\n";
    $output .= "  <A HREF=\"account.php\">". t("REGISTER") ."</A>\n";
    $output .= " </FORM>\n";
    $output .= "</DIV>\n";

    $theme->box(t("Login"), $output);
  }
}


function theme_blocks($region, $theme) {
  global $id, $PHP_SELF, $status, $user;

  switch (strrchr($PHP_SELF, "/")) {
    case "/node.php":
      if ($region != "left") {
        if ($user->id) $node = db_fetch_object(db_query("SELECT * FROM node WHERE nid = '$id'"));
        if ($node->status == $status[queued]) theme_moderation_results($theme, $node);
        // else theme_new_headlines($theme);
      }
      break;
    case "/index.php":
      if ($user->id) $result = db_query("SELECT * FROM blocks b LEFT JOIN layout l ON b.name = l.block WHERE (b.status = 2 OR (b.status = 1 AND l.user = '$user->id'))". (($region == "left" || $region == "right") ? ($region == "left" ? " AND b.region = 0" : " AND b.region = 1") : "") ." ORDER BY weight");
      else $result = db_query("SELECT * FROM blocks WHERE status = 2". (($region == "left" || $region == "right") ? ($region == "left" ? " AND region = 0" : " AND region = 1") : "") ." ORDER BY weight");
      while ($block = db_fetch_object($result)) {
        $blocks = module_execute($block->module, "block");
        $theme->box(t($blocks[$block->offset]["subject"]), $blocks[$block->offset]["content"]);
      }
      break;
  }
}

function theme_morelink($theme, $node) {
  return ($node->body) ? "[ <A HREF=\"node.php?id=$node->nid\"><FONT COLOR=\"$theme->link\">". t("read more") ."</FONT></A> | ". sizeof(explode(" ", $node->body)) ." ". t("words") ." | <A HREF=\"node.php?id=$node->nid\"><FONT COLOR=\"$theme->link\">". format_plural(node_get_comments($node->nid), "comment", "comments") ."</FONT></A> ]" : "[ <A HREF=\"node.php?id=$node->nid\"><FONT COLOR=\"$theme->link\">". format_plural(node_get_comments($node->nid), "comment", "comments") ."</FONT></A> ]";
}

function theme_moderation_results($theme, $node) {
  global $user;

  if ($user->id && $node->nid && ($user->id == $node->author || user_get($user, "history", "n$node->nid"))) {
    $result = db_query("SELECT * FROM users WHERE history LIKE '%n$node->nid%'");
    while ($account = db_fetch_object($result)) {
      $output .= format_username($account->userid) ." voted '". user_get($account, "history", "n$node->nid") ."'.<BR>";
    }

    $theme->box(t("Moderation results"), ($output ? $output : t("This node has not been moderated yet.")));
  }
}

/*
//
// depricated -> new block strategy
//
function theme_new_headlines($theme, $num = 10) {
  $result = db_query("SELECT nid, title FROM node WHERE status = 2 AND type = 'story' ORDER BY nid DESC LIMIT $num");
  while ($node = db_fetch_object($result)) $content .= "<LI><A HREF=\"node.php?id=$node->nid\">". check_output($node->title) ."</A></LI>\n";
  $theme->box(t("Latest headlines"), $content);
}

function theme_old_headlines($theme, $num = 10) {
  global $user;

  $result = db_query("SELECT id, subject, timestamp FROM story WHERE status = 2 ORDER BY timestamp DESC LIMIT ". ($user->nodes ? $user->nodes : $num) .", $num");
  while ($node = db_fetch_object($result)) {
    if ($time != date("F jS", $node->timestamp)) {
      $content .= "<P><B>". date("l, M jS", $node->timestamp) ."</B></P>\n";
      $time = date("F jS", $node->timestamp);
    }
    $content .= "<LI><A HREF=\"node.php?id=$node->nid\">". check_output($node->title) ."</A></LI>\n";
  }
  $theme->box(t("Older headlines"), $content);
}
*/

?>

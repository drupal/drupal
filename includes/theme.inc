<?php

class BaseTheme {
  function links(&$links, $state = 0) {
    // $state = 0 : default behaviour
    // $state = 1 : main page
    // $state = 2 : theme_link()
    // Subject to change coz its messy
    foreach ($links as $link) {
      if ($link[0]) $_links[] = count($link) == 2 ? "<A HREF=\"$link[0]\"><FONT COLOR=\"$theme->link\">". t($link[1]) ."</FONT></A>" : t($link[0]);
    }

    return ($_links ? implode(" | ", $_links) : "");
  }
}

function theme_init() {
  global $user, $themes;

  if ($user->theme && file_exists($themes[$user->theme][0])) {
    include_once $themes[$user->theme][0];
  }
  else {
    include_once $themes[variable_get("theme_default", key($themes))][0];
  }
  return new Theme();
}

function theme_link() {
  global $user, $theme;
  $links[] = array("index.php",  t("home"));
  $links[] = array("search.php",  t("search"));
  $links[] = array("submit.php",  t("submit"));
  $links[] = array("account.php",  t("account"));

  foreach (module_list() as $name) {
    if (module_hook($name, "page")) $links[] = array("module.php?mod=$name", t($name));
  }

  return $theme->links($links, 2);
}


function theme_account($theme) {
  global $user;

  if ($user->id) {
    // Display account settings:
    $content .= "<A HREF=\"account.php?op=track&topic=comments\">". t("track your comments") ."</A><BR>\n";
    $content .= "<A HREF=\"account.php?op=track&topic=nodes\">". t("track your nodes") ."</A><BR>\n";
    $content .= "<A HREF=\"account.php?op=track&topic=site\">". strtr(t("track %a"), array("%a" => variable_get("site_name", "drupal"))) ."</A><BR>\n";
    $content .= "<P>\n";
    $content .= "<A HREF=\"account.php?op=edit&topic=user\">". t("edit your information") ."</A><BR>\n";
    $content .= "<A HREF=\"account.php?op=edit&topic=site\">". t("edit your preferences") ."</A><BR>\n";
    $content .= "<A HREF=\"account.php?op=edit&topic=content\">". t("edit your content") ."</A><BR>\n";
    $content .= "<P>\n";

    if (user_access($user, "access administration pages")) {
      $content .= "<A HREF=\"admin.php\">". strtr(t("administer %a"), array("%a" => variable_get("site_name", "drupal"))) ."</A><BR>\n";
      $content .= "<P>\n";
    }

    foreach (module_list() as $name) {
      if ($links = module_invoke($name, "menu")) {
        foreach ($links as $link) $content .= "$link<BR>\n";
      }
    }
    if ($link) $content .= "<P>\n";

    $content .= "<A HREF=\"account.php?op=logout\">". t("logout") ."</A><BR>\n";

    $theme->box(strtr(t("%a's configuration"), array("%a" => $user->userid)), "$content");
  }
  else {
    $output .= "<DIV ALIGN=\"center\">\n";
    $output .= " <FORM ACTION=\"account.php?op=login\" METHOD=\"post\">\n";
    $output .= "  <B>". t("Username") .":</B><BR><INPUT NAME=\"userid\" SIZE=\"15\"><P>\n";
    $output .= "  <B>". t("Password") .":</B><BR><INPUT NAME=\"passwd\" SIZE=\"15\" TYPE=\"password\"><BR>\n";
    $output .= "  <INPUT TYPE=\"submit\" VALUE=\"". t("Login") ."\"><BR>\n";
    if (variable_get("account_register", 1)) $output .= "  <A HREF=\"account.php\">". t("REGISTER") ."</A>\n";
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
        $blocks = module_invoke($block->module, "block");
        $theme->box(t($blocks[$block->offset]["subject"]), $blocks[$block->offset]["content"]);
      }
      break;
  }
}

function theme_moderation_results($theme, $node) {
  foreach (explode(",", $node->users) as $vote) {
    if ($vote) {
      $data = explode("=", $vote);
      $output .= format_username($data[0]) ." voted '$data[1]'.<BR>";
    }
  }

  $theme->box(t("Moderation results"), ($output ? $output : t("This node has not been moderated yet.")));
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

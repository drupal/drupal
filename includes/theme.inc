<?php

class BaseTheme {
  function links($links, $delimiter = " | ") {
    return implode($delimiter, $links);
  }

  function images($name) {
    return $name;
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

function theme_account($region, $theme) {
  global $user;

  if ($user->id) {
    // Display account settings:
    $content .= "<table><tr><td nowrap=\"nowrap\">\n";
    $content .= "<a href=\"account.php?op=edit&type=information\">". t("your information") ."</a><BR>\n";
    $content .= "<a href=\"account.php?op=edit&type=settings\">". t("your settings") ."</a><BR>\n";
    $content .= "<a href=\"account.php?op=edit&type=blocks\">". t("your blocks") ."</a><BR>\n";
    $content .= "<a href=\"account.php?op=view&type=comments\">". t("your comments") ."</a><BR>\n";
    $content .= "<a href=\"account.php?op=view&type=contributions\">". t("your contributions") ."</a><BR>\n";
    $content .= "<a href=\"account.php?op=view&type=site\">". strtr(t("your %a"), array("%a" => variable_get("site_name", "drupal"))) ."</a><BR>\n";
    $content .= "<p />\n";

    if (user_access("access administration pages")) {
      $content .= "<a href=\"admin.php\">". strtr(t("administer %a"), array("%a" => variable_get("site_name", "drupal"))) ."</a><BR>\n";
      $content .= "<p />\n";
    }

    foreach (module_list() as $name) {
      if (module_hook($name, "link")) {
        $links = module_invoke($name, "link", "menu");
        foreach ($links as $link) $content .= "$link<br />\n";
      }
    }
    if ($link) $content .= "<p />\n";

    $content .= "<a href=\"account.php?op=logout\">". t("logout") ."</a>\n";
    $content .= "</td></tr></table>\n";

    $theme->box($user->userid, $content, $region);
  }
  else {
    $output .= "<div align=\"center\">\n";
    $output .= " <form action=\"account.php?op=login\" method=\"post\">\n";
    $output .= "  <b>". t("Username") .":</b><br /><input name=\"userid\" size=\"15\"><p />\n";
    $output .= "  <b>". t("Password") .":</b><br /><input name=\"passwd\" size=\"15\" TYPE=\"password\"><br />\n";
    $output .= "  <input type=\"submit\" value=\"". t("Login") ."\"><br />\n";
    if (variable_get("account_register", 1)) $output .= "  <a href=\"account.php\">". t("REGISTER") ."</a>\n";
    $output .= " </form>\n";
    $output .= "</div>\n";

    $theme->box(t("Login"), $output, $region);
  }
}


function theme_blocks($region, $theme) {
  global $id, $PHP_SELF, $status, $user;

  switch (strrchr($PHP_SELF, "/")) {
    case "/node.php":
      if ($region != "left") {
        if ($user->id) $node = db_fetch_object(db_query("SELECT * FROM node WHERE nid = '$id'"));
        if ($node->status == $status[queued]) theme_moderation_results($theme, $node, $region);
      }
      break;
    case "/index.php":
      if ($user->id) $result = db_query("SELECT * FROM blocks b LEFT JOIN layout l ON b.name = l.block WHERE (b.status = 2 OR (b.status = 1 AND l.user = '$user->id'))". (($region == "left" || $region == "right") ? ($region == "left" ? " AND b.region = 0" : " AND b.region = 1") : "") ." ORDER BY weight");
      else $result = db_query("SELECT * FROM blocks WHERE status = 2". (($region == "left" || $region == "right") ? ($region == "left" ? " AND region = 0" : " AND region = 1") : "") ." ORDER BY weight");
      while ($block = db_fetch_object($result)) {
        $blocks = module_invoke($block->module, "block");
        $theme->box(t($blocks[$block->offset]["subject"]), $blocks[$block->offset]["content"], $region);
      }
      break;
  }
}

function theme_moderation_results($theme, $node, $region) {
  foreach (explode(",", $node->users) as $vote) {
    if ($vote) {
      $data = explode("=", $vote);
      $output .= format_username($data[0]) ." voted '$data[1]'.<br />";
    }
  }

  $theme->box(t("Moderation results"), ($output ? $output : t("This node has not been moderated yet.")), $region);
}

?>

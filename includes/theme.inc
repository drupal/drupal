<?php

class BaseTheme {
  function links($links, $delimiter = " | ") {
    return implode($delimiter, $links);
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

    if (user_access("access administration pages")) {
      $content .= "<A HREF=\"admin.php\">". strtr(t("administer %a"), array("%a" => variable_get("site_name", "drupal"))) ."</A><BR>\n";
      $content .= "<P>\n";
    }

    foreach (module_list() as $name) {
      if (module_hook($name, "link")) {
        $links = module_invoke($name, "link", "menu");
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

?>

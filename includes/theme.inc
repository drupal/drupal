<?php

class BaseTheme {
  function links($links, $delimiter = " | ") {
    return implode($delimiter, $links);
  }

  function image($name) {
    return "misc/$name";
  }

  function comment_controls($threshold = 1, $mode = 3, $order = 1) {
    global $REQUEST_URI, $user;
    $output .= "<DIV ALIGN=\"CENTER\">\n";
    $output .= "<FORM METHOD=\"post\" ACTION=\"$REQUEST_URI\">\n";
    $output .= comment_mode(($user->uid ? $user->mode : $mode));
    $output .= comment_order(($user->uid ? $user->sort : $order));
    $output .= comment_threshold(($user->uid ? $user->threshold : $threshold));
    $output .= "<INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"". t("Update settings") ."\">\n";
    $output .= "<INPUT TYPE=\"submit\" NAME=\"op\" VALUE=\"". t("Add comment") ."\">\n";
    $output .= "</FORM>\n";
    $output .= "</DIV>\n";
    return $output;
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

  if ($user->uid) {
    // Display account settings:
    $content .= "<table><tr><td nowrap=\"nowrap\">\n";

    foreach (module_list() as $name) {
      if (module_hook($name, "link")) {
        $links = module_invoke($name, "link", "menu");
        foreach ($links as $link) $content .= "$link<br />\n";
      }
    }

    if (user_access("access administration pages")) {
      $content .= "<p />\n";
      $content .= "<a href=\"admin.php\">". strtr(t("administer %a"), array("%a" => variable_get("site_name", "drupal"))) ."</a><BR>\n";
    }

    $content .= "</td></tr></table>\n";

    $theme->box($user->name, $content, $region);
  }
  else {
    $output .= "<div align=\"center\">\n";
    $output .= " <form action=\"module.php?mod=user&op=login\" method=\"post\">\n";
    $output .= "  <b>". t("Username") .":</b><br /><input name=\"edit[name]\" size=\"15\"><p />\n";
    $output .= "  <b>". t("Password") .":</b><br /><input name=\"edit[pass]\" size=\"15\" TYPE=\"password\"><br />\n";
    $output .= "  <input type=\"submit\" value=\"". t("Log in") ."\"><br />\n";
    if (variable_get("account_register", 1)) $output .= "  <a href=\"module.php?mod=user\">". t("REGISTER") ."</a>\n";
    $output .= " </form>\n";
    $output .= "</div>\n";

    $theme->box(t("Log in"), $output, $region);
  }

}

function theme_blocks($region, $theme) {
  global $id, $PHP_SELF, $status, $user;

  switch (strrchr($PHP_SELF, "/")) {
    case "/node.php":
      if ($region != "left") {
        if ($user->uid) $node = db_fetch_object(db_query("SELECT * FROM node WHERE nid = '$id'"));
        if ($node->status == $status[queued]) theme_moderation_results($theme, $node, $region);
      }
      break;
    case "/index.php":
      if ($user->uid) $result = db_query("SELECT * FROM blocks b LEFT JOIN layout l ON b.name = l.block WHERE (b.status = 2 OR (b.status = 1 AND l.user = '$user->uid'))". (($region == "left" || $region == "right") ? ($region == "left" ? " AND b.region = 0" : " AND b.region = 1") : "") ." ORDER BY weight");
      else $result = db_query("SELECT * FROM blocks WHERE status = 2". (($region == "left" || $region == "right") ? ($region == "left" ? " AND region = 0" : " AND region = 1") : "") ." ORDER BY weight");
      while ($block = db_fetch_object($result)) {
        $blocks = module_invoke($block->module, "block");
        if ($blocks[$block->offset]["content"])
          $theme->box(t($blocks[$block->offset]["subject"]), $blocks[$block->offset]["content"], $region);
      }
      break;
  }
}

function theme_moderation_results($theme, $node, $region) {
  foreach (explode(",", $node->users) as $vote) {
    if ($vote) {
      $data = explode("=", $vote);
      $account = user_load(array("uid" => $data[0]));
      $output .= format_name($account) ." voted '$data[1]'.<br />";
    }
  }

  $theme->box(t("Moderation results"), ($output ? $output : t("This node has not been moderated yet.")), $region);
}

?>

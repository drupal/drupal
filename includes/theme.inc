<?php
// $Id$

class BaseTheme {

  function header($title = "") {
    $output .= "<html><head><title>". variable_get(site_name, "drupal") ."</title></head><body>";
    $output .= "<table border=\"0\" cellspacing=\"4\" cellpadding=\"4\"><tr><td valign=\"top\" width=\"170\">";

    print $output;
    $this->box(t("Navigation"), implode("<br />", link_page())); $this->user("all", $this); theme_blocks("all", $this);
    print "</td><td valgin=\"top\">";

  }

  function links($links, $delimiter = " | ") {
    return implode($delimiter, $links);
  }

  function image($name) {
    return "misc/$name";
  }

  function user($region) {
    global $user;

    if ($user->uid) {
      // Display account settings:

      $output .= "<div width=\"125\">\n";

      foreach (module_list() as $name) {
        if (module_hook($name, "link")) {
          $links = module_invoke($name, "link", "menu");
          foreach ($links as $link) {
            $output .= "$link<br />\n";
          }
        }
      }

      if (user_access("access administration pages")) {
        $output .= "<br />\n";
        $output .= "<a href=\"admin.php\">". strtr(t("administer %a"), array("%a" => variable_get("site_name", "drupal"))) ."</a><BR>\n";
      }

      $output .= "</div>";

      $this->box($user->name, $output, $region);
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

      $this->box(t("Log in"), $output, $region);
    }
  }

  function node($node, $main) {
    $output .= "<b>". check_output($node->title) ."</b> by ". format_name($node) ."<br />";
    if ($main && $node->teaser) {
      $output .= strip_tags(check_output($node->teaser, 1));
    }
    else {
      $output .= check_output($node->body, 1);
    }
    if ($links = link_node($node, $main)) {
      $output .= "<br />[ ". $this->links($links) ." ]";
    }
    $output .= "<hr />";

    print $output;
  }


  function comment_controls($threshold = 1, $mode = 3, $order = 1) {
    return form_item(t("Comment viewing options"), comment_mode($mode) . comment_order($order) . comment_threshold($threshold) ." <input type=\"submit\" name=\"op\" value=\"". t("Update settings") ."\" />", t("Select your prefered way to display the comments and click 'Update settings' to active your changes."));
  }

  function comment($comment, $link = 0) {
    $output .= "<a name=\"$comment->cid\"></a>";
    $output .= "<div style=\"border: 1px solid; padding: 10px;\">";
    $output .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">";
    $output .= " <tr><td><div style=\"font-size: 110%; font-weight: bold;\">". check_output($comment->subject) ."</div></td><td align=\"right\" rowspan=\"2\" valign=\"top\">". comment_moderation($comment) ."</td></tr>";
    $output .= " <tr><td><div style=\"margin-left: 10px; padding-bottom: 10px; font-size: 90%;\">". sprintf(t("by %s on %s"), format_name($comment), format_date($comment->timestamp)) ."</div></td></tr>";
    $output .= " <tr><td colspan=\"2\">". check_output($comment->comment, 1) ."</td></tr>";
    $output .= " <tr><td align=\"right\" colspan=\"2\">$link</td></tr>";
    $output .= "</table>";
    $output .= "</div><br />";
    print $output;
  }

  function box($subject, $content, $region = "main") {
    $output .= "<b>". check_output($subject) ."</b><br />". check_output($content) ."<p />";
    print $output;
  }

  function footer() {
    $output .= "</td></tr></table>";
    $output .= "</body></html>";
    print $output;
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

function theme_blocks($region, &$theme) {
  global $id, $PHP_SELF, $REQUEST_URI, $user;

  switch (strrchr($PHP_SELF, "/")) {
    case "/node.php":
      if ($region != "left") {
        if ($user->uid) $node = db_fetch_object(db_query("SELECT * FROM node WHERE nid = '$id'"));
        if ($node->moderate == 1) theme_moderation_results($theme, $node, $region);
      }
    default:
      if ($user->uid) $result = db_query("SELECT * FROM blocks b LEFT JOIN layout l ON b.name = l.block WHERE (b.status = 2 OR (b.status = 1 AND l.uid = '$user->uid'))". (($region == "left" OR $region == "right") ? ($region == "left" ? " AND b.region = 0" : " AND b.region = 1") : "") ." AND (b.path = '' OR '". strrchr($REQUEST_URI, "/") ."' RLIKE b.path) ORDER BY weight");
      else $result = db_query("SELECT * FROM blocks WHERE status = 2". (($region == "left" OR $region == "right") ? ($region == "left" ? " AND region = 0" : " AND region = 1") : "") ." ORDER BY weight");
      while ($result && ($block = db_fetch_object($result))) {
        $blocks = module_invoke($block->module, "block");
        if ($blocks[$block->delta]["content"])
          $theme->box(t($blocks[$block->delta]["subject"]), $blocks[$block->delta]["content"], $region);
      }
      break;
  }
}

function theme_moderation_results(&$theme, $node, $region) {
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

<?php

/**
* Basic theme
*
* @package theme system
*/
class BaseTheme {
  var $background = "#ffffff";
  var $foreground = "#000000";

  function system($field) {
    $system["name"] = "Basic theme";
    $system["author"] = "Drupal";
    $system["description"] = "Basic theme. Lynx friendly";

    return $system[$field];
  }

  function header($title = "") {
    $output .= "<html><head><title>". variable_get(site_name, "drupal") ."</title></head><body bgcolor=\"$this->background\" text=\"$this->foreground\">";
    $output .= "<table border=\"0\" cellspacing=\"4\" cellpadding=\"4\"><tr><td valign=\"top\" width=\"170\">";

    print $output;
    $this->box(t("Navigation"), @implode("<br />", link_page())); theme_blocks("all", $this);
    print "</td><td valign=\"top\">";

  }

  function links($links, $delimiter = " | ") {
    return implode($delimiter, $links);
  }

  function image($name) {
    return "misc/$name";
  }

  function node($node, $main) {
    if (function_exists("taxonomy_node_get_terms")) {
      foreach (taxonomy_node_get_terms($node->nid) as $term) {
        $terms[] = l($term->name, array("or" => $term->tid), "index");
      }
    }

    $output = "<b>". check_output($node->title) ."</b> by ". format_name($node) ."<br />";
    if (count($terms)) {
      $output .= "<small>(". $this->links($terms) .")</small><br />";
    }
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
    $output .= " <tr><td><div style=\"margin-left: 10px; padding-bottom: 10px; font-size: 90%;\">". t("by %a on %b", array("%a" => format_name($comment), "%b" => format_date($comment->timestamp))) ."</div></td></tr>";
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

function theme_list() {
  static $list;

  if (!$list) {
    $list = array();
    $result = db_query("SELECT * FROM system where type = 'theme' AND status = '1' ORDER BY name");
    while ($theme = db_fetch_object($result)) {
      if (file_exists($theme->filename)) {
        $list[$theme->name] = $theme;
      }
    }
  }

  return $list;
}

function theme_init() {
  global $user;

  $themes = theme_list();
  $name = $user->theme ? $user->theme : variable_get("theme_default", 0);
  if (is_object($themes[$name])) {
    include_once($themes[$name]->filename);
    $theme_class = "Theme_$name";
    @$obj =& new $theme_class;
    return $obj;
  }

  watchdog("warning", "No valid themes enabled.");
  @$obj =& new BaseTheme;
  return $obj;
}

function theme_blocks($region, &$theme) {
  global $id, $PHP_SELF, $user;

  $result = db_query("SELECT * FROM blocks WHERE (status = '1' OR custom = '1') ". ($region != "all" ? "AND region = '%s' " : "") ."ORDER BY weight, name", $region == "left" ? 0 : 1);

  while ($result && ($block = db_fetch_object($result))) {
    if (($block->status && (!$user->uid || !$block->custom)) || ($block->custom && $user->block[$block->name])) {
      $blocks = module_invoke($block->module, "block");
      if ($blocks[$block->delta]["content"]) {
        $theme->box(t($blocks[$block->delta]["subject"]), $blocks[$block->delta]["content"], $region);
      }
    }
  }
}

?>

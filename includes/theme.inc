<?

function theme_init() {
  global $user, $themes;

  if ($user->theme && file_exists($themes[$user->theme][0])) {
    include_once $themes[$user->theme][0];
  }
  else {
    include_once $themes[key($themes)][0];
  }
  return new Theme();
}

function theme_account($theme) {
  global $user, $site_name, $links, $menu;

  if ($user->id) {

    function submission_number() {
      $result = db_query("SELECT COUNT(id) FROM stories WHERE status = 1");
      return ($result) ? db_result($result, 0) : 0;
    }

    function menu($name, $module) {
      global $menu;
      if ($module["menu"]) $menu = ($menu) ? array_merge($menu, $module["menu"]()) : $module["menu"]();
    }

    module_iterate("menu");

    // Display account settings:
    $content .= "<LI><A HREF=\"account.php?op=track&topic=comments\">". t("track your comments") ."</A></LI>\n";
    $content .= "<LI><A HREF=\"account.php?op=track&topic=stories\">". t("track your stories") ."</A></LI>\n";
    $content .= "<LI><A HREF=\"account.php?op=track&topic=site\">". strtr(t("track %a"), array("%a" => $site_name)) ."</A></LI>\n";
    $content .= "<P>\n";
    $content .= "<LI><A HREF=\"account.php?op=edit&topic=user\">". t("edit your information") ."</A></LI>\n";
    $content .= "<LI><A HREF=\"account.php?op=edit&topic=site\">". t("edit your preferences") ."</A></LI>\n";
    $content .= "<LI><A HREF=\"account.php?op=edit&topic=content\">". t("edit your content") ."</A></LI>\n";
    $content .= "<P>\n";

    if (user_access($user)) {
      $content .= "<LI><A HREF=\"admin.php\">administer ". $site_name ."</A></LI>\n";
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
    $output .= "<CENTER>\n";
    $output .= " <FORM ACTION=\"account.php?op=login\" METHOD=\"post\">\n";
    $output .= "  <B>". t("Username") .":</B><BR><INPUT NAME=\"userid\" SIZE=\"15\"><P>\n";
    $output .= "  <B>". t("Password") .":</B><BR><INPUT NAME=\"passwd\" SIZE=\"15\" TYPE=\"password\"><BR>\n";
    $output .= "  <INPUT TYPE=\"submit\" VALUE=\"". t("Login") ."\"><BR>\n";
    $output .= "  <A HREF=\"account.php\">". t("REGISTER") ."</A>\n";
    $output .= " </FORM>\n";
    $output .= "</CENTER>\n";

    $theme->box(t("Login"), $output);
  }
}


function theme_blocks($region, $theme) {
  global $id, $PHP_SELF, $user;

  switch (strrchr($PHP_SELF, "/")) {
    case "/story.php":
      if ($region != "left") {
        if ($user->id) $story = db_fetch_object(db_query("SELECT * FROM stories WHERE id = '$id'"));
        if ($story->status == 1) theme_moderation_results($theme, $story);
        else theme_new_headlines($theme);
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

function theme_morelink($theme, $story) {
  return ($story->article) ? "[ <A HREF=\"story.php?id=$story->id\"><FONT COLOR=\"$theme->link\"><B>". t("read more") ."</B></FONT></A> | ". sizeof(explode(" ", $story->article)) ." ". t("words") ." | <A HREF=\"story.php?id=$story->id\"><FONT COLOR=\"$theme->link\">". format_plural($story->comments, "comment", "comments") ."</FONT></A> ]" : "[ <A HREF=\"story.php?id=$story->id\"><FONT COLOR=\"$theme->link\">". format_plural($story->comments, "comment", "comments") ."</FONT></A> ]";
}

function theme_moderation_results($theme, $story) {
  global $user;

  if ($user->id && $story->id && ($user->id == $story->author || user_get($user, "history", "s$story->id"))) {
    $result = db_query("SELECT * FROM users WHERE history LIKE '%s$story->id%'");
    while ($account = db_fetch_object($result)) {
      $output .= format_username($account->userid) ." voted `". user_get($account, "history", "s$story->id") ."'.<BR>";
    }

    $theme->box(t("Moderation results"), ($output ? $output : t("This story has not been moderated yet.")));
  }
}

function theme_related_links($theme, $story) {
  // Parse story for <A HREF="">-tags:
  $text = stripslashes("$story->abstract $story->updates $story->article");
  while ($text = stristr($text, "<A HREF=")) {
    $link = substr($text, 0, strpos(strtolower($text), "</a>") + 4);
    $text = stristr($text, "</A>");
    if (!stristr($link, "mailto:")) $content .= "<LI>$link</LI>";
  }

  // Stories in the same section:
  $content .= " <LI>". t("More about") ." <A HREF=\"index.php?section=". urlencode($story->section) ."\">$story->section</A>.</LI>";

  $theme->box(t("Related links"), $content);
}

function theme_new_headlines($theme, $num = 10) {
  global $user;

  $content = "";
  $result = db_query("SELECT id, subject FROM stories WHERE status = 2 ORDER BY id DESC LIMIT $num");
  while ($story = db_fetch_object($result)) $content .= "<LI><A HREF=\"story.php?id=$story->id\">". check_output($story->subject) ."</A></LI>\n";
  $theme->box(t("Latest headlines"), $content);
}

function theme_old_headlines($theme, $num = 10) {
  global $user;

  if ($user->stories) $result = db_query("SELECT id, subject, timestamp FROM stories WHERE status = 2 ORDER BY timestamp DESC LIMIT $user->stories, $num");
  else $result = db_query("SELECT id, subject, timestamp FROM stories WHERE status = 2 ORDER BY timestamp DESC LIMIT $num, $num");

  while ($story = db_fetch_object($result)) {
    if ($time != date("F jS", $story->timestamp)) {
      $content .= "<P><B>". date("l, M jS", $story->timestamp) ."</B></P>\n";
      $time = date("F jS", $story->timestamp);
    }
    $content .= "<LI><A HREF=\"story.php?id=$story->id\">". check_output($story->subject) ."</A></LI>\n";
  }
  $theme->box(t("Older headlines"), $content);
}

?>

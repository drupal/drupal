<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <title><?php print $head_title ?></title>
    <?php print $head ?>
    <?php print $styles ?>
    <?php print $scripts ?>
    <style type="text/css" media="all">@import "<?php print $path_to_theme ?>/style.css";</style>
    <!--[if lt IE 7]>
    <style type="text/css" media="all">@import "<?php print $path_to_theme ?>/fix-ie.css";</style>
    <![endif]-->
  </head>
  <body class="<?php
  $classes = array('', 'sidebar-left', 'sidebar-right', 'sidebar-both');
  print $classes[((bool)$sidebar_left) + 2 * ((bool)$sidebar_right)];
  ?>">

<!-- Layout -->
  <div id="header-region" class="clear-block"></div>

    <div id="wrapper">
    <div id="container" class="clear-block">

      <div id="header">
        <div id="logo-floater">
          <h1><a href="<?php print check_url($base_path) ?>"><img src="<?php print check_url($logo) ?>" alt="Drupal" id="logo" /><span><?php print $site_title ?></span></a></h1>
        </div>
      </div> <!-- /header -->

      <?php if ($sidebar_left): ?>
        <div id="sidebar-left" class="sidebar">
          <?php print $sidebar_left ?>
        </div>
      <?php endif; ?>

      <div id="center"><div id="squeeze"><div class="right-corner"><div class="left-corner">
          <?php if ($title): print '<h2>'. $title .'</h2>'; endif; ?>

          <?php if ($messages): print $messages; endif; ?>
          <?php print $content ?>
          <span class="clear"></span>

          <!--partial-->

      </div></div></div></div> <!-- /.left-corner, /.right-corner, /#squeeze, /#center -->

      <?php if ($sidebar_right): ?>
        <div id="sidebar-right" class="sidebar">
          <?php print $sidebar_right ?>
        </div>
      <?php endif; ?>

    </div> <!-- /container -->
  </div>
<!-- /layout -->

  </body>
</html>

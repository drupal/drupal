<?php
// $Id$
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php print $language->language ?>" lang="<?php print $language->language ?>" dir="<?php print $language->dir ?>">
  <head>
    <title><?php print $head_title ?></title>
    <?php print $head ?>
    <?php print $styles ?>
    <?php print $scripts ?>
    <!--[if lt IE 7]>
      <?php print $ie_styles ?>
    <![endif]-->
  </head>
  <body class="<?php print $body_classes ?>">

  <div id="header-region" class="clear-block"><?php print $header ?></div>

  <div id="wrapper">
    <div id="container" class="clear-block">

      <div id="header">
        <div id="logo-floater">
        <?php if ($logo || $site_title): ?>
          <h1><a href="<?php print $front_page ?>" title="<?php print $site_title ?>">
          <?php if ($logo): ?>
            <img src="<?php print $logo ?>" alt="<?php print $site_title ?>" id="logo" />
          <?php endif; ?>
          <?php print $site_html ?>
          </a></h1>
        <?php endif; ?>
        </div>

        <?php if ($primary_nav): print $primary_nav; endif; ?>
        <?php if ($secondary_nav): print $secondary_nav; endif; ?>
      </div> <!-- /#header -->

      <?php if ($left): ?>
        <div id="sidebar-left" class="sidebar">
          <?php if ($search_box): ?><div class="block block-theme"><?php print $search_box ?></div><?php endif; ?>
          <?php print $left ?>
        </div>
      <?php endif; ?>

      <div id="center"><div id="squeeze"><div class="right-corner"><div class="left-corner">
          <?php print $breadcrumb; ?>
          <?php if ($mission): ?><div id="mission"><?php print $mission ?></div><?php endif; ?>
          <?php if ($tabs): ?><div id="tabs-wrapper" class="clear-block"><?php endif; ?>
          <?php if ($title): ?><h2<?php print $tabs ? ' class="with-tabs"' : '' ?>><?php print $title ?></h2><?php endif; ?>
          <?php if ($tabs): ?><ul class="tabs primary"><?php print $tabs ?></ul></div><?php endif; ?>
          <?php if ($tabs2): ?><ul class="tabs secondary"><?php print $tabs2 ?></ul><?php endif; ?>
          <?php if ($show_messages && $messages): print $messages; endif; ?>
          <?php print $help; ?>
          <div class="clear-block">
            <?php print $content ?>
          </div>
          <?php print $feed_icons ?>
          <div id="footer"><?php print $footer_message . $footer ?></div>
      </div></div></div></div> <!-- /.left-corner, /.right-corner, /#squeeze, /#center -->

      <?php if ($right): ?>
        <div id="sidebar-right" class="sidebar">
          <?php if (!$left && $search_box): ?><div class="block block-theme"><?php print $search_box ?></div><?php endif; ?>
          <?php print $right ?>
        </div>
      <?php endif; ?>

    </div> <!-- /#container -->
  </div> <!-- /#wrapper -->

  <?php print $closure ?>
  </body>
</html>

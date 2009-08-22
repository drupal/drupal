<?php
// $Id$
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.0//EN"
  "http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php print $language->language ?>" dir="<?php print $language->dir ?>"
  <?php print $rdf_namespaces ?>>
  <head profile="<?php print $grddl_profile ?>">
    <title><?php print $head_title ?></title>
    <?php print $head ?>
    <?php print $styles ?>
    <?php print $scripts ?>
    <!--[if lt IE 7]>
      <?php print $ie_styles ?>
    <![endif]-->
  </head>
  <body class="<?php print $classes ?>">

  <?php print $page_top; ?>

  <div id="header-region" class="clearfix"><?php print $header ?></div>

  <div id="wrapper">
    <div id="container" class="clearfix">

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

      <?php if ($sidebar_first): ?>
        <div id="sidebar-first" class="sidebar">
          <?php if ($search_box): ?><div class="block block-theme"><?php print $search_box ?></div><?php endif; ?>
          <?php print $sidebar_first ?>
        </div>
      <?php endif; ?>

      <div id="center"><div id="squeeze"><div class="right-corner"><div class="left-corner">
          <?php print $breadcrumb; ?>
          <?php if ($highlight): ?><div id="highlight"><?php print $highlight ?></div><?php endif; ?>
          <?php if ($tabs): ?><div id="tabs-wrapper" class="clearfix"><?php endif; ?>
          <?php if ($title): ?><h2<?php print $tabs ? ' class="with-tabs"' : '' ?>><?php print $title ?></h2><?php endif; ?>
          <?php if ($tabs): ?><ul class="tabs primary"><?php print $tabs ?></ul></div><?php endif; ?>
          <?php if ($tabs2): ?><ul class="tabs secondary"><?php print $tabs2 ?></ul><?php endif; ?>
          <?php if ($show_messages && $messages): print $messages; endif; ?>
          <?php print $help; ?>
          <?php if ($action_links): ?><ul class="action-links"><?php print $action_links; ?></ul><?php endif; ?>
          <div class="clearfix">
            <?php print $content ?>
          </div>
          <?php print $feed_icons ?>
          <div id="footer"><?php print $footer ?></div>
      </div></div></div></div> <!-- /.left-corner, /.right-corner, /#squeeze, /#center -->

      <?php if ($sidebar_second): ?>
        <div id="sidebar-second" class="sidebar">
          <?php if (!$sidebar_first && $search_box): ?><div class="block block-theme"><?php print $search_box ?></div><?php endif; ?>
          <?php print $sidebar_second ?>
        </div>
      <?php endif; ?>

    </div> <!-- /#container -->
  </div> <!-- /#wrapper -->

  <?php print $page_bottom; ?>
  </body>
</html>

<?php
// $Id: maintenance-page.tpl.php,v 1.10 2010/11/24 03:30:59 webchick Exp $

/**
 * @file
 * Default theme implementation to display a single Drupal page while offline.
 *
 * All the available variables are mirrored in html.tpl.php and page.tpl.php.
 * Some may be blank but they are provided for consistency.
 *
 * @see template_preprocess()
 * @see template_preprocess_maintenance_page()
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php print $language->language ?>" lang="<?php print $language->language ?>" dir="<?php print $language->dir ?>">

<head>
  <title><?php print $head_title; ?></title>
  <?php print $head; ?>
  <?php print $styles; ?>
  <?php print $scripts; ?>
</head>
<body class="<?php print $classes; ?>">
  <div id="page">
    <div id="header">
      <div id="logo-title">

        <?php if (!empty($logo)): ?>
          <a href="<?php print $base_path; ?>" title="<?php print t('Home'); ?>" rel="home" id="logo">
            <img src="<?php print $logo; ?>" alt="<?php print t('Home'); ?>" />
          </a>
        <?php endif; ?>

        <div id="name-and-slogan">
          <?php if (!empty($site_name)): ?>
            <h1 id="site-name">
              <a href="<?php print $base_path ?>" title="<?php print t('Home'); ?>" rel="home"><span><?php print $site_name; ?></span></a>
            </h1>
          <?php endif; ?>

          <?php if (!empty($site_slogan)): ?>
            <div id="site-slogan"><?php print $site_slogan; ?></div>
          <?php endif; ?>
        </div> <!-- /name-and-slogan -->
      </div> <!-- /logo-title -->

      <?php if (!empty($header)): ?>
        <div id="header-region">
          <?php print $header; ?>
        </div>
      <?php endif; ?>

    </div> <!-- /header -->

    <div id="container" class="clearfix">

      <?php if (!empty($sidebar_first)): ?>
        <div id="sidebar-first" class="column sidebar">
          <?php print $sidebar_first; ?>
        </div> <!-- /sidebar-first -->
      <?php endif; ?>

      <div id="main" class="column"><div id="main-squeeze">

        <div id="content">
          <?php if (!empty($title)): ?><h1 class="title" id="page-title"><?php print $title; ?></h1><?php endif; ?>
          <?php if (!empty($messages)): print $messages; endif; ?>
          <div id="content-content" class="clearfix">
            <?php print $content; ?>
          </div> <!-- /content-content -->
        </div> <!-- /content -->

      </div></div> <!-- /main-squeeze /main -->

      <?php if (!empty($sidebar_second)): ?>
        <div id="sidebar-second" class="column sidebar">
          <?php print $sidebar_second; ?>
        </div> <!-- /sidebar-second -->
      <?php endif; ?>

    </div> <!-- /container -->

    <div id="footer-wrapper">
      <div id="footer">
        <?php if (!empty($footer)): print $footer; endif; ?>
      </div> <!-- /footer -->
    </div> <!-- /footer-wrapper -->

  </div> <!-- /page -->

</body>
</html>

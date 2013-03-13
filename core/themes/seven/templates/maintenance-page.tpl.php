<?php

/**
 * @file
 * Seven's theme implementation to display a single Drupal page while offline.
 *
 * All of the available variables are mirrored in page.tpl.php.
 *
 * @see template_preprocess()
 * @see template_preprocess_maintenance_page()
 * @see seven_preprocess_maintenance_page()
 *
 * @ingroup themeable
 */
?>
<!DOCTYPE html>
<html lang="<?php print $language->langcode ?>" dir="<?php print $language->dir ?>">
  <head>
    <title><?php print $head_title; ?></title>
    <?php print $head; ?>
    <?php print $styles; ?>
    <?php print $scripts; ?>
  </head>
  <body class="<?php print $attributes['class']; ?>">

  <?php print $page_top; ?>

  <header id="branding">
    <?php if ($title): ?><h1 class="page-title"><?php print $title; ?></h1><?php endif; ?>
  </header>

  <div id="page">

    <div id="sidebar-first" class="sidebar">
      <?php if ($logo): ?>
        <img id="logo" src="<?php print $logo ?>" alt="<?php print $site_name ?>" />
      <?php endif; ?>
      <?php if ($sidebar_first): ?>
        <?php print $sidebar_first ?>
      <?php endif; ?>
    </div>

    <main id="content" class="clearfix">
      <?php if ($messages): ?>
        <div id="console"><?php print $messages; ?></div>
      <?php endif; ?>
      <?php if ($help): ?>
        <div id="help">
          <?php print $help; ?>
        </div>
      <?php endif; ?>
      <?php print $content; ?>
    </main>

  </div>

  <footer role="contentinfo">
    <?php print $page_bottom; ?>
  </footer>

  </body>
</html>

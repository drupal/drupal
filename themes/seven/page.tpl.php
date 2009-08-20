<?php
// $Id$
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.0//EN"
  "http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php print $language->language; ?>" dir="<?php print $language->dir; ?>"
  <?php print $rdf_namespaces; ?>>
  <head profile="<?php print $grddl_profile; ?>">
    <title><?php print $head_title; ?></title>
    <?php print $head; ?>
    <?php print $styles; ?>
    <?php print $scripts; ?>
    <?php print $ie_styles; ?>
  </head>
  <body class="<?php print $classes; ?>">

  <?php print $page_top; ?>

  <div id="branding" class="clearfix">
    <?php print $breadcrumb; ?>
    <?php if ($title): ?><h1 class="page-title"><?php print $title; ?></h1><?php endif; ?>
    <?php if ($primary_local_tasks): ?><ul class="tabs primary"><?php print $primary_local_tasks; ?></ul><?php endif; ?>
  </div>

  <div id="page">
    <?php if ($secondary_local_tasks): ?><ul class="tabs secondary"><?php print $secondary_local_tasks; ?></ul><?php endif; ?>

    <div id="content" class="clearfix">
      <?php if ($show_messages && $messages): ?>
        <div id="console" class="clearfix"><?php print $messages; ?></div>
      <?php endif; ?>
      <?php if ($help): ?>
        <div id="help">
          <?php print $help; ?>
        </div>
      <?php endif; ?>
      <?php print $content; ?>
    </div>

    <div id="footer">
      <?php print $feed_icons; ?>
    </div>

  </div>

  <?php print $page_bottom; ?>

  </body>
</html>

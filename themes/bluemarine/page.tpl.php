<?php
// $Id$
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.0//EN" "http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php print $language->language ?>" dir="<?php print $language->dir ?>"
  <?php print $rdf_namespaces ?>>
<head profile="<?php print $grddl_profile ?>">
  <title><?php print $head_title ?></title>
  <?php print $head ?>
  <?php print $styles ?>
  <?php print $scripts ?>
  <script type="text/javascript"><?php /* Needed to avoid Flash of Unstyle Content in IE */ ?> </script>
</head>

<body class="<?php print $body_classes; ?>">
  <div id="header" class="clear-block">
    <?php if ($search_box) { ?><div class="search-box"><?php print $search_box ?></div><?php }; ?>
    <?php if ($logo) { ?><a class="logo" href="<?php print $front_page ?>" title="<?php print t('Home') ?>"><img src="<?php print $logo ?>" alt="<?php print t('Home') ?>" /></a><?php } ?>
    <?php if ($site_name) { ?><h1 class='site-name'><a href="<?php print $front_page ?>" title="<?php print t('Home') ?>"><?php print $site_name ?></a></h1><?php }; ?>
    <?php if ($site_slogan) { ?><div class='site-slogan'><?php print $site_slogan ?></div><?php } ?>

    <div id="menu">
      <?php if (isset($secondary_menu)) { ?><?php print theme('links', $secondary_menu, array('class' => 'links', 'id' => 'subnavlist')); ?><?php } ?>
      <?php if (isset($main_menu)) { ?><?php print theme('links', $main_menu, array('class' => 'links', 'id' => 'navlist')) ?><?php } ?>
    </div>

    <div id="header-region"><?php print $header ?></div>
  </div>

  <div class="layout-columns">
    <?php if ($left) { ?><div id="sidebar-left" class="column"><?php print $left ?></div><?php } ?>

    <div id="main" class="column">
      <?php if ($mission) { ?><div id="mission"><?php print $mission ?></div><?php } ?>
      <div class="inner">
        <?php print $breadcrumb ?>
        <h1 class="title"><?php print $title ?></h1>
        <?php if ($tabs) { ?><div class="tabs"><?php print $tabs ?></div><?php } ?>
        <?php print $help ?>
        <?php if ($show_messages) { print $messages; } ?>
        <?php print $content; ?>
        <?php print $feed_icons; ?>
      </div>
    </div>

    <?php if ($right) { ?><div id="sidebar-right" class="column"><?php print $right ?></div><?php } ?>
  </div>

  <div id="footer">
    <?php print $footer_message ?>
    <?php print $footer ?>
  </div>

<?php print $closure ?>
</body>
</html>

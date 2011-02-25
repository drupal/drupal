<?php
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="<?php print $language->language ?>" xml:lang="<?php print $language->language ?>" dir="<?php print $language->dir ?>">
<head>
  <meta http-equiv="Content-Style-Type" content="text/css" />
  <?php print $head ?>
  <title><?php print $head_title ?></title>
  <?php print $styles ?>
  <?php print $scripts ?>
</head>

<body>

<div class="hide"><a href="#content" title="<?php print t('Skip navigation') ?>." accesskey="2"><?php print t('Skip navigation') ?></a>.</div>

<table id="primary-menu" summary="Navigation elements." border="0" cellpadding="0" cellspacing="0" width="100%">
  <tr>
    <td id="home" width="10%">
      <?php if ($logo) : ?>
        <a href="<?php print $front_page ?>" title="<?php print t('Home') ?>"><img src="<?php print($logo) ?>" alt="<?php print t('Home') ?>" border="0" /></a>
      <?php endif; ?>
    </td>

    <td id="site-info" width="20%">
      <?php if ($site_name) : ?>
        <div class='site-name'><a href="<?php print $front_page ?>" title="<?php print t('Home') ?>"><?php print($site_name) ?></a></div>
      <?php endif;?>
      <?php if ($site_slogan) : ?>
        <div class='site-slogan'><?php print($site_slogan) ?></div>
      <?php endif;?>
    </td>
    <td class="primary-links" width="70%" align="center" valign="middle">
      <?php print theme('links', $primary_links, array('class' => 'links', 'id' => 'navlist')) ?>
    </td>
  </tr>
</table>

<table id="secondary-menu" summary="Navigation elements." border="0" cellpadding="0" cellspacing="0" width="100%">
  <tr>
    <td class="secondary-links" width="75%"  align="center" valign="middle">
      <?php print theme('links', $secondary_links, array('class' => 'links', 'id' => 'subnavlist')) ?>
    </td>
    <td width="25%" align="center" valign="middle">
      <?php print $search_box ?>
    </td>
  </tr>
  <tr>
    <td colspan="2"><div><?php print $header ?></div></td>
  </tr>
</table>

<table id="content" border="0" cellpadding="15" cellspacing="0" width="100%">
  <tr>
    <?php if ($left != ""): ?>
    <td id="sidebar-left">
      <?php print $left ?>
    </td>
    <?php endif; ?>

    <td valign="top">
      <?php if ($mission != ""): ?>
      <div id="mission"><?php print $mission ?></div>
      <?php endif; ?>

      <div id="main">
        <?php if ($title != ""): ?>
          <?php print $breadcrumb ?>
          <h1 class="title"><?php print $title ?></h1>

          <?php if ($tabs != ""): ?>
            <div class="tabs"><?php print $tabs ?></div>
          <?php endif; ?>

        <?php endif; ?>

        <?php if ($show_messages && $messages != ""): ?>
          <?php print $messages ?>
        <?php endif; ?>

        <?php if ($help != ""): ?>
            <div id="help"><?php print $help ?></div>
        <?php endif; ?>

      <!-- start main content -->
      <?php print $content; ?>
      <?php print $feed_icons; ?>
      <!-- end main content -->

      </div><!-- main -->
    </td>
    <?php if ($right != ""): ?>
    <td id="sidebar-right">
      <?php print $right ?>
    </td>
    <?php endif; ?>
  </tr>
</table>

<table id="footer-menu" summary="Navigation elements." border="0" cellpadding="0" cellspacing="0" width="100%">
  <tr>
    <td align="center" valign="middle">
    <?php if (isset($primary_links)) : ?>
      <?php print theme('links', $primary_links, array('class' => 'links primary-links')) ?>
    <?php endif; ?>
    <?php if (isset($secondary_links)) : ?>
      <?php print theme('links', $secondary_links, array('class' => 'links secondary-links')) ?>
    <?php endif; ?>
    </td>
  </tr>
</table>

<?php if ($footer_message || $footer) : ?>
<div id="footer-message">
    <?php print $footer_message . $footer;?>
</div>
<?php endif; ?>
<?php print $closure;?>
</body>
</html>

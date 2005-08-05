<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="{language}" xml:lang="{language}">
<head>
  <title><?php print $head_title ?></title>
  <meta http-equiv="Content-Style-Type" content="text/css" />
  <?php print $head ?>
  <?php print $styles ?>
</head>

<body bgcolor="#ffffff" <?php print theme("onload_attribute"); ?>>

<div class="hide"><a href="#content" title="Skip navigation." accesskey="2">Skip navigation</a>.</div>

<table id="primary-menu" summary="Navigation elements." border="0" cellpadding="0" cellspacing="0" width="100%">
  <tr>
    <td id="home" width="10%">

      <?php if ($logo) : ?>
        <a href="<?php print url() ?>" title="Home"><img src="<?php print($logo) ?>" alt="Home" width="144" height="63" border="0" /></a>
      <?php endif; ?>

    </td>

    <td id="site-info" width="20%">

      <?php if ($site_name) : ?>
        <div class='site-name'><a href="<?php print url() ?>" title="Home"><?php print($site_name) ?></a></div>
      <?php endif;?>

      <?php if ($site_slogan) : ?>
        <div class='site-slogan'><?php print($site_slogan) ?></div>
      <?php endif;?>

    </td>

    <td class="primary-links" width="70%" align="center" valign="middle">
      <?php print theme('links', $primary_links) ?>
    </td>
  </tr>
</table>

<table id="secondary-menu" summary="Navigation elements." border="0" cellpadding="0" cellspacing="0" width="100%">
  <tr>
    <td class="secondary-links" width="75%"  align="center" valign="middle">
      <?php print theme('links', $secondary_links) ?>
    </td>
    <td  width="25%"  align="center" valign="middle">
      <?php if ($search_box): ?>
      <form action="<?php print $search_url ?>" method="post">
        <div id="search">
          <input class="form-text" type="text" size="15" value="" name="edit[keys]" alt="<?php print $search_description ?>" />
          <input class="form-submit" type="submit" value="<?php print $search_button_text ?>" alt="submit" />
        </div>
      </form>
      <?php endif; ?>
    </td>
    </tr>
</table>

<table id="content" border="0" cellpadding="15" cellspacing="0" width="100%">
  <tr>
    <?php if ($sidebar_left != ""): ?>
    <td id="sidebar-left">
      <?php print $sidebar_left ?>
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

        <?php if ($help != ""): ?>
            <div id="help"><?php print $help ?></div>
        <?php endif; ?>

        <?php if ($messages != ""): ?>
          <?php print $messages ?>
        <?php endif; ?>

      <!-- start main content -->
      <?php print($content) ?>
      <!-- end main content -->

      </div><!-- main -->
    </td>
    <?php if ($sidebar_right != ""): ?>
    <td id="sidebar-right">
      <?php print $sidebar_right ?>
    </td>
    <?php endif; ?>
  </tr>
</table>

<table id="footer-menu" summary="Navigation elements." border="0" cellpadding="0" cellspacing="0" width="100%">
  <tr>
    <td align="center" valign="middle">
    <?php if (is_array($primary_links)) : ?>
      <div class="primary-links">
        <?php foreach ($primary_links as $link): ?>
          <?php print $link?> |
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <?php if (is_array($secondary_links)) : ?>
      <div class="secondary-links">
        <?php foreach ($secondary_links as $link): ?>
          <?php print $link?> |
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    </td>
  </tr>
</table>

<?php if ($footer_message) : ?>
<div id="footer-message">
    <p><?php print $footer_message;?></p>
</div>
<?php endif; ?>
<?php print $closure;?>
</body>
</html>

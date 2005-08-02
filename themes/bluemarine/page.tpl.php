<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="<?php print $language ?>" xml:lang="<?php print $language ?>">

<head>
  <title><?php print $head_title ?></title>
  <?php print $head ?>
  <?php print $styles ?>
  <script type="text/javascript"><?php /* Needed to avoid Flash of Unstyle Content in IE */ ?> </script>
</head>

<body<?php print $onload_attributes ?>>

<table border="0" cellpadding="0" cellspacing="0" id="header">
  <tr>
    <td id="logo">

      <a href="./" title="Home"><img src="<?php print $logo ?>" alt="Home" /></a>
      <h1 class='site-name'><a href="./" title="Home"><?php print $site_name ?></a></h1>
      <div class='site-slogan'><?php print $site_slogan ?></div>

    </td>
    <td id="menu">
      <div id="secondary"><?php print theme('links', $secondary_links) ?></div>
      <div id="primary"><?php print theme('links', $primary_links) ?></div>
      <?php if ($search_box) { ?><form action="<?php print $search_url ?>" method="post">
        <div id="search">
          <input class="form-text" type="text" size="15" value="" name="edit[keys]" alt="<?php print $search_description ?>" />
          <input class="form-submit" type="submit" value="<?php print $search_button_text ?>" />
        </div>
      </form><?php } ?>
    </td>
  </tr>
</table>

<table border="0" cellpadding="0" cellspacing="0" id="content">
  <tr>
    <?php if ($sidebar_left) { ?><td id="sidebar-left">
      <?php print $sidebar_left ?>
    </td><?php } ?>
    <td valign="top">
      <?php if ($mission) { ?><div id="mission"><?php print $mission ?></div><?php } ?>
      <div id="main">
        <?php print $breadcrumb ?>
        <h1 class="title"><?php print $title ?></h1>
        <div class="tabs"><?php print $tabs ?></div>
        <?php print $help ?>
        <?php print $messages ?>

        <?php print $content; ?>
      </div>
    </td>
    <?php if ($sidebar_right) { ?><td id="sidebar-right">
      <?php print $sidebar_right ?>
    </td><?php } ?>
  </tr>
</table>

<div id="footer">
  <?php print $footer_message ?>
</div>
<?php print $closure ?>
</body>
</html>

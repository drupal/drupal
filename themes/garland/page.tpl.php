<?php
// $Id$
?>
  <?php print render($page['header']); ?>

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

      <?php if ($page['sidebar_first']): ?>
        <div id="sidebar-first" class="sidebar">
          <?php print render($page['sidebar_first']); ?>
        </div>
      <?php endif; ?>

      <div id="center"><div id="squeeze"><div class="right-corner"><div class="left-corner">
          <?php print $breadcrumb; ?>
          <?php if ($page['highlight']): ?><div id="highlight"><?php render($page['highlight']); ?></div><?php endif; ?>
          <?php if ($tabs): ?><div id="tabs-wrapper" class="clearfix"><?php endif; ?>
          <?php if ($title): ?><h2<?php print $tabs ? ' class="with-tabs"' : '' ?>><?php print $title ?></h2><?php endif; ?>
          <?php if ($tabs): ?><ul class="tabs primary"><?php print render($tabs) ?></ul></div><?php endif; ?>
          <?php if ($tabs2): ?><ul class="tabs secondary"><?php print render($tabs2) ?></ul><?php endif; ?>
          <?php if ($show_messages && $messages): print $messages; endif; ?>
          <?php print render($page['help']); ?>
          <?php if ($action_links): ?><ul class="action-links"><?php print render($action_links); ?></ul><?php endif; ?>
          <div class="clearfix">
            <?php print render($page['content']); ?>
          </div>
          <?php print $feed_icons ?>
          <?php print render($page['footer']) ?>
      </div></div></div></div> <!-- /.left-corner, /.right-corner, /#squeeze, /#center -->

      <?php if ($page['sidebar_second']): ?>
        <div id="sidebar-second" class="sidebar">
          <?php print render($page['sidebar_second']); ?>
        </div>
      <?php endif; ?>

    </div> <!-- /#container -->
  </div> <!-- /#wrapper -->

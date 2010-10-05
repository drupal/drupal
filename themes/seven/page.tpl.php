<?php
// $Id: page.tpl.php,v 1.14 2010/10/05 00:29:14 dries Exp $
?>
  <div id="branding" class="clearfix">
    <?php print $breadcrumb; ?>
    <?php print render($title_prefix); ?>
    <?php if ($title): ?>
      <h1 class="page-title"><?php print $title; ?></h1>
    <?php endif; ?>
    <?php print render($title_suffix); ?>
    <?php if ($primary_local_tasks): ?>
      <h2 class="element-invisible"><?php print t('Primary tabs'); ?></h2>
      <ul class="tabs primary"><?php print render($primary_local_tasks); ?></ul>
    <?php endif; ?>
  </div>

  <div id="page">
    <?php if ($secondary_local_tasks): ?>
      <h2 class="element-invisible"><?php print t('Secondary tabs'); ?></h2>
      <ul class="tabs secondary"><?php print render($secondary_local_tasks); ?></ul>
    <?php endif; ?>

    <div id="content" class="clearfix">
      <div class="element-invisible"><a id="main-content"></a></div>
      <?php if ($messages): ?>
        <div id="console" class="clearfix"><?php print $messages; ?></div>
      <?php endif; ?>
      <?php if ($page['help']): ?>
        <div id="help">
          <?php print render($page['help']); ?>
        </div>
      <?php endif; ?>
      <?php if ($action_links): ?><ul class="action-links"><?php print render($action_links); ?></ul><?php endif; ?>
      <?php print render($page['content']); ?>
    </div>

    <div id="footer">
      <?php print $feed_icons; ?>
    </div>

  </div>

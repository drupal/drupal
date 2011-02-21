<?php
// $Id: views-ui-edit-view.tpl.php,v 1.10.6.2 2010/10/16 14:55:13 dereine Exp $
/**
 * @file views-ui-edit-view.tpl.php
 * Template for the primary view editing window.
 */
?>
<div class="views-edit-view">
  <?php if ($locked): ?>
    <div class="view-locked">
       <?php print t('This view is being edited by user !user, and is therefore locked from editing by others. This lock is !age old. Click here to <a href="!break">break this lock</a>.', array('!user' => $locked, '!age' => $lock_age, '!break' => $break)); ?>
    </div>
  <?php endif; ?>
  <div class="views-basic-info contextual-links-region clearfix<?php if (!empty($view->changed)) { print " changed"; }?>">
    <?php if (!is_numeric($view->vid)): ?>
      <div class="view-changed view-new"><?php print t('New view'); ?></div>
    <?php else: ?>
      <div class="view-changed"><?php print t('Changed view'); ?></div>
    <?php endif; ?>
    <div class="views-quick-links">
      <?php print $quick_links ?>
    </div>
    <?php print t('View %name, displaying items of type <strong>@base</strong>.',
        array('%name' => $view->name, '@base' => $base_table)); ?>
  </div>

  <?php print $tabs; ?>

  <div id="views-ajax-form">
    <div id="views-ajax-title">
      <?php // This is initially empty ?>
    </div>
    <div id="views-ajax-pad">
      <?php /* This is sent in because it is also sent out through settings and
      needs to be consistent. */ ?>
      <?php print $message; ?>
    </div>
  </div>

  <?php print $save_button ?>

  <h2><?php print t('Live preview'); ?></h2>
  <div id='views-live-preview'>
    <?php print $preview ?>
  </div>
</div>

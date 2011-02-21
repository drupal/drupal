<?php
// $Id: views-ui-edit-tab.tpl.php,v 1.11.6.4 2010/12/24 08:25:02 dereine Exp $
/**
 * @file views-ui-edit-tab.tpl.php
 * Template for the primary view editing window.
 */
?>
<div class="clearfix views-display views-display-<?php print $display->id; if (!empty($display->deleted)) { print ' views-display-deleted'; }; ?>">
  <?php // top section ?>
  <?php if ($remove): ?>
    <div class="remove-display"><?php print $remove ?></div>
  <?php endif; ?>
  <?php if ($clone): ?>
    <div class="clone-display"><?php print $clone ?></div>
  <?php endif; ?>
  <div class="top">
    <div class="inside">
      <?php print $display_help_icon; ?>
      <span class="display-title">
        <?php print $title; ?>
      </span>
      <span class="display-description">
        <?php print $description; ?>
      </span>
    </div>
  </div>

  <?php // left section ?>
  <div class="left tab-section">
    <div class="inside">
      <?php // If this is the default display, add some basic stuff here. ?>
      <?php if ($default): ?>
        <div class="views-category">
          <div class="views-category-title"><?php print t('View settings'); ?></div>
          <div class="views-category-content">
          <?php foreach ($details as $name => $detail): ?>
            <div class="<?php $details_class[$name]; if (!empty($details_changed[$name])) { print ' changed'; }?>">
              <?php print $detail ?>
            </div>
          <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <?php foreach ($categories as $category_id => $category): ?>
        <div class="views-category">
          <div class="views-category-title views-category-<?php print $category_id; ?>">
            <?php print $category['title']; ?>
          </div>
          <div class="views-category-content">
            <?php foreach ($category['data'] as $data): ?>
              <div class="<?php
                print $data['class'];
                if (!empty($data['overridden'])) {
                  print ' overridden';
                }
                if (!empty($data['defaulted'])) {
                  print ' defaulted';
                }
                if (!empty($data['changed'])) {
                  print ' changed';
                }?>">
                <?php print $data['links'] . $data['content'] ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <?php // middle section ?>
  <div class="middle tab-section">
    <div class="inside">
      <?php foreach ($areas as $area): ?>
      <div class="views-category">
        <?php print $area; ?>
      </div>
      <?php endforeach;?>
      <?php if (!empty($fields)): ?>
        <div class="views-category">
          <?php print $fields; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <?php // right section ?>
  <div class="right tab-section">
    <div class="inside">
      <div class="views-category">
        <?php print $relationships; ?>
      </div>
      <div class="views-category">
        <?php print $arguments; ?>
      </div>
      <div class="views-category">
        <?php print $sorts; ?>
      </div>
      <div class="views-category">
        <?php print $filters; ?>
      </div>
    </div>
  </div>

</div>

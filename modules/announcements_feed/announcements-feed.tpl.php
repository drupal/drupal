<?php

/**
 * @file
 * Template file for the theming of announcements_feed admin page.
 *
 * Available variables:
 * - $count: Contains the total number of announcements.
 * - $featured: An array of featured announcements.
 * - $standard: An array of non-featured announcements.
 *
 * Each $announcement in $featured and $standard contain:
 * - $announcement['id']: Unique id of the announcement.
 * - $announcement['title']: Title of the announcement.
 * - $announcement['teaser']: Short description of the announcement.
 * - $announcement['link']: Learn more link of the announcement.
 * - $announcement['date_published']: Timestamp of the announcement.
 *
 * @see announcements_feed_theme()
 *
 * @ingroup themeable
 */
?>
<?php if ($count): ?>
  <div class="announcements">
    <ul class="admin-list">
      <?php if ($featured): ?>
        <div class="featured-announcements-wrapper">
          <?php foreach ($featured as $key => $announcement): ?>
            <li class="leaf">
              <div class="announcement-title">
                <h4>
                  <?php print $announcement['title']; ?>
                </h4>
              </div>
              <div class="announcement-teaser">
                <?php print strip_tags($announcement['teaser']); ?>
              </div>
              <div class="announcement-link">
                <?php if($announcement['link']): ?>
                  <a target="_blank" href="<?php print $announcement['link']; ?>">
                    <span>
                      <?php print t('Learn More'); ?>
                    </span>
                  </a>
                <?php endif; ?>
              </div>
            </li>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      <?php if ($standard): ?>
        <?php foreach ($standard as $key => $announcement): ?>
          <li class="leaf">
            <a target="_blank" href="<?php print $announcement['link']; ?>"><?php print $announcement['title']; ?></a>
            <div class="description">
              <?php print format_date(strtotime($announcement['date_published']), 'short'); ?>
            </div>
          </li>
        <?php endforeach; ?>
      <?php endif; ?>
    </ul>
    <?php if ($feed_link): ?>
      <div class="announcements--view-all">
        <a target="_blank" href="<?php print $feed_link; ?>"><?php print t('View all announcements'); ?></a>
      </div>
    <?php endif; ?>
  </div>
<?php else: ?>
  <div class="no-announcements"><span><?php print t('No announcements available'); ?></span></div>
<?php endif; ?>

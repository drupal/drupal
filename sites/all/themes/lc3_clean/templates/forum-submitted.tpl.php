<?php
// vim: set ts=2 sw=2 sts=2 et:

/**
 * @file
 * Default theme implementation to format a simple string indicated when and
 * by whom a topic was submitted.
 *
 * Available variables:
 *
 * - $author: The author of the post.
 * - $time: How long ago the post was created.
 * - $topic: An object with the raw data of the post. Unsafe, be sure
 *   to clean this data before printing.
 *
 * @package   LiteCommerce3 theme
 * @author    Creative Development LLC <info@cdev.ru>
 * @copyright Copyright (c) 2010 Creative Development LLC <info@cdev.ru>. All rights reserved
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GNU General Public License, version 2
 * @version   SVN: $Id: forum-submitted.tpl.php 4782 2010-12-24 07:55:29Z xplorer $
 * @link      http://www.litecommerce.com/
 * @see       ____file_see____
 * @see       template_preprocess_forum_submitted()
 * @see       theme_forum_submitted()
 * @see       template_preprocess()
 * @see       template_preprocess_page()
 * @see       template_process()
 * @since     1.0.0
 */
?>
<?php if ($time): ?>
  <div class="submitted">
	<div class="last-post-time"><?php print t('@time ago', array('@time' => $time)); ?></div>
	<div class="last-post-author"><span>by</span> <?php print $author; ?></div>
  </div>
<?php else: ?>
  <?php print t('n/a'); ?>
<?php endif; ?>

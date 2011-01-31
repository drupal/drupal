<?php
// vim: set ts=2 sw=2 sts=2 et:

/**
 * @file
 * Default theme implementation for comments.
 *
 * Available variables:
 * - $author: Comment author. Can be link or plain text.
 * - $content: An array of comment items. Use render($content) to print them all, or
 *   print a subset such as render($content['field_example']). Use
 *   hide($content['field_example']) to temporarily suppress the printing of a
 *   given element.
 * - $created: Formatted date and time for when the comment was created.
 *   Preprocess functions can reformat it by calling format_date() with the
 *   desired parameters on the $comment->created variable.
 * - $changed: Formatted date and time for when the comment was last changed.
 *   Preprocess functions can reformat it by calling format_date() with the
 *   desired parameters on the $comment->changed variable.
 * - $new: New comment marker.
 * - $permalink: Comment permalink.
 * - $submitted: Submission information created from $author and $created during
 *   template_preprocess_comment().
 * - $picture: Authors picture.
 * - $signature: Authors signature.
 * - $status: Comment status. Possible values are:
 *   comment-unpublished, comment-published or comment-preview.
 * - $title: Linked title.
 * - $classes: String of classes that can be used to style contextually through
 *   CSS. It can be manipulated through the variable $classes_array from
 *   preprocess functions. The default values can be one or more of the following:
 *   - comment: The current template type, i.e., "theming hook".
 *   - comment-by-anonymous: Comment by an unregistered user.
 *   - comment-by-node-author: Comment by the author of the parent node.
 *   - comment-preview: When previewing a new or edited comment.
 *   The following applies only to viewers who are registered users:
 *   - comment-unpublished: An unpublished comment visible only to administrators.
 *   - comment-by-viewer: Comment by the user currently viewing the page.
 *   - comment-new: New comment since last the visit.
 * - $title_prefix (array): An array containing additional output populated by
 *   modules, intended to be displayed in front of the main title tag that
 *   appears in the template.
 * - $title_suffix (array): An array containing additional output populated by
 *   modules, intended to be displayed after the main title tag that appears in
 *   the template.
 *
 * These two variables are provided for context:
 * - $comment: Full comment object.
 * - $node: Node object the comments are attached to.
 *
 * Other variables:
 * - $classes_array: Array of html class attribute values. It is flattened
 *   into a string within the variable $classes.
 *
 * @category  LiteCommerce themes
 * @package   LiteCommerce3 theme
 * @author    Creative Development LLC <info@cdev.ru>
 * @copyright Copyright (c) 2010 Creative Development LLC <info@cdev.ru>. All rights reserved
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GNU General Public License, version 2
 * @version   SVN: $Id: comment.tpl.php 4778 2010-12-23 21:00:18Z xplorer $
 * @link      http://www.litecommerce.com/
 * @see       ____file_see____
 * @see       template_preprocess()
 * @see       template_preprocess_comment()
 * @see       template_process()
 * @see       theme_comment()
 * @since     1.0.0
 */
?>
<div class="<?php print $classes; ?> clearfix"<?php print $attributes; ?>>

  <div class="comment-meta">
    <?php print $picture; ?>
    <?php print $author; ?>
  </div>

  <div class="comment-body">

    <?php if ($new): ?>
      <span class="new"><?php print $new ?></span>
    <?php endif; ?>

    <div class="content comment-content"<?php print $content_attributes; ?>>

      <?php if ($title): ?>
        <?php print render($title_prefix); ?>
          <h3<?php print $title_attributes; ?>><?php print $title ?></h3>
        <?php print render($title_suffix); ?>
      <?php endif; ?>

      <?php
        // We hide the comments and links now so that we can render them later.
        hide($content['links']);
        print render($content);
      ?>

      <?php if ($signature): ?>
      <div class="user-signature">
        <?php print $signature ?>
      </div>
      <?php endif; ?>

      <div class="comment-arrow">&nbsp;</div>

    </div>

    <div class="submitted">
      <?php print $submitted; ?>
      <?php print $permalink; ?>
    </div>

    <?php print render($content['links']) ?>

  </div>

</div>

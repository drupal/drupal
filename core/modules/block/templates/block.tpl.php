<?php

/**
 * @file
 * Default theme implementation to display a block.
 *
 * Available variables:
 * - $plugin_id: The ID of the block implementation.
 * - $label: The configured label of the block if visible.
 * - $configuration: An array of the block's configuration values.
 *   - label: The configured label for the block.
 *   - label_display: The display settings for the label.
 *   - module: The module that provided this block plugin.
 *   - cache: The cache settings.
 *   - Block plugin specific settings will also be stored here.
 * - $content: Block content.
 * - $attributes: An instance of Attributes class that can be manipulated as an
 *    array and printed as a string.
 *    It includes the 'class' information, which includes:
 *   - block: The current template type, i.e., "theming hook".
 *   - block-[module]: The module generating the block. For example, the user
 *     module is responsible for handling the default user navigation block. In
 *     that case the class would be 'block-user'.
 * - $title_prefix (array): An array containing additional output populated by
 *   modules, intended to be displayed in front of the main title tag that
 *   appears in the template.
 * - $title_suffix (array): An array containing additional output populated by
 *   modules, intended to be displayed after the main title tag that appears in
 *   the template.
 *
 * Helper variables:
 * - $is_front: Flags true when presented in the front page.
 * - $logged_in: Flags true when the current user is a logged-in member.
 * - $is_admin: Flags true when the current user is an administrator.
 * - $block_html_id: A valid HTML ID and guaranteed unique.
 *
 * @see template_preprocess()
 * @see template_preprocess_block()
 * @see template_process()
 *
 * @ingroup themeable
 */
?>
<?php if (isset($block_html_id)): ?>
  <div id="<?php print $block_html_id; ?>" <?php print $attributes; ?>>
<?php else: ?>
  <div <?php print $attributes; ?>>
<?php endif; ?>

  <?php print render($title_prefix); ?>
<?php if ($label): ?>
  <h2<?php print $title_attributes; ?>><?php print $label; ?></h2>
<?php endif;?>
  <?php print render($title_suffix); ?>

  <div<?php print $content_attributes; ?>>
    <?php print render($content) ?>
  </div>
</div>

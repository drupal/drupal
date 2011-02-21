<?php
// $Id: simplenews-newsletter-body.tpl.php,v 1.2 2010/12/31 11:36:42 mirodietiker Exp $

/**
 * @file
 * Default theme implementation to format the simplenews newsletter body.
 *
 * Copy this file in your theme directory to create a custom themed body.
 * Rename it to override it. Available templates:
 *   simplenews-newsletter-body--[tid].tpl.php
 *   simplenews-newsletter-body--[view mode].tpl.php
 *   simplenews-newsletter-body--[tid]--[view mode].tpl.php
 * See README.txt for more details.
 *
 * Available variables:
 * - $build: Array as expected by render().
 * - $title: Node title
 * - $language: Language object
 * - $view_mode: Active view mode.
 *
 * @see template_preprocess_simplenews_newsletter_body()
 * @see theme_simplenews_newsletter_body()
 */
?>
<h2><?php print $title; ?></h2>
<?php print render($build); ?>

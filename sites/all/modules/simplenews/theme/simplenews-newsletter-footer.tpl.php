<?php
// $Id: simplenews-newsletter-footer.tpl.php,v 1.2 2010/12/31 11:36:42 mirodietiker Exp $

/**
 * @file
 * Default theme implementation to format the simplenews newsletter footer.
 * 
 * Copy this file in your theme directory to create a custom themed footer.
 * Rename it to simplenews-newsletter-footer--<tid>.tpl.php to override it for a 
 * newsletter using the newsletter term's id.
 *
 * // TODO Update the available variables.
 * Available variables:
 * - $build: Array as expected by render().
 * - $language: language object
 * - $key: email key [node|test]
 * - $format: newsletter format [plain|html]
 * - $unsubscribe_text: unsubscribe text
 * - $test_message: test message warning message
 *
 * Available tokens:
 * - [simplenews-subscriber:unsubscribe-url]: unsubscribe url to be used as link
 * Other available tokens: simplenews_token_info() 'simplenews-subscriber'
 * and 'simplenews-list'
 *
 * @see template_preprocess_simplenews_newsletter_footer()
 * @see theme_simplenews_newsletter_footer()
 */
?>
<?php if ($format == 'html'): ?>
  <p class="newsletter-footer"><a href="[simplenews-subscriber:unsubscribe-url]"><?php print $unsubscribe_text ?></a></p>
<?php else: ?>
-- <?php print $unsubscribe_text ?>: [simplenews-subscriber:unsubscribe-url]
<?php endif ?>

<?php if ($key == 'test'): ?>
- - - <?php print $test_message ?> - - -
<?php endif ?>

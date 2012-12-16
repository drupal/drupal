<?php

/**
 * @file
 * Default theme implementation to present all user data.
 *
 * This template is used when viewing a registered user's page,
 * e.g., example.com/user/123. 123 being the users ID.
 *
 * Use render($content) to print all content, or print a subset
 * such as render($content['field_example']).
 * By default, $user_profile['summary'] is provided, which contains data on the
 * user's history. Other data can be included by modules.
 *
 * Available variables:
 *   - $content: An array of content items. Use render() to print them.
 *   - Field variables: for each field instance attached to the user a
 *     corresponding variable is defined; e.g., $account->field_example has a
 *     variable $field_example defined. When needing to access a field's raw
 *     values, developers/themers are strongly encouraged to use these
 *     variables. Otherwise they will have to explicitly specify the desired
 *     field language, e.g. $account->field_example['en'], thus overriding any
 *     language negotiation rule that was previously applied.
 *
 * @see template_preprocess_user()
 *
 * @ingroup themeable
 */
?>
<article class="profile"<?php print $attributes; ?>>
  <?php print render($content); ?>
</article>

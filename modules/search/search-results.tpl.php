<?php
// $Id: search-results.tpl.php,v 1.5 2010/01/30 07:59:25 dries Exp $

/**
 * @file
 * Default theme implementation for displaying search results.
 *
 * This template collects each invocation of theme_search_result(). This and
 * the child template are dependent to one another sharing the markup for
 * definition lists.
 *
 * Note that modules may implement their own search type and theme function
 * completely bypassing this template.
 *
 * Available variables:
 * - $search_results: All results as it is rendered through
 *   search-result.tpl.php
 * - $type: The type of search, e.g., "node" or "user".
 *
 *
 * @see template_preprocess_search_results()
 */
?>
<?php if ($search_results) : ?>
  <h2><?php print t('Search results');?></h2>
  <dl class="search-results <?php print $type; ?>-results">
    <?php print $search_results; ?>
  </dl>
  <?php print $pager; ?>
<?php else : ?>
  <h2><?php print t('Your search yielded no results');?></h2>
  <?php print search_help('search#noresults', drupal_help_arg()); ?>
<?php endif; ?>

<?php

/**
 * @file
 * Page callbacks for the taxonomy module.
 */

use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Menu callback; displays all nodes associated with a term.
 *
 * @param Drupal\taxonomy\Entity\Term $term
 *   The taxonomy term entity.
 */
function taxonomy_term_page(Term $term) {
  // Assign the term name as the page title.
  drupal_set_title($term->label());

  // @todo This overrides any other possible breadcrumb and is a pure hard-coded
  //   presumption. Make this behavior configurable per vocabulary or term.
  $breadcrumb = array();
  $current = $term;
  while ($parents = taxonomy_term_load_parents($current->id())) {
    $current = array_shift($parents);
    $breadcrumb[] = l($current->label(), 'taxonomy/term/' . $current->id());
  }
  $breadcrumb[] = l(t('Home'), NULL);
  $breadcrumb = array_reverse($breadcrumb);
  drupal_set_breadcrumb($breadcrumb);
  drupal_add_feed('taxonomy/term/' . $term->id() . '/feed', 'RSS - ' . $term->label());

  foreach ($term->uriRelationships() as $rel) {
    $uri = $term->uri($rel);
    // Set the term path as the canonical URL to prevent duplicate content.
    drupal_add_html_head_link(array('rel' => $rel, 'href' => url($uri['path'], $uri['options'])), TRUE);

    if ($rel == 'canonical') {
      // Set the non-aliased canonical path as a default shortlink.
      drupal_add_html_head_link(array('rel' => 'shortlink', 'href' => url($uri['path'], array_merge($uri['options'], array('alias' => TRUE)))), TRUE);
    }
  }

  $build['taxonomy_terms'] = taxonomy_term_view_multiple(array($term->id() => $term));
  if ($nids = taxonomy_select_nodes($term->id(), TRUE, Drupal::config('node.settings')->get('items_per_page'))) {
    $nodes = node_load_multiple($nids);
    $build['nodes'] = node_view_multiple($nodes);
    $build['pager'] = array(
      '#theme' => 'pager',
      '#weight' => 5,
    );
  }
  else {
    $build['no_content'] = array(
      '#prefix' => '<p>',
      '#markup' => t('There is currently no content classified with this term.'),
      '#suffix' => '</p>',
    );
  }
  return $build;
}

/**
 * Generate the content feed for a taxonomy term.
 *
 * @param Drupal\taxonomy\Entity\Term $term
 *   The taxonomy term entity.
 */
function taxonomy_term_feed(Term $term) {
  $channel['link'] = url('taxonomy/term/' . $term->id(), array('absolute' => TRUE));
  $channel['title'] = Drupal::config('system.site')->get('name') . ' - ' . $term->label();
  // Only display the description if we have a single term, to avoid clutter and confusion.
  // HTML will be removed from feed description.
  $channel['description'] = check_markup($term->description->value, $term->format->value, '', TRUE);
  $nids = taxonomy_select_nodes($term->id(), FALSE, Drupal::config('system.rss')->get('items.limit'));

  return node_feed($nids, $channel);
}

/**
 * Page callback: Outputs JSON for taxonomy autocomplete suggestions.
 *
 * This callback outputs term name suggestions in response to Ajax requests
 * made by the taxonomy autocomplete widget for taxonomy term reference
 * fields. The output is a JSON object of plain-text term suggestions, keyed by
 * the user-entered value with the completed term name appended.  Term names
 * containing commas are wrapped in quotes.
 *
 * For example, suppose the user has entered the string 'red fish, blue' in the
 * field, and there are two taxonomy terms, 'blue fish' and 'blue moon'. The
 * JSON output would have the following structure:
 * @code
 *   {
 *     "red fish, blue fish": "blue fish",
 *     "red fish, blue moon": "blue moon",
 *   };
 * @endcode
 *
 * @param $field_name
 *   The name of the term reference field.
 *
 * @see taxonomy_menu()
 * @see taxonomy_field_widget_info()
 */
function taxonomy_autocomplete($field_name) {
  // A comma-separated list of term names entered in the autocomplete form
  // element. Only the last term is used for autocompletion.
  $tags_typed = Drupal::request()->query->get('q');

  // Make sure the field exists and is a taxonomy field.
  if (!($field = field_info_field($field_name)) || $field['type'] !== 'taxonomy_term_reference') {
    // Error string. The JavaScript handler will realize this is not JSON and
    // will display it as debugging information.
    print t('Taxonomy field @field_name not found.', array('@field_name' => $field_name));
    exit;
  }

  // The user enters a comma-separated list of tags. We only autocomplete the last tag.
  $tags_typed = drupal_explode_tags($tags_typed);
  $tag_last = drupal_strtolower(array_pop($tags_typed));

  $matches = array();
  if ($tag_last != '') {

    // Part of the criteria for the query come from the field's own settings.
    $vids = array();
    foreach ($field['settings']['allowed_values'] as $tree) {
      $vids[] = $tree['vocabulary'];
    }

    $query = db_select('taxonomy_term_data', 't');
    $query->addTag('term_access');

    // Do not select already entered terms.
    if (!empty($tags_typed)) {
      $query->condition('t.name', $tags_typed, 'NOT IN');
    }
    // Select rows that match by term name.
    $tags_return = $query
      ->fields('t', array('tid', 'name'))
      ->condition('t.vid', $vids)
      ->condition('t.name', '%' . db_like($tag_last) . '%', 'LIKE')
      ->range(0, 10)
      ->execute()
      ->fetchAllKeyed();

    $prefix = count($tags_typed) ? drupal_implode_tags($tags_typed) . ', ' : '';

    $term_matches = array();
    foreach ($tags_return as $tid => $name) {
      $n = $name;
      // Term names containing commas or quotes must be wrapped in quotes.
      if (strpos($name, ',') !== FALSE || strpos($name, '"') !== FALSE) {
        $n = '"' . str_replace('"', '""', $name) . '"';
      }
      $term_matches[$prefix . $n] = check_plain($name);
    }
  }

  return new JsonResponse($term_matches);
}

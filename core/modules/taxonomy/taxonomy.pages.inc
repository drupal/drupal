<?php

/**
 * @file
 * Page callbacks for the taxonomy module.
 */

use Drupal\taxonomy\Entity\Term;

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

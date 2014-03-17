<?php

/**
 * @file
 * Page callbacks for the taxonomy module.
 */

use Drupal\taxonomy\Entity\Term;

/**
 * Menu callback; displays all nodes associated with a term.
 *
 * @param \Drupal\taxonomy\Entity\Term $term
 *   The taxonomy term entity.
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal\taxonomy\Controller\TaxonomyController::termPage().
 */
function taxonomy_term_page(Term $term) {
  $build['#attached']['drupal_add_feed'][] = array('taxonomy/term/' . $term->id() . '/feed', 'RSS - ' . $term->getName());

  foreach ($term->uriRelationships() as $rel) {
    // Set the term path as the canonical URL to prevent duplicate content.
    $build['#attached']['drupal_add_html_head_link'][] = array(
      array(
        'rel' => $rel,
        'href' => $term->url($rel),
      ),
      TRUE,
    );

    if ($rel == 'canonical') {
      // Set the non-aliased canonical path as a default shortlink.
      $build['#attached']['drupal_add_html_head_link'][] = array(
        array(
          'rel' => 'shortlink',
          'href' => $term->url($rel, array('alias' => TRUE)),
        ),
        TRUE,
      );
    }
  }

  $build['taxonomy_terms'] = taxonomy_term_view_multiple(array($term->id() => $term));
  if ($nids = taxonomy_select_nodes($term->id(), TRUE, \Drupal::config('node.settings')->get('items_per_page'))) {
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
 * @param \Drupal\taxonomy\Entity\Term $term
 *   The taxonomy term entity.
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal\taxonomy\Controller\TaxonomyController::termFeed().
 */
function taxonomy_term_feed(Term $term) {
  $channel['link'] = url('taxonomy/term/' . $term->id(), array('absolute' => TRUE));
  $channel['title'] = \Drupal::config('system.site')->get('name') . ' - ' . $term->getName();
  // Only display the description if we have a single term, to avoid clutter and confusion.
  // HTML will be removed from feed description.
  $channel['description'] = $term->description->processed;
  $nids = taxonomy_select_nodes($term->id(), FALSE, \Drupal::config('system.rss')->get('items.limit'));

  return node_feed($nids, $channel);
}

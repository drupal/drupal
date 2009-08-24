<?php
// $Id$

/**
 * @file
 * Page callbacks for the taxonomy module.
 */

/**
 * Menu callback; displays all nodes associated with a term.
 *
 * @param $term
 *   The taxonomy term.
 * @return
 *   The page content.
 */
function taxonomy_term_page($term) {
  // Build breadcrumb based on the hierarchy of the term.
  $current = (object) array(
    'tid' => $term->tid,
  );
  $breadcrumb = array();
  while ($parents = taxonomy_get_parents($current->tid)) {
    $current = array_shift($parents);
    $breadcrumb[] = l($current->name, taxonomy_term_path($current));
  }
  $breadcrumb[] = l(t('Home'), NULL);
  $breadcrumb = array_reverse($breadcrumb);
  drupal_set_breadcrumb($breadcrumb);
  drupal_add_feed(url('taxonomy/term/' . $term->tid . '/feed'), 'RSS - ' . $term->name);
  drupal_add_css(drupal_get_path('module', 'taxonomy') . '/taxonomy.css');

  $build = array();
  $build += field_attach_view('taxonomy_term', $term);
  if (!empty($term->description)) {
    $build['term_description'] = array(
      '#markup' => filter_xss_admin($term->description),
      '#weight' => -1,
      '#prefix' => '<div class="taxonomy-term-description">',
      '#suffix' => '</div>',
    );
  }

  if ($nids = taxonomy_select_nodes(array($term->tid), NULL, TRUE)) {
    $nodes = node_load_multiple($nids);
    $build += node_build_multiple($nodes);
    $build['pager'] = array(
      '#markup' => theme('pager', NULL),
      '#weight' => 5,
     );
  }
  else {
    $build['no_content'] = array(
      '#prefix' => '<p>',
      '#markup' => t('There are currently no posts in this category.'),
      '#suffix' => '</p>',
    );
  }
  return $build;
}

/**
 * Generate the content feed for a taxonomy term.
 *
 * @param $term
 *   The taxonomy term.
 */
function taxonomy_term_feed($term) {
  $channel['link'] = url('taxonomy/term/' . $term->tid, array('absolute' => TRUE));
  $channel['title'] = variable_get('site_name', 'Drupal') . ' - ' . $term->name;
  // Only display the description if we have a single term, to avoid clutter and confusion.
  // HTML will be removed from feed description, so no need to filter here.
  $channel['description'] = $term->description;
  $nids = taxonomy_select_nodes(array($term->tid, NULL, NULL, FALSE));

  node_feed($nids, $channel);
}

/**
 * Page to edit a vocabulary term.
 */
function taxonomy_term_edit($term) {
  if (isset($term)) {
    drupal_set_title($term->name);
    include_once DRUPAL_ROOT . '/'. drupal_get_path('module', 'taxonomy') . '/taxonomy.admin.inc';
    return drupal_get_form('taxonomy_form_term', taxonomy_vocabulary_load($term->vid), (array)$term);
  }
  return drupal_not_found();
}

/**
 * Helper function for autocompletion
 */
function taxonomy_autocomplete_legacy($vid = 0, $tags_typed = '') {
  // The user enters a comma-separated list of tags. We only autocomplete the last tag.
  $tags_typed = drupal_explode_tags($tags_typed);
  $tag_last = drupal_strtolower(array_pop($tags_typed));

  $matches = array();
  if ($tag_last != '') {
    $query = db_select('taxonomy_term_data', 't');
    $query->addTag('term_access');
    $query->leftJoin('taxonomy_term_synonym', 'ts', 't.tid = ts.tid');
    // Don't select already entered terms.
    if (count($tags_typed)) {
      $query->condition('t.name', $tags_typed, 'NOT IN');
    }
    $tags_return = $query
      ->fields('t', array('tid', 'name'))
      ->condition('t.vid', $vid)
      // Select rows that either match by term or synonym name.
      ->condition(db_or()
	    ->where("LOWER(t.name) LIKE :last_string", array(':last_string' => '%' . $tag_last . '%'))
	    ->where("LOWER(ts.name) LIKE :last_string", array(':last_string' => '%' . $tag_last . '%'))
      )
      ->range(0, 10)
      ->execute()
      ->fetchAllKeyed();

    $prefix = count($tags_typed) ? implode(', ', $tags_typed) . ', ' : '';

    // We use two arrays to make sure synonym suggestions appear last.
    $term_matches = $synonym_matches = array();
    foreach ($tags_return as $tid => $name) {
      $n = $name;
      // Commas and quotes in terms are special cases, so encode 'em.
      if (strpos($name, ',') !== FALSE || strpos($name, '"') !== FALSE) {
        $n = '"' . str_replace('"', '""', $name) . '"';
      }
      // Inform the user his query matched a synonym rather than a term.
      if (strpos(drupal_strtolower($name), $tag_last) === FALSE) {
        $name = t('Did you mean %suggestion', array('%suggestion' => $name));
        $synonym_matches[$prefix . $n] = filter_xss($name);
      }
    }
  }

  drupal_json(array_merge($term_matches, $synonym_matches));
}

/**
 * Helper function for autocompletion
 */
function taxonomy_autocomplete($field_name, $bundle, $tags_typed = '') {
  $instance = field_info_instance($field_name, $bundle);
  $field = field_info_field($field_name);

  // The user enters a comma-separated list of tags. We only autocomplete the last tag.
  $tags_typed = drupal_explode_tags($tags_typed);
  $tag_last = drupal_strtolower(array_pop($tags_typed));

  $matches = array();
  if ($tag_last != '') {

    // Part of the criteria for the query come from the field's own settings.
    $vids = array();
    foreach ($field['settings']['allowed_values'] as $tree) {
      $vids[] = $tree['vid'];
    }

    $query = db_select('taxonomy_term_data', 't');
    $query->addTag('term_access');

    // Do not select already entered terms.
    if (!empty($tags_typed)) {
      $query->condition('t.name', $tags_typed, 'NOT IN');
    }
    $tags_return = $query
      ->fields('t', array('tid', 'name'))
      ->condition('t.vid', $vids)
      // Select rows that match by term name.
      ->condition(db_or()
      ->where("t.name LIKE :last_string", array(':last_string' => '%' . $tag_last . '%'))
      )
      ->range(0, 10)
      ->execute()
      ->fetchAllKeyed();

    $prefix = count($tags_typed) ? implode(', ', $tags_typed) . ', ' : '';

    $term_matches = array();
    foreach ($tags_return as $tid => $name) {
      $n = $name;
      // Term names containing commas or quotes must be wrapped in quotes.
      if (strpos($name, ',') !== FALSE || strpos($name, '"') !== FALSE) {
        $n = '"' . str_replace('"', '""', $name) . '"';
      }
      else {
        $term_matches[$prefix . $n] = filter_xss($name);
      }
    }
  }

  drupal_json($term_matches);
}

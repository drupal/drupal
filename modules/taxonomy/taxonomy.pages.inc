<?php
// $Id$

/**
 * @file
 * Page callbacks for the taxonomy module.
 */

/**
 * Menu callback; displays all nodes associated with a term.
 */
function taxonomy_term_page($terms, $depth = 0, $op = 'page') {
  if ($terms['operator'] != 'and' && $terms['operator'] != 'or') {
    drupal_not_found();
  }
  $str_tids = $terms['str_tids'];

  if ($terms['tids']) {
    $result = db_query(db_rewrite_sql('SELECT t.tid, t.name FROM {term_data} t WHERE t.tid IN (' . db_placeholders($terms['tids']) . ')', 't', 'tid'), $terms['tids']);
    $tids = array(); // we rebuild the $tids-array so it only contains terms the user has access to.
    $names = array();
    while ($term = db_fetch_object($result)) {
      $tids[] = $term->tid;
      $names[] = $term->name;
    }

    if ($names) {
      $title = check_plain(implode(', ', $names));
      drupal_set_title($title);

      switch ($op) {
        case 'page':
          // Build breadcrumb based on first hierarchy of first term:
          $current->tid = $tids[0];
          $breadcrumb = array();
          while ($parents = taxonomy_get_parents($current->tid)) {
            $current = array_shift($parents);
            $breadcrumb[] = l($current->name, taxonomy_term_path($current));
          }
          $breadcrumb[] = l(t('Home'), NULL);
          $breadcrumb = array_reverse($breadcrumb);
          drupal_set_breadcrumb($breadcrumb);

          $output = theme('taxonomy_term_page', $tids, taxonomy_select_nodes($tids, $terms['operator'], $depth, TRUE));
          drupal_add_feed(url('taxonomy/term/' . $str_tids . '/' . $depth . '/feed'), 'RSS - ' . $title);
          return $output;
          break;

        case 'feed':
          $channel['link'] = url('taxonomy/term/' . $str_tids . '/' . $depth, array('absolute' => TRUE));
          $channel['title'] = variable_get('site_name', 'Drupal') . ' - ' . $title;
          // Only display the description if we have a single term, to avoid clutter and confusion.
          if (count($tids) == 1) {
            $term = taxonomy_term_load($tids[0]);
            // HTML will be removed from feed description, so no need to filter here.
            $channel['description'] = $term->description;
          }

          $result = taxonomy_select_nodes($tids, $terms['operator'], $depth, FALSE);
          $items = array();
          while ($row = db_fetch_object($result)) {
            $items[] = $row->nid;
          }

          node_feed($items, $channel);
          break;

        default:
          drupal_not_found();
      }
    }
    else {
      drupal_not_found();
    }
  }
}

/**
 * Render a taxonomy term page HTML output.
 *
 * @param $tids
 *   An array of term ids.
 * @param $result
 *   A pager_query() result, such as that performed by taxonomy_select_nodes().
 *
 * @ingroup themeable
 */
function theme_taxonomy_term_page($tids, $result) {
  drupal_add_css(drupal_get_path('module', 'taxonomy') . '/taxonomy.css');
  $output = '';

  // Only display the description if we have a single term, to avoid clutter and confusion.
  if (count($tids) == 1) {
    $term = taxonomy_term_load($tids[0]);
    $description = $term->description;

    // Check that a description is set.
    if (!empty($description)) {
      $output .= '<div class="taxonomy-term-description">';
      $output .= filter_xss_admin($description);
      $output .= '</div>';
    }
  }

  $output .= taxonomy_render_nodes($result);

  return $output;
}

/**
 * Page to edit a vocabulary term.
 */
function taxonomy_term_edit($term) {
  if (isset($term)) {
    drupal_set_title($term->name);
    return drupal_get_form('taxonomy_form_term', taxonomy_vocabulary_load($term->vid), (array)$term);
  }
  return drupal_not_found();
}

/**
 * Helper function for autocompletion
 */
function taxonomy_autocomplete($vid, $string = '') {
  // The user enters a comma-separated list of tags. We only autocomplete the last tag.
  $array = drupal_explode_tags($string);

  // Fetch last tag
  $last_string = trim(array_pop($array));
  $matches = array();
  if ($last_string != '') {
    $result = db_query_range(db_rewrite_sql("SELECT t.tid, t.name FROM {term_data} t WHERE t.vid = :vid AND LOWER(t.name) LIKE LOWER(:last_string)", 't', 'tid'), array(
      ':vid' => $vid,
      ':last_string' => '%'. $last_string .'%',
    ), 0, 10);

    $prefix = count($array) ? implode(', ', $array) . ', ' : '';

    while ($tag = db_fetch_object($result)) {
      $n = $tag->name;
      // Commas and quotes in terms are special cases, so encode 'em.
      if (strpos($tag->name, ',') !== FALSE || strpos($tag->name, '"') !== FALSE) {
        $n = '"' . str_replace('"', '""', $tag->name) . '"';
      }
      $matches[$prefix . $n] = check_plain($tag->name);
    }
  }

  drupal_json($matches);
}

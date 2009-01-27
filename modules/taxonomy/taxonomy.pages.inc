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
    $result = db_query(db_rewrite_sql('SELECT t.tid, t.name FROM {taxonomy_term_data} t WHERE t.tid IN (' . db_placeholders($terms['tids']) . ')', 't', 'tid'), $terms['tids']);
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
          $current = (object) array(
            'tid' => $tids[0],
          );
          $breadcrumb = array();
          while ($parents = taxonomy_get_parents($current->tid)) {
            $current = array_shift($parents);
            $breadcrumb[] = l($current->name, taxonomy_term_path($current));
          }
          $breadcrumb[] = l(t('Home'), NULL);
          $breadcrumb = array_reverse($breadcrumb);
          drupal_set_breadcrumb($breadcrumb);
          drupal_add_feed(url('taxonomy/term/' . $str_tids . '/' . $depth . '/feed'), 'RSS - ' . $title);
          drupal_add_css(drupal_get_path('module', 'taxonomy') . '/taxonomy.css');

          $build = array();
          // Only display the description if we have a single term, to avoid clutter and confusion.
          if (count($tids) == 1) {
            $term = taxonomy_term_load($tids[0]);
            if (!empty($term->description)) {
              $build['term_description'] = array(
                '#markup' => filter_xss_admin($term->description),
                '#weight' => -1,
                '#prefix' => '<div class="taxonomy-term-description">',
                '#suffix' => '</div>',
              );
            }
          }

          if ($nids = taxonomy_select_nodes($tids, $terms['operator'], $depth, TRUE)) {
            $nodes = node_load_multiple($nids);
            $build += node_build_multiple($nodes);
            $build['pager'] = array(
              '#markup' => theme('pager', NULL, variable_get('default_nodes_main', 10)),
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

          return drupal_get_page($build);

        case 'feed':
          $channel['link'] = url('taxonomy/term/' . $str_tids . '/' . $depth, array('absolute' => TRUE));
          $channel['title'] = variable_get('site_name', 'Drupal') . ' - ' . $title;
          // Only display the description if we have a single term, to avoid clutter and confusion.
          if (count($tids) == 1) {
            $term = taxonomy_term_load($tids[0]);
            // HTML will be removed from feed description, so no need to filter here.
            $channel['description'] = $term->description;
          }

          $nids = taxonomy_select_nodes($tids, $terms['operator'], $depth, FALSE);

          node_feed($nids, $channel);
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
    $result = db_query_range(db_rewrite_sql("SELECT t.tid, t.name FROM {taxonomy_term_data} t WHERE t.vid = :vid AND LOWER(t.name) LIKE LOWER(:last_string)", 't', 'tid'), array(
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

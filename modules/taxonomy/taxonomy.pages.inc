<?php
// $Id: taxonomy.pages.inc,v 1.2 2007/09/27 16:52:00 goba Exp $

/**
 * @file
 * Administrative page callbacks for the taxonomy module.
 */

/**
 * Menu callback; displays all nodes associated with a term.
 */
function taxonomy_term_page($str_tids = '', $depth = 0, $op = 'page') {
  $terms = taxonomy_terms_parse_string($str_tids);
  if ($terms['operator'] != 'and' && $terms['operator'] != 'or') {
    drupal_not_found();
  }

  if ($terms['tids']) {
    $result = db_query(db_rewrite_sql('SELECT t.tid, t.name FROM {term_data} t WHERE t.tid IN ('. db_placeholders($terms['tids']) .')', 't', 'tid'), $terms['tids']);
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
          $breadcrumbs = array(array('path' => $_GET['q'], 'title' => $names[0]));
          while ($parents = taxonomy_get_parents($current->tid)) {
            $current = array_shift($parents);
            $breadcrumbs[] = array('path' => 'taxonomy/term/'. $current->tid, 'title' => $current->name);
          }
          $breadcrumbs = array_reverse($breadcrumbs);
          menu_set_location($breadcrumbs);

          $output = taxonomy_render_nodes(taxonomy_select_nodes($tids, $terms['operator'], $depth, TRUE));
          drupal_add_feed(url('taxonomy/term/'. $str_tids .'/'. $depth .'/feed'), 'RSS - '. $title);
          return $output;
          break;

        case 'feed':
          $term = taxonomy_get_term($tids[0]);
          $channel['link'] = url('taxonomy/term/'. $str_tids .'/'. $depth, array('absolute' => TRUE));
          $channel['title'] = variable_get('site_name', 'Drupal') .' - '. $title;
          $channel['description'] = $term->description;

          $result = taxonomy_select_nodes($tids, $terms['operator'], $depth, FALSE);

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
 * Helper function for autocompletion
 */
function taxonomy_autocomplete($vid, $string = '') {
  // The user enters a comma-separated list of tags. We only autocomplete the last tag.
  $array = drupal_explode_tags($string);

  // Fetch last tag
  $last_string = trim(array_pop($array));
  $matches = array();
  if ($last_string != '') {
    $result = db_query_range(db_rewrite_sql("SELECT t.tid, t.name FROM {term_data} t WHERE t.vid = %d AND LOWER(t.name) LIKE LOWER('%%%s%%')", 't', 'tid'), $vid, $last_string, 0, 10);

    $prefix = count($array) ? implode(', ', $array) .', ' : '';

    while ($tag = db_fetch_object($result)) {
      $n = $tag->name;
      // Commas and quotes in terms are special cases, so encode 'em.
      if (strpos($tag->name, ',') !== FALSE || strpos($tag->name, '"') !== FALSE) {
        $n = '"'. str_replace('"', '""', $tag->name) .'"';
      }
      $matches[$prefix . $n] = check_plain($tag->name);
    }
  }

  drupal_json($matches);
}

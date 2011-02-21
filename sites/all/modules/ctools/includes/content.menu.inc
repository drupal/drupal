<?php
// $Id: content.menu.inc,v 1.9 2011/01/05 20:34:46 merlinofchaos Exp $

/**
 * @file
 * Contains menu item registration for the content tool.
 *
 * The menu items registered are AJAX callbacks for the things like
 * autocomplete and other tools needed by the content types.
 */

function ctools_content_menu(&$items) {
  $base = array(
    'access arguments' => array('access content'),
    'type' => MENU_CALLBACK,
    'file' => 'includes/content.menu.inc',
  );
  $items['ctools/autocomplete/%'] = array(
    'page callback' => 'ctools_content_autocomplete_entity',
    'page arguments' => array(2),
  ) + $base;
}

/**
 * Helper function for autocompletion of entity titles.
 */
function ctools_content_autocomplete_entity($type, $string = '') {
  $entity = entity_get_info($type);
  if ($string != '') {
    // @todo verify the query logic here, it's untested.
    // Set up the query
    $query = db_select($entity['base table'], 'b');
    if ($entity['entity keys']['label']) {
      $query->fields('b', array($entity['entity keys']['id'], $entity['entity keys']['label']))->range(0, 10);
    }
    else {
      $query->fields('b', array($entity['entity keys']['id']))->range(0, 10);
    }

    $preg_matches = array();
    $match = preg_match('/\[id: (\d+)\]/', $string, $preg_matches);
    if (!$match) {
      $match = preg_match('/^id: (\d+)/', $string, $preg_matches);
    }
    if ($match) {
      $query->condition('b.' . $entity['entity keys']['id'], $preg_matches[1]);
    }
    elseif ($entity['entity keys']['label']) {
      $query->condition('b.' . $entity['entity keys']['label'], '%' . db_like($string) . '%', 'LIKE');
    }

    $matches = array();
    if ($type == 'node') {
      $query->addTag('node_access');
      foreach ($query->execute() as $nodeish) {
        $name = empty($nodeish->name) ? variable_get('anonymous', t('Anonymous')) : check_plain($nodeish->name);
        $matches[$nodeish->title . " [id: $nodeish->nid]"] = '<span class="autocomplete_title">' . check_plain($nodeish->title) . '</span> <span class="autocomplete_user">(' . t('by @user', array('@user' => $name)) . ')</span>';
      }
    }
    else {
      foreach ($query->execute() as $item) {
        $id = $item->{$entity['entity keys']['id']};
        if ($entity['entity keys']['label']) {
          $matches[$item->{$entity['entity keys']['label']} . " [id: $id]"] = '<span class="autocomplete_title">' . check_plain($item->{$entity['entity keys']['label']}) . '</span>';
        }
        else {
          $matches["[id: $id]"] = '<span class="autocomplete_title">' . check_plain($item->{$entity['entity keys']['id']}) . '</span>';
        }
      }
    }
    drupal_json_output($matches);
  }
}

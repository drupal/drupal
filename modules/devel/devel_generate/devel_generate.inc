<?php

/**
 * Generate some random users.
 *
 * @param $num
 *  Number of users to generate.
 * @param $kill
 *  Boolean that indicates if existing users should be removed first.
 * @param $age
 *  The max age of each randomly-generated user, in seconds.
 * @param $roles
 *  An array of role IDs that the users should receive.
 * @param $pass
 *  A string to be used as the password for the generated users.
 */
function devel_create_users($num, $kill, $age = 0, $roles = array(), $pass = NULL) {
  $url = parse_url($GLOBALS['base_url']);
  if ($kill) {
    $uids = db_select('users', 'u')
            ->fields('u', array('uid'))
            ->condition('uid', 1, '>')
            ->execute()
            ->fetchAllAssoc('uid');
    user_delete_multiple(array_keys($uids));
    drupal_set_message(format_plural(count($uids), '1 user deleted', '@count users deleted.'));
  }

  if ($num > 0) {
    $names = array();
    while (count($names) < $num) {
      $name = devel_generate_word(mt_rand(6, 12));
      $names[$name] = '';
    }

    if (empty($roles)) {
      $roles = array(DRUPAL_AUTHENTICATED_RID);
    }
    foreach ($names as $name => $value) {
      $edit = array(
        'uid'     => NULL,
        'name'    => $name,
        'pass'    => $pass,
        'mail'    => $name . '@' . $url['host'] . '.invalid',
        'status'  => 1,
        'created' => REQUEST_TIME - mt_rand(0, $age),
        'roles' => drupal_map_assoc($roles),
        'devel_generate' => TRUE // A flag to let hook_user_* know that this is a generated user.
      );
      $account = entity_create('user', $edit);

      // Populate all core fields on behalf of field.module
      module_load_include('inc', 'devel_generate', 'devel_generate.fields');
      devel_generate_fields($account, 'user', 'user');
      $account->save();
    }
  }
  drupal_set_message(t('!num_users created.', array('!num_users' => format_plural($num, '1 user', '@count users'))));
}


/**
 * The main API function for creating content.
 *
 * See devel_generate_content_form() for the supported keys in
 * $form_state['values'].
 * Other modules may participate by form_alter() on that form and then handling
 * their data during hook_node_insert() or in their own submit handler for the
 * form.
 *
 * @param string $form_state
 * @return void
 */
function devel_generate_content($form_state) {
  if (!empty($form_state['values']['kill_content'])) {
    devel_generate_content_kill($form_state['values']);
  }

  if (count($form_state['values']['node_types'])) {
    // Generate nodes.
    devel_generate_content_pre_node($form_state['values']);
    $start = time();
    for ($i = 1; $i <= $form_state['values']['num_nodes']; $i++) {
      devel_generate_content_add_node($form_state['values']);
      if (function_exists('drush_log') && $i % drush_get_option('feedback', 1000) == 0) {
        $now = time();
        drush_log(dt('Completed !feedback nodes (!rate nodes/min)', array('!feedback' => drush_get_option('feedback', 1000), '!rate' => (drush_get_option('feedback', 1000)*60)/($now-$start))), 'ok');
        $start = $now;
      }
    }
  }

  devel_generate_set_message(format_plural($form_state['values']['num_nodes'], '1 node created.', 'Finished creating @count nodes'));
}

function devel_generate_add_comments($node, $users, $max_comments, $title_length = 8) {
  $num_comments = mt_rand(1, $max_comments);
  for ($i = 1; $i <= $num_comments; $i++) {
    switch ($i % 3) {
      case 1:
        $pid = db_query_range("SELECT cid FROM {comment} WHERE pid = 0 AND nid = :nid ORDER BY RAND()", 0, 1, array(':nid' => $node->nid))->fetchField();
        break;
      case 2:
        $pid = db_query_range("SELECT cid FROM {comment} WHERE pid > 0 AND nid = :nid ORDER BY RAND()", 0, 1, array(':nid' => $node->nid))->fetchField();
        break;
      default:
        $pid = 0;
    }

    $comment = entity_create('comment', array(
      'nid' => $node->nid,
      'cid' => NULL,
      'name' => 'devel generate',
      'mail' => 'devel_generate@example.com',
      'timestamp' => mt_rand($node->created, REQUEST_TIME),
      'subject' => substr(devel_create_greeking(mt_rand(2, $title_length), TRUE), 0, 63),
      'uid' => $users[array_rand($users)],
      'langcode' => LANGUAGE_NOT_SPECIFIED,
      'pid' => $pid,
    ));

    // Populate all core fields on behalf of field.module
    module_load_include('inc', 'devel_generate', 'devel_generate.fields');
    devel_generate_fields($comment, 'comment', 'comment_node_' . $node->type);
    $comment->save();
  }
}

function devel_generate_vocabs($records, $maxlength = 12, $types = array('page', 'article')) {
  $vocs = array();

  // Insert new data:
  for ($i = 1; $i <= $records; $i++) {
    $name = devel_generate_word(mt_rand(2, $maxlength));
    $voc = entity_create('taxonomy_vocabulary', array(
      'name' => $name,
      'vid' => drupal_strtolower($name),
      'langcode' => LANGUAGE_NOT_SPECIFIED,
      'description' => "description of $name",
      'hierarchy' => 1,
      'weight' => mt_rand(0, 10),
      'multiple' => 1,
      'required' => 0,
      'relations' => 1,
    ));
    taxonomy_vocabulary_save($voc);
    $vocs[] = $voc->name;

    unset($voc);
  }
  return $vocs;
}

/**
 * Generates taxonomy terms for a list of given vocabularies.
 *
 * @param $records
 *   int number of terms to create in total.
 * @param $vocabs
 *   array list of vocabs to populate.
 * @param $maxlength
 *   int maximum length per term.
 * @return
 *   array the list of names of the created terms.
 */
function devel_generate_terms($records, $vocabs, $maxlength = 12) {
  $terms = array();

  // Insert new data:
  $max = db_query('SELECT MAX(tid) FROM {taxonomy_term_data}')->fetchField();
  $start = time();
  for ($i = 1; $i <= $records; $i++) {
    $values = array();
    switch ($i % 2) {
      case 1:
        // Set vid and vocabulary_machine_name properties.
        $vocab = $vocabs[array_rand($vocabs)];
        $values['vid'] = $vocab->vid;
        $values['vocabulary_machine_name'] = $vocab->vid;
        $values['parent'] = array(0);
        break;
      default:
        while (TRUE) {
          // Keep trying to find a random parent.
          $candidate = mt_rand(1, $max);
          $query = db_select('taxonomy_term_data', 't');
          $parent = $query
                    ->fields('t', array('tid', 'vid'))
                    ->condition('t.vid', array_keys($vocabs), 'IN')
                    ->condition('t.tid', $candidate, '>=')
                    ->range(0,1)
                    ->execute()
                    ->fetchAssoc();
          if ($parent['tid']) {
            break;
          }
        }
        $values['parent'] = array($parent['tid']);
        // Slight speedup due to this property being set.
        $values['vocabulary_machine_name'] = $parent['vid'];
        $values['vid'] = $parent['vid'];
        break;
    }

    $values['name'] = devel_generate_word(mt_rand(2, $maxlength));
    $values['description'] = "description of " . $values['name'];
    $values['format'] = filter_fallback_format();
    $values['weight'] = mt_rand(0, 10);
    $values['langcode'] = LANGUAGE_NOT_SPECIFIED;
    $term = entity_create('taxonomy_term', $values);

    // Populate all core fields on behalf of field.module
    module_load_include('inc', 'devel_generate', 'devel_generate.fields');
    devel_generate_fields($term, 'taxonomy_term', $values['vocabulary_machine_name']);

    if ($status = taxonomy_term_save($term)) {
      $max += 1;
      if (function_exists('drush_log')) {
        $feedback = drush_get_option('feedback', 1000);
        if ($i % $feedback == 0) {
          $now = time();
          drush_log(dt('Completed !feedback terms (!rate terms/min)', array('!feedback' => $feedback, '!rate' => $feedback*60 / ($now-$start) )), 'ok');
          $start = $now;
        }
      }

      // Limit memory usage. Only report first 20 created terms.
      if ($i < 20) {
        $terms[] = $term->name;
      }

      unset($term);
    }
  }
  return $terms;
}

// TODO: use taxonomy_get_entries once that exists.
function devel_generate_get_terms($vids) {
  return db_select('taxonomy_term_data', 'td')
           ->fields('td', array('tid'))
           ->condition('vid', $vids, 'IN')
           ->orderBy('tid', 'ASC')
           ->execute()
           ->fetchCol('tid');
}

/**
 * Deletes all terms of a vocabulary.
 *
 * @param $vid
 *   int a vocabulary vid.
 */
function devel_generate_delete_vocabulary_terms($vid) {
  $tids = array();
  foreach (taxonomy_get_tree($vid) as $term) {
    $tids[] = $term->tid;
  }
  taxonomy_term_delete_multiple($tids);
}

/**
 * Deletes all vocabularies.
 */
function devel_generate_delete_vocabularies() {
  foreach (taxonomy_vocabulary_load_multiple() as $vid => $vocab) {
    taxonomy_vocabulary_delete($vid);
  }
}

/**
 * Deletes custom generated menus
 */
function devel_generate_delete_menus() {
  if (module_exists('menu')) {
    foreach (menu_get_menus(FALSE) as $menu => $menu_title) {
      if (strpos($menu, 'devel-') === 0) {
        menu_load($menu)->delete();
      }
    }
  }
  // Delete menu links generated by devel.
  $result = db_select('menu_links', 'm')
    ->fields('m', array('mlid'))
    ->condition('m.menu_name', 'devel', '<>')
    // Look for the serialized version of 'devel' => TRUE.
    ->condition('m.options', '%' . db_like('s:5:"devel";b:1') . '%', 'LIKE')
    ->execute();
  foreach ($result as $link) {
    menu_link_delete($link->mlid);
  }
}

/**
 * Generates new menus.
 */
function devel_generate_menus($num_menus, $title_length = 12) {
  $menus = array();

  if (!module_exists('menu')) {
    $num_menus = 0;
  }

  for ($i = 1; $i <= $num_menus; $i++) {
    $menu = array();
    $menu['label'] = devel_generate_word(mt_rand(2, max(2, $title_length)));
    $menu['id'] = 'devel-' . drupal_strtolower($menu['label']);
    $menu['description'] = t('Description of @name', array('@name' => $menu['label']));
    $new_menu = entity_create('menu', $menu);
    $new_menu->save();
    $menus[$new_menu->id()] = $new_menu->label();
  }

  return $menus;
}

/**
 * Generates menu links in a tree structure.
 */
function devel_generate_links($num_links, $menus, $title_length, $link_types, $max_depth, $max_width) {
  $links = array();
  $menus = array_keys(array_filter($menus));
  $link_types = array_keys(array_filter($link_types));

  $nids = array();
  for ($i = 1; $i <= $num_links; $i++) {
    // Pick a random menu.
    $menu_name = $menus[array_rand($menus)];
    // Build up our link.
    $link = entity_create('menu_link', array(
      'menu_name'   => $menu_name,
      'options'     => array('devel' => TRUE),
      'weight'      => mt_rand(-50, 50),
      'mlid'        => 0,
      'link_title'  => devel_generate_word(mt_rand(2, max(2, $title_length))),
    ));
    $link->options['attributes']['title'] = t('Description of @title.', array('@title' => $link->link_title));

    // For the first $max_width items, make first level links.
    if ($i <= $max_width) {
      $depth = 0;
    }
    else {
      // Otherwise, get a random parent menu depth.
      $depth = mt_rand(1, max(1, $max_depth - 1));
    }
    // Get a random parent link from the proper depth.
    do {
      $link->plid = db_select('menu_links', 'm')
        ->fields('m', array('mlid'))
        ->condition('m.menu_name', $menus, 'IN')
        ->condition('m.depth', $depth)
        ->range(0, 1)
        ->orderRandom()
        ->execute()
        ->fetchField();
      $depth--;
    } while (!$link->plid && $depth > 0);
    if (!$link->plid) {
      $link->plid = 0;
    }

    $link_type = array_rand($link_types);
    switch ($link_types[$link_type]) {
      case 'node':
        // Grab a random node ID.
        $select = db_select('node', 'n')
          ->fields('n', array('nid', 'title'))
          ->condition('n.status', 1)
          ->range(0, 1)
          ->orderRandom();
        // Don't put a node into the menu twice.
        if (!empty($nids[$menu_name])) {
          $select->condition('n.nid', $nids[$menu_name], 'NOT IN');
        }
        $node = $select->execute()->fetchAssoc();
        if (isset($node['nid'])) {
          $nids[$menu_name][] = $node['nid'];
          $link->link_path = $link->router_path = 'node/' . $node['nid'];
          $link->link_title = $node['title'];
          break;
        }
      case 'external':
        $link->link_path = 'http://www.example.com/';
        break;
      case 'front':
        $link->link_path = $link->router_path = '<front>';
        break;
      default:
        $link->devel_link_type = $link_type;
        break;
    }

    $link->save();

    $links[$link->id()] = $link->link_title;
  }

  return $links;
}

function devel_generate_word($length){
  mt_srand((double)microtime()*1000000);

  $vowels = array("a", "e", "i", "o", "u");
  $cons = array("b", "c", "d", "g", "h", "j", "k", "l", "m", "n", "p", "r", "s", "t", "u", "v", "w", "tr",
  "cr", "br", "fr", "th", "dr", "ch", "ph", "wr", "st", "sp", "sw", "pr", "sl", "cl", "sh");

  $num_vowels = count($vowels);
  $num_cons = count($cons);
  $word = '';

  while(strlen($word) < $length){
    $word .= $cons[mt_rand(0, $num_cons - 1)] . $vowels[mt_rand(0, $num_vowels - 1)];
  }

  return substr($word, 0, $length);
}

function devel_create_content($type = NULL) {
  $nparas = mt_rand(1,12);
  $type = empty($type) ? mt_rand(0,3) : $type;

  $output = "";
  switch($type % 3) {
    // MW: This appears undesireable. Was giving <p> in text fields
    // case 1: // html
    //       for ($i = 1; $i <= $nparas; $i++) {
    //         $output .= devel_create_para(mt_rand(10,60),1);
    //       }
    //       break;
    //
    //     case 2: // brs only
    //       for ($i = 1; $i <= $nparas; $i++) {
    //         $output .= devel_create_para(mt_rand(10,60),2);
    //       }
    //       break;

    default: // plain text
      for ($i = 1; $i <= $nparas; $i++) {
        $output .= devel_create_para(mt_rand(10,60)) ."\n\n";
      }
  }

  return $output;
}

function devel_create_para($words, $type = 0) {
  $output = '';
  switch ($type) {
    case 1:
      $output .= "<p>" . devel_create_greeking($words)  . "</p>";
      break;

    case 2:
      $output .= devel_create_greeking($words) . "<br />";
      break;

    default:
      $output .= devel_create_greeking($words);
  }
  return $output;
}

function devel_create_greeking($word_count, $title = FALSE) {
  $dictionary = array("abbas", "abdo", "abico", "abigo", "abluo", "accumsan",
    "acsi", "ad", "adipiscing", "aliquam", "aliquip", "amet", "antehabeo",
    "appellatio", "aptent", "at", "augue", "autem", "bene", "blandit",
    "brevitas", "caecus", "camur", "capto", "causa", "cogo", "comis",
    "commodo", "commoveo", "consectetuer", "consequat", "conventio", "cui",
    "damnum", "decet", "defui", "diam", "dignissim", "distineo", "dolor",
    "dolore", "dolus", "duis", "ea", "eligo", "elit", "enim", "erat",
    "eros", "esca", "esse", "et", "eu", "euismod", "eum", "ex", "exerci",
    "exputo", "facilisi", "facilisis", "fere", "feugiat", "gemino",
    "genitus", "gilvus", "gravis", "haero", "hendrerit", "hos", "huic",
    "humo", "iaceo", "ibidem", "ideo", "ille", "illum", "immitto",
    "importunus", "imputo", "in", "incassum", "inhibeo", "interdico",
    "iriure", "iusto", "iustum", "jugis", "jumentum", "jus", "laoreet",
    "lenis", "letalis", "lobortis", "loquor", "lucidus", "luctus", "ludus",
    "luptatum", "macto", "magna", "mauris", "melior", "metuo", "meus",
    "minim", "modo", "molior", "mos", "natu", "neo", "neque", "nibh",
    "nimis", "nisl", "nobis", "nostrud", "nulla", "nunc", "nutus", "obruo",
    "occuro", "odio", "olim", "oppeto", "os", "pagus", "pala", "paratus",
    "patria", "paulatim", "pecus", "persto", "pertineo", "plaga", "pneum",
    "populus", "praemitto", "praesent", "premo", "probo", "proprius",
    "quadrum", "quae", "qui", "quia", "quibus", "quidem", "quidne", "quis",
    "ratis", "refero", "refoveo", "roto", "rusticus", "saepius",
    "sagaciter", "saluto", "scisco", "secundum", "sed", "si", "similis",
    "singularis", "sino", "sit", "sudo", "suscipere", "suscipit", "tamen",
    "tation", "te", "tego", "tincidunt", "torqueo", "tum", "turpis",
    "typicus", "ulciscor", "ullamcorper", "usitas", "ut", "utinam",
    "utrum", "uxor", "valde", "valetudo", "validus", "vel", "velit",
    "veniam", "venio", "vereor", "vero", "verto", "vicis", "vindico",
    "virtus", "voco", "volutpat", "vulpes", "vulputate", "wisi", "ymo",
    "zelus");
  $dictionary_flipped = array_flip($dictionary);

  $greeking = '';

  if (!$title) {
    $words_remaining = $word_count;
    while ($words_remaining > 0) {
      $sentence_length = mt_rand(3, 10);
      $words = array_rand($dictionary_flipped, $sentence_length);
      $sentence = implode(' ', $words);
      $greeking .= ucfirst($sentence) . '. ';
      $words_remaining -= $sentence_length;
    }
  }
  else {
    // Use slightly different method for titles.
    $words = array_rand($dictionary_flipped, $word_count);
    $words = is_array($words) ? implode(' ', $words) : $words;
    $greeking = ucwords($words);
  }

  // Work around possible php garbage collection bug. Without an unset(), this
  // function gets very expensive over many calls (php 5.2.11).
  unset($dictionary, $dictionary_flipped);
  return trim($greeking);
}

function devel_get_users() {
  $users = array();
  $result = db_query_range("SELECT uid FROM {users}", 0, 50);
  foreach ($result as $record) {
    $users[] = $record->uid;
  }
  return $users;
}

/**
 * Generate statistics information for a node.
 *
 * @param $node
 *   A node object.
 */
function devel_generate_add_statistics($node) {
  $statistic = array(
    'nid' => $node->nid,
    'totalcount' => mt_rand(0, 500),
    'timestamp' => REQUEST_TIME - mt_rand(0, $node->created),
  );
  $statistic['daycount'] = mt_rand(0, $statistic['totalcount']);
  db_insert('node_counter')->fields($statistic)->execute();
}

/**
 * Handle the devel_generate_content_form request to kill all of the content.
 * This is used by both the batch and non-batch branches of the code.
 *
 * @param $num
 *   array of options obtained from devel_generate_content_form.
 */
function devel_generate_content_kill($values) {
  $results = db_select('node', 'n')
              ->fields('n', array('nid'))
              ->condition('type', $values['node_types'], 'IN')
              ->execute();
  foreach ($results as $result) {
    $nids[] = $result->nid;
  }

  if (!empty($nids)) {
    node_delete_multiple($nids);
    drupal_set_message(t('Deleted %count nodes.', array('%count' => count($nids))));
  }
}

/**
 * Pre-process the devel_generate_content_form request.  This is needed so
 * batch api can get the list of users once.  This is used by both the batch
 * and non-batch branches of the code.
 *
 * @param $num
 *   array of options obtained from devel_generate_content_form.
 */
function devel_generate_content_pre_node(&$results) {
  // Get user id.
  $users = devel_get_users();
  $users = array_merge($users, array('0'));
  $results['users'] = $users;
}

/**
 * Create one node. Used by both batch and non-batch code branches.
 *
 * @param $num
 *   array of options obtained from devel_generate_content_form.
 */
function devel_generate_content_add_node(&$results) {
  if (!isset($results['time_range'])) {
    $results['time_range'] = 0;
  }
  $users = $results['users'];

  $node_type = array_rand(array_filter($results['node_types']));
  $type = node_type_load($node_type);

  $edit_node = array(
    'nid' => NULL,
    'type' => $node_type,
    'uid' => $users[array_rand($users)],
    'revision' => mt_rand(0, 1),
    'status' => TRUE,
    'promote' => mt_rand(0, 1),
    'created' => REQUEST_TIME - mt_rand(0, $results['time_range']),
    'langcode' => devel_generate_get_langcode($results),
  );
  if ($type->has_title) {
    // We should not use the random function if the value is not random
    if ($results['title_length'] < 2) {
      $edit_node['title'] = devel_create_greeking(1, TRUE);
    }
    else {
      $edit_node['title'] = devel_create_greeking(mt_rand(1, $results['title_length']), TRUE);
    }
  }
  else {
    $edit_node['title'] = '';
  }
  $node = entity_create('node', $edit_node);

  // A flag to let hook_node_insert() implementations know that this is a
  // generated node.
  $node->devel_generate = $results;

  // Populate all core fields on behalf of field.module
  module_load_include('inc', 'devel_generate', 'devel_generate.fields');
  devel_generate_fields($node, 'node', $node->type);

  // See devel_generate_node_insert() for actions that happen before and after
  // this save.
  node_save($node);
}

/*
 * Determine language based on $results.
 */
function devel_generate_get_langcode($results) {
  if (isset($results['add_language'])) {
    $langcodes = $results['add_language'];
    $langcode = $langcodes[array_rand($langcodes)];
  }
  else {
    $langcode = language_default()->langcode;
  }
  return $langcode == 'en' ? LANGUAGE_NOT_SPECIFIED : $langcode;
}

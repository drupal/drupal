#!/usr/bin/env php
<?php

/**
 * @file
 * Generates content for a Drupal 7 database to test the upgrade process.
 *
 * Run this script at the root of an existing Drupal 6 installation.
 * Steps to use this generation script:
 * - Install drupal 7.
 * - Run this script from your Drupal ROOT directory.
 * - Use the dump-database-d7.sh to generate the D7 file
 *   modules/simpletest/tests/upgrade/database.filled.php
 */

// Define settings.
$cmd = 'index.php';
define('DRUPAL_ROOT', getcwd());
$_SERVER['HTTP_HOST']       = 'default';
$_SERVER['PHP_SELF']        = '/index.php';
$_SERVER['REMOTE_ADDR']     = '127.0.0.1';
$_SERVER['SERVER_SOFTWARE'] = NULL;
$_SERVER['REQUEST_METHOD']  = 'GET';
$_SERVER['QUERY_STRING']    = '';
$_SERVER['PHP_SELF']        = $_SERVER['REQUEST_URI'] = '/';
$_SERVER['HTTP_USER_AGENT'] = 'console';
$modules_to_enable          = array('path', 'poll', 'taxonomy');

// Bootstrap Drupal.
include_once './includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

// Enable requested modules.
require_once DRUPAL_ROOT . '/' . variable_get('password_inc', 'includes/password.inc');
include_once './modules/system/system.admin.inc';
$form = system_modules();
foreach ($modules_to_enable as $module) {
  $form_state['values']['status'][$module] = TRUE;
}
$form_state['values']['disabled_modules'] = $form['disabled_modules'];
system_modules_submit(NULL, $form_state);
unset($form_state);

// Run cron after installing.
drupal_cron_run();

// Create six users.
$query = db_insert('users')->fields(array('uid', 'name', 'pass', 'mail', 'status', 'created', 'access'));
for ($i = 0; $i < 6; $i++) {
  $name = "test user $i";
  $pass = md5("test PassW0rd $i !(.)");
  $mail = "test$i@example.com";
  $now = mktime(0, 0, 0, 1, $i + 1, 2010);
  $query->values(array(db_next_id(), $name, user_hash_password($pass), $mail, 1, $now, $now));
}
$query->execute();

// Create vocabularies and terms.

if (module_exists('taxonomy')) {
  $terms = array();

  // All possible combinations of these vocabulary properties.
  $hierarchy = array(0, 1, 2, 0, 1, 2, 0, 1, 2, 0, 1, 2);
  $multiple  = array(0, 1, 0, 1, 0, 1, 0, 1, 0, 1, 0, 1);
  $required  = array(0, 0, 0, 0, 0, 0, 1, 1, 1, 1, 1, 1);

  $voc_id = 0;
  $term_id = 0;
  for ($i = 0; $i < 24; $i++) {
    $vocabulary = new stdClass;
    ++$voc_id;
    $vocabulary->name = "vocabulary $voc_id (i=$i)";
    $vocabulary->machine_name = 'vocabulary_' . $voc_id . '_' . $i;
    $vocabulary->description = "description of ". $vocabulary->name;
    $vocabulary->multiple = $multiple[$i % 12];
    $vocabulary->required = $required[$i % 12];
    $vocabulary->relations = 1;
    $vocabulary->hierarchy = $hierarchy[$i % 12];
    $vocabulary->weight = $i;
    taxonomy_vocabulary_save($vocabulary);
    $field = array(
      'field_name' => 'taxonomy_'. $vocabulary->machine_name,
      'module' => 'taxonomy',
      'type' => 'taxonomy_term_reference',
      'cardinality' => $vocabulary->multiple || $vocabulary->tags ? FIELD_CARDINALITY_UNLIMITED : 1,
      'settings' => array(
        'required' => $vocabulary->required ? TRUE : FALSE,
        'allowed_values' => array(
          array(
            'vocabulary' => $vocabulary->machine_name,
            'parent' => 0,
          ),
        ),
      ),
    );
    field_create_field($field);
    $node_types = $i > 11 ? array('page') : array_keys(node_type_get_types());
    foreach ($node_types as $bundle) {
      $instance = array(
        'label' => $vocabulary->name,
        'field_name' => $field['field_name'],
        'bundle' => $bundle,
        'entity_type' => 'node',
        'settings' => array(),
        'description' => $vocabulary->help,
        'required' => $vocabulary->required,
        'widget' => array(),
        'display' => array(
          'default' => array(
            'type' => 'taxonomy_term_reference_link',
            'weight' => 10,
          ),
          'teaser' => array(
            'type' => 'taxonomy_term_reference_link',
            'weight' => 10,
          ),
        ),
      );
      if ($vocabulary->tags) {
        $instance['widget'] = array(
          'type' => 'taxonomy_autocomplete',
          'module' => 'taxonomy',
          'settings' => array(
            'size' => 60,
            'autocomplete_path' => 'taxonomy/autocomplete',
          ),
        );
      }
      else {
        $instance['widget'] = array(
          'type' => 'options_select',
          'settings' => array(),
        );
      }
      field_create_instance($instance);
    }
    $parents = array();
    // Vocabularies without hierarchy get one term; single parent vocabularies
    // get one parent and one child term. Multiple parent vocabularies get
    // three terms: t0, t1, t2 where t0 is a parent of both t1 and t2.
    for ($j = 0; $j < $vocabulary->hierarchy + 1; $j++) {
      $term = new stdClass;
      $term->vocabulary_machine_name = $vocabulary->machine_name;
      // For multiple parent vocabularies, omit the t0-t1 relation, otherwise
      // every parent in the vocabulary is a parent.
      $term->parent = $vocabulary->hierarchy == 2 && i == 1 ? array() : $parents;
      ++$term_id;
      $term->name = "term $term_id of vocabulary $voc_id (j=$j)";
      $term->description = 'description of ' . $term->name;
      $term->format = 'filtered_html';
      $term->weight = $i * 3 + $j;
      taxonomy_term_save($term);
      $terms[] = $term->tid;
      $term_vocabs[$term->tid] = 'taxonomy_' . $vocabulary->machine_name;
      $parents[] = $term->tid;
    }
  }
}

$node_id = 0;
$revision_id = 0;
module_load_include('inc', 'node', 'node.pages');
for ($i = 0; $i < 24; $i++) {
  $uid = intval($i / 8) + 3;
  $user = user_load($uid);
  $node = new stdClass();
  $node->uid = $uid;
  $node->type = $i < 12 ? 'page' : 'story';
  $node->sticky = 0;
  ++$node_id;
  ++$revision_id;
  $node->title = "node title $node_id rev $revision_id (i=$i)";
  $node->language = LANGUAGE_NONE;
  $body_text =  str_repeat("node body ($node->type) - $i", 100);
  $node->body[$node->language][0]['value'] = $body_text;
  $node->body[$node->language][0]['summary'] = text_summary($body_text);
  $node->body[$node->language][0]['format'] = 'filtered_html';
  $node->status = intval($i / 4) % 2;
  $node->revision = $i < 12;
  $node->promote = $i % 2;
  $node->created = $now + $i * 86400;
  $node->log = "added $i node";
  // Make every term association different a little. For nodes with revisions,
  // make the initial revision have a different set of terms than the
  // newest revision.
  $items = array();
  if (module_exists('taxonomy')) {
    if ($node->revision) {
      $node_terms = array($terms[$i], $terms[47-$i]);
    }
    else {
      $node_terms = $terms;
      unset($node_terms[$i], $node_terms[47 - $i]);
    }
    foreach ($node_terms as $tid) {
      $field_name = $term_vocabs[$tid];
      $node->{$field_name}[LANGUAGE_NONE][] = array('tid' => $tid);
    }
  }
  $node->path = array('alias' => "content/$node->created");
  node_save($node);
  if ($node->revision) {
    $user = user_load($uid + 3);
    ++$revision_id;
    $node->title .= " rev2 $revision_id";
    $body_text =  str_repeat("node revision body ($node->type) - $i", 100);
    $node->body[$node->language][0]['value'] = $body_text;
    $node->body[$node->language][0]['summary'] = text_summary($body_text);
    $node->body[$node->language][0]['format'] = 'filtered_html';
    $node->log = "added $i revision";
    $node_terms = $terms;
    unset($node_terms[$i], $node_terms[47 - $i]);
    foreach ($node_terms as $tid) {
      $field_name = $term_vocabs[$tid];
      $node->{$field_name}[LANGUAGE_NONE][] = array('tid' => $tid);
    }
    node_save($node);
  }
}

if (module_exists('poll')) {
  // Create poll content.
  for ($i = 0; $i < 12; $i++) {
    $uid = intval($i / 4) + 3;
    $user = user_load($uid);
    $node = new stdClass();
    $node->uid = $uid;
    $node->type = 'poll';
    $node->sticky = 0;
    $node->title = "poll title $i";
    $node->language = LANGUAGE_NONE;
    $node->status = intval($i / 2) % 2;
    $node->revision = 1;
    $node->promote = $i % 2;
    $node->created = REQUEST_TIME + $i * 43200;
    $node->runtime = 0;
    $node->active = 1;
    $node->log = "added $i poll";
    $node->path = array('alias' => "content/poll/$i");

    $nbchoices = ($i % 4) + 2;
    for ($c = 0; $c < $nbchoices; $c++) {
      $node->choice[] = array('chtext' => "Choice $c for poll $i", 'chvotes' => 0, 'weight' => 0);
    }
    node_save($node);
    $path = array(
      'alias' => "content/poll/$i/results",
      'source' => "node/$node->nid/results",
    );
    path_save($path);

    // Add some votes.
    $node = node_load($node->nid);
    $choices = array_keys($node->choice);
    $original_user = $GLOBALS['user'];
    for ($v = 0; $v < ($i % 4); $v++) {
      drupal_static_reset('ip_address');
      $_SERVER['REMOTE_ADDR'] = "127.0.$v.1";
      $GLOBALS['user'] = drupal_anonymous_user();// We should have already allowed anon to vote.
      $c = $v % $nbchoices;
      $form_state = array();
      $form_state['values']['choice'] = $choices[$c];
      $form_state['values']['op'] = t('Vote');
      drupal_form_submit('poll_view_voting', $form_state, $node);
    }
  }
}

// Test that upgrade works even on a bundle whose parent module was disabled.
// This is simulated by creating an existing content type and changing the
// bundle to another type through direct database update queries.
$node_type = 'broken';
$uid = 6;
$user = user_load($uid);
$node = new stdClass();
$node->uid = $uid;
$node->type = 'article';
$body_text = str_repeat("node body ($node_type) - 37", 100);
$node->sticky = 0;
$node->title = "node title 24";
$node->language = LANGUAGE_NONE;
$node->body[$node->language][0]['value'] = $body_text;
$node->body[$node->language][0]['summary'] = text_summary($body_text);
$node->body[$node->language][0]['format']  = 'filtered_html';
$node->status = 1;
$node->revision = 0;
$node->promote = 0;
$node->created = 1263769200;
$node->log = "added a broken node";
$node->path = array('alias' => "content/1263769200");
node_save($node);
db_update('node')
  ->fields(array(
    'type' => $node_type,
  ))
  ->condition('nid', $node->nid)
  ->execute();
if (db_table_exists('field_data_body')) {
  db_update('field_data_body')
    ->fields(array(
      'bundle' => $node_type,
    ))
    ->condition('entity_id', $node->nid)
    ->condition('entity_type', 'node')
    ->execute();
  db_update('field_revision_body')
    ->fields(array(
      'bundle' => $node_type,
    ))
    ->condition('entity_id', $node->nid)
    ->condition('entity_type', 'node')
    ->execute();
}
db_update('field_config_instance')
  ->fields(array(
    'bundle' => $node_type,
  ))
  ->condition('bundle', 'article')
  ->execute();

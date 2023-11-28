<?php

/**
 * @file
 * Test fixture.
 */

use Drupal\Core\Database\Database;
use Drupal\Core\Serialization\Yaml;

$connection = Database::getConnection();

$format_list_ol_start = Yaml::decode(file_get_contents(__DIR__ . '/filter.format.test_format_list_ol_start.yml'));
$format_list_ol_start_post_3261599 = Yaml::decode(file_get_contents(__DIR__ . '/filter.format.test_format_list_ol_start_post_3261599.yml'));
$connection->insert('config')
  ->fields([
    'collection',
    'name',
    'data',
  ])
  ->values([
    'collection' => '',
    'name' => 'filter.format.test_format_list_ol_start',
    'data' => serialize($format_list_ol_start),
  ])
  ->values([
    'collection' => '',
    'name' => 'filter.format.test_format_list_ol_start_post_3261599',
    'data' => serialize($format_list_ol_start_post_3261599),
  ])
  ->execute();

$editor_list_ol_start = Yaml::decode(file_get_contents(__DIR__ . '/editor.editor.test_format_list_ol_start.yml'));
$editor_list_ol_start_post_3261599 = Yaml::decode(file_get_contents(__DIR__ . '/editor.editor.test_format_list_ol_start_post_3261599.yml'));
$connection->insert('config')
  ->fields([
    'collection',
    'name',
    'data',
  ])
  ->values([
    'collection' => '',
    'name' => 'editor.editor.test_format_list_ol_start',
    'data' => serialize($editor_list_ol_start),
  ])
  ->values([
    'collection' => '',
    'name' => 'editor.editor.test_format_list_ol_start_post_3261599',
    'data' => serialize($editor_list_ol_start_post_3261599),
  ])
  ->execute();

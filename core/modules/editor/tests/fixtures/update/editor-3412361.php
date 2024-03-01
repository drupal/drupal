<?php

/**
 * @file
 * Test fixture.
 */

use Drupal\Core\Database\Database;
use Drupal\Core\Serialization\Yaml;

$connection = Database::getConnection();

$umami_basic_html_format = Yaml::decode(file_get_contents(__DIR__ . '/filter.format.umami_basic_html.yml'));
$umami_basic_html_format['format'] = 'umami_basic_html';
$connection->insert('config')
  ->fields([
    'collection',
    'name',
    'data',
  ])
  ->values([
    'collection' => '',
    'name' => 'filter.format.umami_basic_html',
    'data' => serialize($umami_basic_html_format),
  ])
  ->execute();

$umami_basic_html_editor = Yaml::decode(file_get_contents(__DIR__ . '/editor.editor.umami_basic_html.yml'));
$umami_basic_html_editor['format'] = 'umami_basic_html';
$connection->insert('config')
  ->fields([
    'collection',
    'name',
    'data',
  ])
  ->values([
    'collection' => '',
    'name' => 'editor.editor.umami_basic_html',
    'data' => serialize($umami_basic_html_editor),
  ])
  ->execute();

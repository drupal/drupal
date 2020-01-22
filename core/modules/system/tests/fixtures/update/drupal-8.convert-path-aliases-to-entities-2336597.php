<?php
// @codingStandardsIgnoreFile

/**
 * @file
 * Contains database additions to drupal-8.filled.standard.php.gz for testing
 * the upgrade path of https://www.drupal.org/node/2336597.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

// Add a few more url aliases with various language codes.
$connection->insert('url_alias')
->fields([
  'pid',
  'source',
  'alias',
  'langcode',
])
->values([
  'pid' => '2',
  'source' => '/node/1',
  'alias' => '/test-article-new-alias',
  'langcode' => 'und',
])
->values([
  'pid' => '3',
  'source' => '/node/8',
  'alias' => '/test-alias-for-any-language',
  'langcode' => 'und',
])
->values([
  'pid' => '4',
  'source' => '/node/8',
  'alias' => '/test-alias-in-english',
  'langcode' => 'en',
])
->values([
  'pid' => '5',
  'source' => '/node/8',
  'alias' => '/test-alias-in-spanish',
  'langcode' => 'es',
])
->execute();

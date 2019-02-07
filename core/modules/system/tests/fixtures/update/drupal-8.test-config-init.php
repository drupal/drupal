<?php
// @codingStandardsIgnoreFile

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

// Manually configure the test mail collector implementation to prevent
// tests from sending out emails and collect them in state instead.
// While this should be enforced via settings.php prior to installation,
// some tests expect to be able to test mail system implementations.
$config = $connection->select('config')
  ->fields('config', ['data'])
  ->condition('collection', '')
  ->condition('name', 'system.mail')
  ->execute()
  ->fetchField();
$config = unserialize($config);
$config['interface']['default'] = 'test_mail_collector';
$connection->update('config')
  ->fields([
    'data' => serialize($config),
    'collection' => '',
    'name' => 'system.mail',
  ])
  ->condition('collection', '')
  ->condition('name', 'system.mail')
  ->execute();

// By default, verbosely display all errors and disable all production
// environment optimizations for all tests to avoid needless overhead and
// ensure a sane default experience for test authors.
// @see https://www.drupal.org/node/2259167
$config = $connection->select('config')
  ->fields('config', ['data'])
  ->condition('collection', '')
  ->condition('name', 'system.logging')
  ->execute()
  ->fetchField();
$config = unserialize($config);
$config['error_level'] = 'verbose';
$connection->update('config')
  ->fields([
    'data' => serialize($config),
    'collection' => '',
    'name' => 'system.logging',
  ])
  ->condition('collection', '')
  ->condition('name', 'system.logging')
  ->execute();

$config = $connection->select('config')
  ->fields('config', ['data'])
  ->condition('collection', '')
  ->condition('name', 'system.performance')
  ->execute()
  ->fetchField();
$config = unserialize($config);
$config['css']['preprocess'] = FALSE;
$config['js']['preprocess'] = FALSE;
$connection->update('config')
  ->fields([
    'data' => serialize($config),
    'collection' => '',
    'name' => 'system.performance',
  ])
  ->condition('collection', '')
  ->condition('name', 'system.performance')
  ->execute();

// Set an explicit time zone to not rely on the system one, which may vary
// from setup to setup. The Australia/Sydney time zone is chosen so all
// tests are run using an edge case scenario (UTC10 and DST). This choice
// is made to prevent time zone related regressions and reduce the
// fragility of the testing system in general.
$config = $connection->select('config')
  ->fields('config', ['data'])
  ->condition('collection', '')
  ->condition('name', 'system.date')
  ->execute()
  ->fetchField();
$config = unserialize($config);
$config['timezone']['default'] = 'Australia/Sydney';
$connection->update('config')
  ->fields([
    'data' => serialize($config),
    'collection' => '',
    'name' => 'system.date',
  ])
  ->condition('collection', '')
  ->condition('name', 'system.date')
  ->execute();

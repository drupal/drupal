<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Upgrade\MigrateTestBase.
 */

namespace Drupal\migrate\Tests;

use Drupal\Core\Database\Database;
use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\simpletest\WebTestBase;

class MigrateTestBase extends WebTestBase {

  /**
   * The file path(s) to the dumped database(s) to load into the child site.
   *
   * @var array
   */
  public $databaseDumpFiles = array();

  public static $modules = array('migrate');

  /**
   * @param MigrationInterface $migration
   * @param array $files
   *
   * @return \Drupal\Core\Database\Connection
   */
  protected function prepare(MigrationInterface $migration, array $files = array()) {
    $databasePrefix = 'm_';
    $connection_info = Database::getConnectionInfo('default');
    foreach ($connection_info as $target => $value) {
      $connection_info[$target]['prefix'] = array(
        'default' => $value['prefix']['default'] . $databasePrefix,
      );
    }
    $database = SqlBase::getDatabaseConnection($migration->id(), array('database' => $connection_info['default']));
    foreach (array('source', 'destination', 'idMap') as $key) {
      $configuration = $migration->get($key);
      $configuration['database'] = $database;
      $migration->set($key, $configuration);
    }

    // Load the database from the portable PHP dump.
    // The files may be gzipped.
    foreach ($files as $file) {
      if (substr($file, -3) == '.gz') {
        $file = "compress.zlib://$file";
        require $file;
      }
      preg_match('/^namespace (.*);$/m', file_get_contents($file), $matches);
      $class = $matches[1] . '\\' . basename($file, '.php');
      $class::load($database);
    }
    return $database;
  }
}

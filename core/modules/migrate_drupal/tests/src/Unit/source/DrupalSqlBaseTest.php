<?php

namespace Drupal\Tests\migrate_drupal\Unit\source;

use Drupal\Tests\migrate\Unit\MigrateTestCase;
use Drupal\migrate\Exception\RequirementsException;

/**
 * @coversDefaultClass Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase
 * @group migrate_drupal
 */
class DrupalSqlBaseTest extends MigrateTestCase {

  /**
   * Define bare minimum migration configuration.
   */
  protected $migrationConfiguration = array(
    'id' => 'DrupalSqlBase',
  );

  /**
   * @var \Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase
  */
  protected $base;

  /**
   * Minimum database contents needed to test DrupalSqlBase.
   */
  protected $databaseContents = array(
    'system' => array(
      array(
        'filename' => 'sites/all/modules/module1',
        'name' => 'module1',
        'type' => 'module',
        'status' => 0,
        'schema_version' => -1,
      ),
    ),
  );

  /**
   * @covers ::checkRequirements
   */
  public function testSourceProviderNotActive() {
    $plugin_definition['requirements_met'] = TRUE;
    $plugin_definition['source_provider'] = 'module1';
    /** @var \Drupal\Core\State\StateInterface $state */
    $state = $this->getMock('Drupal\Core\State\StateInterface');
    /** @var \Drupal\Core\Entity\EntityManagerInterface $entity_manager */
    $entity_manager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');
    $plugin = new TestDrupalSqlBase([], 'placeholder_id', $plugin_definition, $this->getMigration(), $state, $entity_manager);
    $plugin->setDatabase($this->getDatabase($this->databaseContents));
    $system_data = $plugin->getSystemData();
    $this->setExpectedException(RequirementsException::class, 'The module module1 is not enabled in the source site.');
    try {
      $plugin->checkRequirements();
    }
    catch (RequirementsException $e) {
      // Ensure requirements are set on the exception.
      $this->assertEquals(['source_provider' => 'module1'], $e->getRequirements());
      // Re-throw so PHPUnit can assert the exception.
      throw $e;
    }
  }

}

namespace Drupal\Tests\migrate_drupal\Unit\source;

use Drupal\Core\Database\Connection;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Extends the DrupalSqlBase abstract class.
 */
class TestDrupalSqlBase extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
  }

  /**
   * Tweaks DrupalSqlBase to set a new database connection for tests.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The new connection to use.
   *
   * @see \Drupal\Tests\migrate\Unit\MigrateSourceSqlTestCase
   */
  public function setDatabase(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [];
  }

}

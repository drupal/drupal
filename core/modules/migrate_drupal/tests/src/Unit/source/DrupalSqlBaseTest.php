<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate_drupal\Unit\source;

use Drupal\Core\Database\Connection;
use Drupal\Tests\migrate\Unit\MigrateTestCase;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * @coversDefaultClass \Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase
 * @group migrate_drupal
 */
class DrupalSqlBaseTest extends MigrateTestCase {

  /**
   * Define bare minimum migration configuration.
   *
   * @var string[]
   */
  protected $migrationConfiguration = [
    'id' => 'DrupalSqlBase',
  ];

  /**
   * @var \Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase
   */
  protected $base;

  /**
   * The plugin definition.
   *
   * @var array
   */
  protected $pluginDefinition = [];

  /**
   * Mock StateInterface.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected $state;

  /**
   * Mock entity type manager.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * Minimum database contents needed to test DrupalSqlBase.
   *
   * @var string[]
   */
  protected $databaseContents = [
    'system' => [
      [
        'filename' => 'sites/all/modules/module1',
        'name' => 'module1',
        'type' => 'module',
        'status' => 0,
        'schema_version' => -1,
      ],
    ],
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->pluginDefinition['requirements_met'] = TRUE;
    $this->pluginDefinition['source_module'] = 'module1';
    $this->state = $this->createMock('Drupal\Core\State\StateInterface');
    $this->entityTypeManager = $this->createMock('Drupal\Core\Entity\EntityTypeManagerInterface');
  }

  /**
   * @covers ::checkRequirements
   */
  public function testSourceProviderNotActive(): void {
    $plugin = new TestDrupalSqlBase([], 'placeholder_id', $this->pluginDefinition, $this->getMigration(), $this->state, $this->entityTypeManager);
    $plugin->setDatabase($this->getDatabase($this->databaseContents));
    $this->expectException(RequirementsException::class);
    $this->expectExceptionMessage('The module module1 is not enabled in the source site.');
    try {
      $plugin->checkRequirements();
    }
    catch (RequirementsException $e) {
      // Ensure requirements are set on the exception.
      $this->assertEquals(['source_module' => 'module1'], $e->getRequirements());
      // Re-throw so PHPUnit can assert the exception.
      throw $e;
    }
  }

  /**
   * @covers ::checkRequirements
   */
  public function testSourceDatabaseError(): void {
    $plugin = new TestDrupalSqlBase([], 'test', $this->pluginDefinition, $this->getMigration(), $this->state, $this->entityTypeManager);
    $this->expectException(RequirementsException::class);
    $this->expectExceptionMessage('No database connection configured for source plugin test');
    $plugin->checkRequirements();
  }

  /**
   * @covers ::checkRequirements
   *
   * @param bool $success
   *   True if this test will not throw an exception.
   * @param null|string $minimum_version
   *   The minimum version declared in the configuration of a source plugin.
   * @param string $schema_version
   *   The schema version for the source module declared in a source plugin.
   *
   * @dataProvider providerMinimumVersion
   */
  public function testMinimumVersion($success, $minimum_version, $schema_version): void {
    $this->pluginDefinition['minimum_version'] = $minimum_version;
    $this->databaseContents['system'][0]['status'] = 1;
    $this->databaseContents['system'][0]['schema_version'] = $schema_version;
    $plugin = new TestDrupalSqlBase([], 'test', $this->pluginDefinition, $this->getMigration(), $this->state, $this->entityTypeManager);
    $plugin->setDatabase($this->getDatabase($this->databaseContents));
    $this->assertSame([], $plugin->fields());

    if (!$success) {
      $this->expectException(RequirementsException::class);
      $this->expectExceptionMessage("Required minimum version $minimum_version");
    }

    $plugin->checkRequirements();
  }

  /**
   * Provides data for testMinimumVersion.
   */
  public static function providerMinimumVersion() {
    return [
      'minimum less than schema' => [
        TRUE,
        '7000',
        '7001',
      ],
      'same version' => [
        TRUE,
        '7001',
        '7001',
      ],
      'minimum greater than schema' => [
        FALSE,
        '7005',
        '7001',
      ],
      'schema version 0' => [
        FALSE,
        '7000',
        '0',
      ],
      'schema version -1' => [
        FALSE,
        '7000',
        '-1',
      ],
      'minimum not set' => [
        TRUE,
        NULL,
        '-1',
      ],
    ];
  }

}

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
    throw new \RuntimeException(__METHOD__ . " not implemented for " . __CLASS__);
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

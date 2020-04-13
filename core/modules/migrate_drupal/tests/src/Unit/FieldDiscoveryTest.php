<?php

namespace Drupal\Tests\migrate_drupal\Unit;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\field_discovery_test\FieldDiscoveryTestClass;
use Drupal\migrate_drupal\FieldDiscoveryInterface;
use Drupal\migrate_drupal\Plugin\MigrateFieldPluginManagerInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the FieldDiscovery Class.
 *
 * @group migrate_drupal
 * @coversDefaultClass \Drupal\migrate_drupal\FieldDiscovery
 */
class FieldDiscoveryTest extends UnitTestCase {

  /**
   * A MigrateFieldPluginManager prophecy.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $fieldPluginManager;

  /**
   * A MigrationPluginManager prophecy.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $migrationPluginManager;

  /**
   * A LoggerChannelInterface prophecy.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->fieldPluginManager = $this->prophesize(MigrateFieldPluginManagerInterface::class);
    $this->migrationPluginManager = $this->prophesize(MigrationPluginManagerInterface::class);
    $this->logger = $this->prophesize(LoggerChannelInterface::class);
  }

  /**
   * Tests the protected getEntityFields method.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param array $expected_fields
   *   The expected fields.
   *
   * @covers ::getEntityFields
   * @dataProvider getEntityFieldsData
   */
  public function testGetEntityFields($entity_type_id, array $expected_fields) {
    $test_data = [
      'getAllFields' => [
        '7' => $this->getAllFieldData(),
      ],
    ];
    $field_discovery = new FieldDiscoveryTestClass($this->fieldPluginManager->reveal(), $this->migrationPluginManager->reveal(), $this->logger->reveal(), $test_data);
    $actual_fields = $field_discovery->getEntityFields('7', $entity_type_id);
    $this->assertSame($expected_fields, $actual_fields);
  }

  /**
   * Provides data for testGetEntityFields.
   *
   * @return array
   *   The data.
   */
  public function getEntityFieldsData() {
    return [
      'Node' => [
        'entity_type_id' => 'node',
        'expected_fields' => [
          'content_type_1' => [
            'field_1' => ['field_info_key' => 'field_1_data'],
            'field_2' => ['field_info_key' => 'field_2_data'],
            'field_3' => ['field_info_key' => 'field_3_data'],
          ],
          'content_type_2' => [
            'field_1' => ['field_info_key' => 'field_1_data'],
            'field_4' => ['field_info_key' => 'field_4_data'],
            'field_5' => ['field_info_key' => 'field_5_data'],
          ],
        ],
      ],
      'User' => [
        'entity_type_id' => 'user',
        'expected_fields' => [
          'user' => [
            'user_field_1' => ['field_info_key' => 'user_field_1_data'],
          ],
        ],
      ],
      'Comment' => [
        'entity_type_id' => 'comment',
        'expected_fields' => [
          'comment_node_content_type_1' => [
            'cfield_1' => ['field_info_key' => 'field_1_data'],
            'cfield_2' => ['field_info_key' => 'field_2_data'],
            'cfield_3' => ['field_info_key' => 'field_3_data'],
          ],
          'comment_node_content_type_2' => [
            'cfield_1' => ['field_info_key' => 'field_1_data'],
            'cfield_4' => ['field_info_key' => 'field_4_data'],
            'cfield_5' => ['field_info_key' => 'field_5_data'],
          ],
        ],
      ],
      'Non-existent Entity' => [
        'entity_type_id' => 'custom_entity',
        'expected_fields' => [],
      ],
    ];
  }

  /**
   * Tests the protected getEntityFields method.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The bundle.
   * @param array $expected_fields
   *   The expected fields.
   *
   * @covers ::getBundleFields
   * @dataProvider getBundleFieldsData
   */
  public function testGetBundleFields($entity_type_id, $bundle, array $expected_fields) {
    $test_data = [
      'getAllFields' => [
        '7' => $this->getAllFieldData(),
      ],
    ];
    $field_discovery = new FieldDiscoveryTestClass($this->fieldPluginManager->reveal(), $this->migrationPluginManager->reveal(), $this->logger->reveal(), $test_data);
    $actual_fields = $field_discovery->getBundleFields('7', $entity_type_id, $bundle);
    $this->assertSame($expected_fields, $actual_fields);
  }

  /**
   * Provides data for testGetBundleFields.
   *
   * @return array
   *   The data.
   */
  public function getBundleFieldsData() {
    return [
      'Node - Content Type 1' => [
        'entity_type_id' => 'node',
        'bundle' => 'content_type_1',
        'expected_fields' => [
          'field_1' => ['field_info_key' => 'field_1_data'],
          'field_2' => ['field_info_key' => 'field_2_data'],
          'field_3' => ['field_info_key' => 'field_3_data'],
        ],
      ],
      'Node - Content Type 2' => [
        'entity_type_id' => 'node',
        'bundle' => 'content_type_2',
        'expected_fields' => [
          'field_1' => ['field_info_key' => 'field_1_data'],
          'field_4' => ['field_info_key' => 'field_4_data'],
          'field_5' => ['field_info_key' => 'field_5_data'],
        ],
      ],
      'User' => [
        'entity_type_id' => 'user',
        'bundle' => 'user',
        'expected_fields' => [
            'user_field_1' => ['field_info_key' => 'user_field_1_data'],
        ],
      ],
      'Comment - Content Type 1' => [
        'entity_type_id' => 'comment',
        'bundle' => 'comment_node_content_type_1',
        'expected_fields' => [
          'cfield_1' => ['field_info_key' => 'field_1_data'],
          'cfield_2' => ['field_info_key' => 'field_2_data'],
          'cfield_3' => ['field_info_key' => 'field_3_data'],
        ],
      ],
      'Comment - Content Type 2' => [
        'entity_type_id' => 'comment',
        'bundle' => 'comment_node_content_type_2',
        'expected_fields' => [
          'cfield_1' => ['field_info_key' => 'field_1_data'],
          'cfield_4' => ['field_info_key' => 'field_4_data'],
          'cfield_5' => ['field_info_key' => 'field_5_data'],
        ],
      ],
      'Non-existent Entity Type' => [
        'entity_type_id' => 'custom_entity',
        'bundle' => 'content_type_1',
        'expected_fields' => [],
      ],
      'Non-existent Bundle' => [
        'entity_type_id' => 'node',
        'bundle' => 'content_type_3',
        'expected_fields' => [],
      ],
    ];
  }

  /**
   * Test the protected getCoreVersion method.
   *
   * @param string[] $tags
   *   The migration tags.
   * @param string|bool $expected_result
   *   The expected return value of the method.
   *
   * @covers ::getCoreVersion
   * @dataProvider getCoreVersionData
   */
  public function testGetCoreVersion(array $tags, $expected_result) {
    $migration = $this->prophesize(MigrationInterface::class);
    $migration->getMigrationTags()->willReturn($tags);
    $field_discovery = new FieldDiscoveryTestClass($this->fieldPluginManager->reveal(), $this->migrationPluginManager->reveal(), $this->logger->reveal());
    if (!$expected_result) {
      $this->expectException(\InvalidArgumentException::class);
    }
    $actual_result = $field_discovery->getCoreVersion($migration->reveal());
    $this->assertEquals($expected_result, $actual_result);
  }

  /**
   * Provides data for testGetCoreVersion()
   *
   * @return array
   *   The test data.
   */
  public function getCoreVersionData() {
    return [
      'Drupal 7' => [
        'tags' => ['Drupal 7'],
        'result' => '7',
      ],
      'Drupal 6' => [
        'tags' => ['Drupal 6'],
        'result' => '6',
      ],
      'D7 with others' => [
        'tags' => ['Drupal 7', 'Translation', 'Other Tag'],
        'result' => '7',
      ],
      'Both (d7 has priority)' => [
        'tags' => ['Drupal 6', 'Drupal 7'],
        'result' => '7',
      ],
      'Neither' => [
        'tags' => ['drupal 6', 'Drupal_6', 'This contains Drupal 7 but is not'],
        'result' => FALSE,
      ],
    ];
  }

  /**
   * Returns dummy data to test the field getters.
   */
  protected function getAllFieldData() {
    return [
      'node' => [
        'content_type_1' => [
          'field_1' => ['field_info_key' => 'field_1_data'],
          'field_2' => ['field_info_key' => 'field_2_data'],
          'field_3' => ['field_info_key' => 'field_3_data'],
        ],
        'content_type_2' => [
          'field_1' => ['field_info_key' => 'field_1_data'],
          'field_4' => ['field_info_key' => 'field_4_data'],
          'field_5' => ['field_info_key' => 'field_5_data'],
        ],
      ],
      'user' => [
        'user' => [
          'user_field_1' => ['field_info_key' => 'user_field_1_data'],
        ],
      ],
      'comment' => [
        'comment_node_content_type_1' => [
          'cfield_1' => ['field_info_key' => 'field_1_data'],
          'cfield_2' => ['field_info_key' => 'field_2_data'],
          'cfield_3' => ['field_info_key' => 'field_3_data'],
        ],
        'comment_node_content_type_2' => [
          'cfield_1' => ['field_info_key' => 'field_1_data'],
          'cfield_4' => ['field_info_key' => 'field_4_data'],
          'cfield_5' => ['field_info_key' => 'field_5_data'],
        ],
      ],
    ];
  }

  /**
   * Tests the getFieldInstanceStubMigration method.
   *
   * @param mixed $core
   *   The Drupal core version.
   * @param array|bool $expected_definition
   *   The expected migration definition, or false if an exception is expected.
   *
   * @covers ::getFieldInstanceStubMigrationDefinition
   * @dataProvider getFieldInstanceStubMigrationDefinition
   */
  public function testGetFieldInstanceStubMigrationDefinition($core, $expected_definition) {
    $field_discovery = new FieldDiscoveryTestClass($this->fieldPluginManager->reveal(), $this->migrationPluginManager->reveal(), $this->logger->reveal());
    if (!$expected_definition) {
      $this->expectException(\InvalidArgumentException::class);
      $this->expectExceptionMessage(sprintf("Drupal version %s is not supported. Valid values for Drupal core version are '6' and '7'.", $core));
    }
    $actual_definition = $field_discovery->getFieldInstanceStubMigrationDefinition($core);
    $this->assertSame($expected_definition, $actual_definition);
  }

  /**
   * Provides data for testGetFieldInstanceStubMigrationDefinition.
   *
   * @return array
   *   The data.
   */
  public function getFieldInstanceStubMigrationDefinition() {
    return [
      'Drupal 6' => [
        'core' => FieldDiscoveryInterface::DRUPAL_6,
        'expected_definition' => [
          'destination' => ['plugin' => 'null'],
          'idMap' => ['plugin' => 'null'],
          'source' => [
            'ignore_map' => TRUE,
            'plugin' => 'd6_field_instance',
          ],
        ],
      ],
      'Drupal 7' => [
        'core' => FieldDiscoveryInterface::DRUPAL_7,
        'expected_definition' => [
          'destination' => ['plugin' => 'null'],
          'idMap' => ['plugin' => 'null'],
          'source' => [
            'ignore_map' => TRUE,
            'plugin' => 'd7_field_instance',
          ],
        ],
      ],
    ];
  }

}

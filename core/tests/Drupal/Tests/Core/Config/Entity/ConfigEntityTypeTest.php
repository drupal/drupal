<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Config\Entity;

use Drupal\Core\Config\Entity\ConfigEntityType;
use Drupal\Core\Config\Entity\Exception\ConfigEntityStorageClassException;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Drupal\Core\Config\Entity\ConfigEntityType.
 */
#[CoversClass(ConfigEntityType::class)]
#[Group('Config')]
class ConfigEntityTypeTest extends UnitTestCase {

  /**
   * The mocked typed config manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $typedConfigManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->typedConfigManager = $this->createMock(TypedConfigManagerInterface::class);
    $container = new ContainerBuilder();
    $container->set('config.typed', $this->typedConfigManager);
    \Drupal::setContainer($container);
  }

  /**
   * Sets up a ConfigEntityType object for a given set of values.
   *
   * @param array $definition
   *   An array of values to use for the ConfigEntityType.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityTypeInterface
   *   The ConfigEntityType object.
   */
  protected function setUpConfigEntityType($definition): ConfigEntityType {
    if (!isset($definition['id'])) {
      $definition += [
        'id' => 'example_config_entity_type',
      ];
    }
    return new ConfigEntityType($definition);
  }

  /**
   * Tests when the prefix length exceeds the maximum defined prefix length.
   *
   * Tests that we get an exception when the length of the config prefix that is
   * returned by getConfigPrefix() exceeds the maximum defined prefix length.
   *
   * @legacy-covers ::getConfigPrefix
   */
  public function testConfigPrefixLengthExceeds(): void {
    // A provider length of 24 and config_prefix length of 59 (+1 for the .)
    // results in a config length of 84, which is too long.
    $definition = [
      'provider' => $this->randomMachineName(24),
      'config_prefix' => $this->randomMachineName(59),
    ];
    $config_entity = $this->setUpConfigEntityType($definition);
    $this->expectException('\Drupal\Core\Config\ConfigPrefixLengthException');
    $this->expectExceptionMessage("The configuration file name prefix {$definition['provider']}.{$definition['config_prefix']} exceeds the maximum character limit of " . ConfigEntityType::PREFIX_LENGTH);
    $this->assertEmpty($config_entity->getConfigPrefix());
  }

  /**
   * Tests when the prefix length is valid.
   *
   * Tests that a valid config prefix returned by getConfigPrefix()
   * does not throw an exception and is formatted as expected.
   *
   * @legacy-covers ::getConfigPrefix
   */
  public function testConfigPrefixLengthValid(): void {
    // A provider length of 24 and config_prefix length of 58 (+1 for the .)
    // results in a config length of 83, which is right at the limit.
    $definition = [
      'provider' => $this->randomMachineName(24),
      'config_prefix' => $this->randomMachineName(58),
    ];
    $config_entity = $this->setUpConfigEntityType($definition);
    $expected_prefix = $definition['provider'] . '.' . $definition['config_prefix'];
    $this->assertEquals($expected_prefix, $config_entity->getConfigPrefix());
  }

  /**
   * Tests construct.
   *
   * @legacy-covers ::__construct
   */
  public function testConstruct(): void {
    $config_entity = new ConfigEntityType([
      'id' => 'example_config_entity_type',
    ]);
    $this->assertEquals('Drupal\Core\Config\Entity\ConfigEntityStorage', $config_entity->getStorageClass());
  }

  /**
   * Tests construct bad storage.
   *
   * @legacy-covers ::__construct
   */
  public function testConstructBadStorage(): void {
    $this->expectException(ConfigEntityStorageClassException::class);
    $this->expectExceptionMessage('\Drupal\Core\Entity\KeyValueStore\KeyValueEntityStorage is not \Drupal\Core\Config\Entity\ConfigEntityStorage or it does not extend it');
    new ConfigEntityType([
      'id' => 'example_config_entity_type',
      'handlers' => ['storage' => '\Drupal\Core\Entity\KeyValueStore\KeyValueEntityStorage'],
    ]);
  }

  /**
   * Tests set storage class.
   *
   * @legacy-covers ::setStorageClass
   */
  public function testSetStorageClass(): void {
    $config_entity = $this->setUpConfigEntityType([]);
    $this->expectException(ConfigEntityStorageClassException::class);
    $this->expectExceptionMessage('\Drupal\Core\Entity\KeyValueStore\KeyValueEntityStorage is not \Drupal\Core\Config\Entity\ConfigEntityStorage or it does not extend it');
    $config_entity->setStorageClass('\Drupal\Core\Entity\KeyValueStore\KeyValueEntityStorage');
  }

  /**
   * Tests the getConfigPrefix() method.
   *
   * @legacy-covers ::getConfigPrefix
   */
  #[DataProvider('providerTestGetConfigPrefix')]
  public function testGetConfigPrefix($definition, $expected): void {
    $entity_type = $this->setUpConfigEntityType($definition);
    $this->assertSame($expected, $entity_type->getConfigPrefix());
  }

  /**
   * Provides test data.
   */
  public static function providerTestGetConfigPrefix(): array {
    return [
      [['provider' => 'node', 'id' => 'node_type', 'config_prefix' => 'type'], 'node.type'],
      [['provider' => 'views', 'id' => 'view'], 'views.view'],
    ];
  }

  /**
   * Tests get properties to export.
   *
   * @legacy-covers ::getPropertiesToExport
   */
  #[DataProvider('providerGetPropertiesToExport')]
  public function testGetPropertiesToExport($definition, $expected): void {
    $entity_type = $this->setUpConfigEntityType($definition);
    $properties_to_export = $entity_type->getPropertiesToExport();
    $this->assertSame($expected, $properties_to_export);

    // Ensure the method is idempotent.
    $properties_to_export = $entity_type->getPropertiesToExport();
    $this->assertSame($expected, $properties_to_export);
  }

  public static function providerGetPropertiesToExport(): array {
    $data = [];
    $data[] = [
      [
        'config_export' => [
          'id',
          'custom_property' => 'customProperty',
        ],
      ],
      [
        'uuid' => 'uuid',
        'langcode' => 'langcode',
        'status' => 'status',
        'dependencies' => 'dependencies',
        'third_party_settings' => 'third_party_settings',
        '_core' => '_core',
        'id' => 'id',
        'custom_property' => 'customProperty',
      ],
    ];

    $data[] = [
      [
        'config_export' => [
          'id',
        ],
        'mergedConfigExport' => [
          'random_key' => 'random_key',
        ],
      ],
      [
        'random_key' => 'random_key',
      ],
    ];
    return $data;
  }

  /**
   * Tests get properties to export no fallback.
   *
   * @legacy-covers ::getPropertiesToExport
   */
  public function testGetPropertiesToExportNoFallback(): void {
    $config_entity_type = new ConfigEntityType([
      'id' => 'example_config_entity_type',
    ]);
    $this->assertNull($config_entity_type->getPropertiesToExport());
  }

}

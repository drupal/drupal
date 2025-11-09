<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Kernel\Plugin\source;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate\Plugin\migrate\source\ContentEntity;
use Drupal\migrate\Plugin\MigrationInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the constructor of the entity content source plugin.
 */
#[Group('migrate')]
#[RunTestsInSeparateProcesses]
class ContentEntityConstructorTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'user',
  ];

  /**
   * Tests the constructor.
   */
  #[DataProvider('providerTestConstructor')]
  public function testConstructor(array $configuration, array $plugin_definition, string $exception_class, string $expected): void {
    $migration = $this->prophesize(MigrationInterface::class)->reveal();
    $this->expectException($exception_class);
    $this->expectExceptionMessage($expected);
    ContentEntity::create($this->container, $configuration, 'content_entity', $plugin_definition, $migration);
  }

  /**
   * Provides data for constructor tests.
   */
  public static function providerTestConstructor(): array {
    return [
      'entity type missing' => [
        [],
        ['entity_type' => ''],
        InvalidPluginDefinitionException::class,
        'Missing required "entity_type" definition.',
      ],
      'non content entity' => [
        [],
        ['entity_type' => 'node_type'],
        InvalidPluginDefinitionException::class,
        'The entity type (node_type) is not supported. The "content_entity" source plugin only supports content entities.',
      ],
      'not bundleable' => [
        ['bundle' => 'foo'],
        ['entity_type' => 'user'],
        \InvalidArgumentException::class,
        'A bundle was provided but the entity type (user) is not bundleable.',
      ],
      'invalid bundle' => [
        ['bundle' => 'foo'],
        ['entity_type' => 'node'],
        \InvalidArgumentException::class,
        'The provided bundle (foo) is not valid for the (node) entity type.',
      ],
    ];
  }

}

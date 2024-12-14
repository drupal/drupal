<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Field;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Test\EventSubscriber\FieldStorageCreateCheckSubscriber;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Tests the field storage create check subscriber.
 *
 * @group Field
 * @group legacy
 */
class FieldStorageCreateCheckDeprecationTest extends KernelTestBase {

  /**
   * Modules to load.
   *
   * @var array
   */
  protected static $modules = ['entity_test', 'field'];

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    parent::register($container);

    // Change the service to act like this is a non-core test.
    $container->register('testing.field_storage_create_check', FieldStorageCreateCheckSubscriber::class)
      ->addArgument(new Reference('database'))
      ->addArgument(new Reference('entity_type.manager'))
      ->addArgument(FALSE)
      ->addTag('event_subscriber');
  }

  /**
   * Tests the field storage create check subscriber.
   */
  public function testFieldStorageCreateCheck(): void {
    $this->expectDeprecation('Creating the "entity_test.field_test" field storage definition without the entity schema "entity_test" being installed is deprecated in drupal:11.2.0 and will be replaced by a LogicException in drupal:12.0.0. See https://www.drupal.org/node/3493981');

    FieldStorageConfig::create([
      'field_name' => 'field_test',
      'entity_type' => 'entity_test',
      'type' => 'integer',
    ])->save();
  }

}

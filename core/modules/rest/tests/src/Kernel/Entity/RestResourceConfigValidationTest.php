<?php

namespace Drupal\Tests\rest\Kernel\Entity;

use Drupal\KernelTests\Core\Config\ConfigEntityValidationTestBase;
use Drupal\rest\Entity\RestResourceConfig;
use Drupal\rest\RestResourceConfigInterface;

/**
 * Tests validation of rest_resource_config entities.
 *
 * @group rest
 */
class RestResourceConfigValidationTest extends ConfigEntityValidationTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['rest', 'serialization'];

  /**
   * {@inheritdoc}
   */
  protected bool $hasLabel = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entity = RestResourceConfig::create([
      'id' => 'test',
      'plugin_id' => 'entity:date_format',
      'granularity' => RestResourceConfigInterface::METHOD_GRANULARITY,
      'configuration' => [],
    ]);
    $this->entity->save();
  }

}

<?php

declare(strict_types=1);

namespace Drupal\Tests\rest\Kernel\Entity;

use Drupal\KernelTests\Core\Config\ConfigEntityValidationTestBase;
use Drupal\rest\Entity\RestResourceConfig;
use Drupal\rest\RestResourceConfigInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests validation of rest_resource_config entities.
 */
#[Group('rest')]
#[Group('config')]
#[Group('Validation')]
#[RunTestsInSeparateProcesses]
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

  /**
   * Tests that the resource plugin ID is validated.
   */
  public function testInvalidPluginId(): void {
    $this->entity->set('plugin_id', 'non_existent');
    $this->assertValidationErrors([
      'plugin_id' => "The 'non_existent' plugin does not exist.",
    ]);
  }

}

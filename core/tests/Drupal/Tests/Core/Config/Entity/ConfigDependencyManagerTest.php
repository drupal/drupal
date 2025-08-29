<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Config\Entity;

use Drupal\Core\Config\Entity\ConfigDependencyManager;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the ConfigDependencyManager class.
 */
#[Group('Config')]
class ConfigDependencyManagerTest extends UnitTestCase {

  public function testNoConfiguration(): void {
    $dep_manger = new ConfigDependencyManager();
    $this->assertEmpty($dep_manger->getDependentEntities('config', 'config_test.dynamic.entity_id:745b0ce0-aece-42dd-a800-ade5b8455e84'));
  }

  public function testNoConfigEntities(): void {
    $dep_manger = new ConfigDependencyManager();
    $dep_manger->setData([
      'simple.config' => [
        'key' => 'value',
      ],
    ]);
    $this->assertEmpty($dep_manger->getDependentEntities('config', 'config_test.dynamic.entity_id:745b0ce0-aece-42dd-a800-ade5b8455e84'));

    // Configuration is always dependent on its provider.
    $dependencies = $dep_manger->getDependentEntities('module', 'simple');
    $this->assertArrayHasKey('simple.config', $dependencies);
    $this->assertCount(1, $dependencies);
  }

}

<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Unit\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\user\Entity\EntityPermissionsRouteProviderWithCheck;

/**
 * Tests the route provider deprecation.
 *
 * @coversDefaultClass \Drupal\user\Entity\EntityPermissionsRouteProviderWithCheck
 * @group user
 * @group legacy
 */
class EntityPermissionsRouteProviderWithCheckTest extends UnitTestCase {

  /**
   * Tests the route provider deprecation.
   *
   * @covers ::getEntityPermissionsRoute
   *
   * @group legacy
   */
  public function testEntityPermissionsRouteProviderWithCheck(): void {

    // Mock the constructor parameters.
    $prophecy = $this->prophesize(EntityTypeInterface::class);
    $entity_type = $prophecy->reveal();
    $prophecy = $this->prophesize(EntityTypeManagerInterface::class);
    $prophecy->getDefinition('entity_type')
      ->willReturn($entity_type);
    $entity_type_manager = $prophecy->reveal();

    $this->expectDeprecation('Drupal\user\Entity\EntityPermissionsRouteProviderWithCheck is deprecated in drupal:11.1.0 and is removed from drupal:12.0.0. Use EntityPermissionsRouteProvider instead. See https://www.drupal.org/node/3384745');
    (new EntityPermissionsRouteProviderWithCheck($entity_type_manager))
      ->getRoutes($entity_type);
  }

}

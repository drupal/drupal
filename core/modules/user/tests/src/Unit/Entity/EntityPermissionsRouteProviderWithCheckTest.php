<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Unit\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\user\Entity\EntityPermissionsRouteProviderWithCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;

/**
 * Tests the route provider deprecation.
 */
#[CoversClass(EntityPermissionsRouteProviderWithCheck::class)]
#[Group('user')]
#[IgnoreDeprecations]
class EntityPermissionsRouteProviderWithCheckTest extends UnitTestCase {

  /**
   * Tests the route provider deprecation.
   *
   * @legacy-covers ::getEntityPermissionsRoute
   */
  #[IgnoreDeprecations]
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

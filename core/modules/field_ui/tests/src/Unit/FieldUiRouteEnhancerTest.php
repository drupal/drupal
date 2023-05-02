<?php

namespace Drupal\Tests\field_ui\Unit;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\field_ui\Routing\FieldUiRouteEnhancer;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\field_ui\Routing\FieldUiRouteEnhancer
 *
 * @group field_ui
 * @group legacy
 */
class FieldUiRouteEnhancerTest extends UnitTestCase {

  /**
   * Tests deprecation of the Drupal\field_ui\Routing\FieldUiRouteEnhancer.
   */
  public function testDeprecation() {
    $this->expectDeprecation('The Drupal\field_ui\Routing\EntityBundleRouteEnhancer is deprecated in drupal:9.4.0 and is removed from drupal:10.0.0. Instead, use \Drupal\Core\Entity\Enhancer\EntityBundleRouteEnhancer. See https://www.drupal.org/node/3245017');
    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class)->reveal();
    $route_enhancer = new FieldUiRouteEnhancer($entity_type_manager);
  }

}

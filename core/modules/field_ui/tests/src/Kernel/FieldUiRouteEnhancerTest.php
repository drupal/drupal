<?php

namespace Drupal\Tests\field_ui\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests that the service "field_ui.route_enhancer" has been deprecated.
 *
 * @group field_ui
 * @group legacy
 */
class FieldUiRouteEnhancerTest extends KernelTestBase {

  /**
   * Modules to install.
   *
   * @var string[]
   */
  protected static $modules = ['field_ui'];

  /**
   * Tests deprecation of the "field_ui.route_enhancer" service.
   */
  public function testFieldUiRouteEnhancerDeprecation() {
    $this->expectDeprecation('The "field_ui.route_enhancer" service is deprecated in drupal:9.4.0 and is removed from drupal:10.0.0. Use the "route_enhancer.entity_bundle" service instead. See https://www.drupal.org/node/3245017');
    $legacy_service = \Drupal::service('field_ui.route_enhancer');
    $new_service = \Drupal::service('route_enhancer.entity_bundle');
    $this->assertSame($new_service, $legacy_service);
  }

}

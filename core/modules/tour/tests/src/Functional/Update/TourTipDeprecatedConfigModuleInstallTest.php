<?php

namespace Drupal\Tests\tour\Functional\Update;

use Drupal\Tests\BrowserTestBase;

/**
 * Confirms that legacy tour tips are updated when module config is imported.
 *
 * @group tour
 * @group legacy
 */
class TourTipDeprecatedConfigModuleInstallTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['tour'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test ensuring that tour config is updated on config import.
   */
  public function testModuleInstall() {
    $this->expectDeprecation("The tour.tip 'attributes' config schema property is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. Instead of 'data-class' and 'data-id' attributes, use 'selector' to specify the element a tip attaches to. See https://www.drupal.org/node/3204093");
    $this->expectDeprecation("The tour.tip 'location' config schema property is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. Instead use 'position'. The value must be a valid placement accepted by PopperJS. See https://www.drupal.org/node/3204093");

    $this->container->get('module_installer')->install(['tour_test', 'tour_legacy_test']);
    $updated_legacy_tour_config = $this->container->get('config.factory')->get('tour.tour.tour-test-legacy');
    $updated_tips = $updated_legacy_tour_config->get('tips');

    // Confirm that tour-test-1 uses `selector` instead of `data-id`.
    $this->assertSame('#tour-test-1', $updated_tips['tour-test-legacy-1']['selector']);
    $this->assertArrayNotHasKey('attributes', $updated_tips['tour-test-legacy-1']);

    // Confirm that tour-test-5 uses `selector` instead of `data-class`.
    $this->assertSame('.tour-test-5', $updated_tips['tour-test-legacy-6']['selector']);
    $this->assertArrayNotHasKey('attributes', $updated_tips['tour-test-legacy-6']);

    // Confirm that tour-test-legacy-7 uses `selector` instead of `data-class`.
    $this->assertSame('.tour-test-7', $updated_tips['tour-test-legacy-7']['selector']);
    $this->assertSame(['foo' => 'bar'], $updated_tips['tour-test-legacy-7']['attributes']);

    $updated_legacy_location_tour_config = $this->container->get('config.factory')->get('tour.tour.tour-test-legacy-location');
    $updated_location_tips = $updated_legacy_location_tour_config->get('tips');

    $this->assertSame('top-start', $updated_location_tips['location-test-top']['position']);
    $this->assertArrayNotHasKey('location', $updated_location_tips['location-test-top']);
    $this->assertEquals('bottom-start', $updated_location_tips['location-test-bottom']['position']);
    $this->assertArrayNotHasKey('location', $updated_location_tips['location-test-bottom']);
    $this->assertEquals('right-start', $updated_location_tips['location-test-right']['position']);
    $this->assertArrayNotHasKey('location', $updated_location_tips['location-test-right']);
    $this->assertEquals('left-start', $updated_location_tips['location-test-left']['position']);
    $this->assertArrayNotHasKey('location', $updated_location_tips['location-test-left']);
  }

}

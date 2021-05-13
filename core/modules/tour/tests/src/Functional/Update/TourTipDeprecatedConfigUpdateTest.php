<?php

namespace Drupal\Tests\tour\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Confirms tour tip deprecated config is updated properly.
 *
 * @group Update
 * @group legacy
 *
 * @see tour_update_9200()
 */
class TourTipDeprecatedConfigUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-9.0.0.bare.standard.php.gz',
    ];
  }

  /**
   * Confirm that tour_update_9200() populates the `selector` property.
   *
   * Joyride-based tours used the `data-id` and `data-class` attributes to
   * associate a tour tip with an element. This was changed to a `selector`
   * property. Existing tours are refactored to use this new property via
   * tour_update_9200(), and this test confirms it is done properly.
   */
  public function testSelectorUpdate() {
    $this->container->get('module_installer')->install(['tour', 'tour_test', 'tour_legacy_test']);

    $legacy_tour_config = $this->container->get('config.factory')->get('tour.tour.tour-test-legacy');
    $tips = $legacy_tour_config->get('tips');

    // Confirm the existing tours do not have the `selector` property.
    $this->assertFalse(isset($tips['tour-test-legacy-1']['selector']));
    $this->assertFalse(isset($tips['tour-test-legacy-6']['selector']));

    // Confirm the value of the tour-test-1 `data-id` attribute.
    $this->assertEquals('tour-test-1', $tips['tour-test-legacy-1']['attributes']['data-id']);

    // Confirm the value of the tour-test-5 `data-class` attribute.
    $this->assertEquals('tour-test-5', $tips['tour-test-legacy-6']['attributes']['data-class']);

    $legacy_location_tour_config = $this->container->get('config.factory')->get('tour.tour.tour-test-legacy-location');
    $tips = $legacy_location_tour_config->get('tips');

    $this->assertSame('top', $tips['location-test-top']['location']);
    $this->assertArrayNotHasKey('position', $tips['location-test-top']);
    $this->assertSame('bottom', $tips['location-test-bottom']['location']);
    $this->assertArrayNotHasKey('position', $tips['location-test-bottom']);
    $this->assertSame('right', $tips['location-test-right']['location']);
    $this->assertArrayNotHasKey('position', $tips['location-test-right']);
    $this->assertSame('left', $tips['location-test-left']['location']);
    $this->assertArrayNotHasKey('position', $tips['location-test-left']);

    $this->runUpdates();

    $updated_legacy_tour_config = $this->container->get('config.factory')->get('tour.tour.tour-test-legacy');
    $updated_tips = $updated_legacy_tour_config->get('tips');

    // Confirm that tour-test-1 uses `selector` instead of `data-id`.
    $this->assertSame('#tour-test-1', $updated_tips['tour-test-legacy-1']['selector']);
    $this->assertArrayNotHasKey('data-id', $updated_tips['tour-test-legacy-1']['attributes']);

    // Confirm that tour-test-5 uses `selector` instead of `data-class`.
    $this->assertSame('.tour-test-5', $updated_tips['tour-test-legacy-6']['selector']);
    $this->assertArrayNotHasKey('data-class', $updated_tips['tour-test-legacy-6']['attributes']);

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

<?php

namespace Drupal\Tests\tour\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Confirms tour tip deprecated config is updated properly.
 *
 * @group tour
 *
 * @see tour_post_update_joyride_selectors_to_selector_property()
 * @see tour_tour_presave()
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
   * Tests tour_post_update_joyride_selectors_to_selector_property().
   *
   * Confirms that tour_post_update_joyride_selectors_to_selector_property()
   * populates the `selector` and `location` properties.
   *
   * Joyride-based tours used the `data-id` and `data-class` attributes to
   * associate a tour tip with an element. This was changed to a `selector`
   * property.
   *
   * Joyride-based tours also used the `location` to configure the positioning
   * of the tour tip.
   *
   * Existing tours are updated to use this new property via
   * tour_post_update_joyride_selectors_to_selector_property(), and this test
   * confirms it is done properly.
   *
   * @see tour_tour_presave()
   */
  public function testUpdate() {
    $legacy_tour_config = $this->container->get('config.factory')->get('tour.tour.views-ui');
    $tips = $legacy_tour_config->get('tips');

    // Confirm the existing tour tip configurations match expectations.
    $this->assertFalse(isset($tips['views-ui-view-admin']['selector']));
    $this->assertEquals('views-display-extra-actions', $tips['views-ui-view-admin']['attributes']['data-id']);
    $this->assertEquals('views-ui-display-tab-bucket.format', $tips['views-ui-format']['attributes']['data-class']);
    $this->assertSame('left', $tips['views-ui-view-admin']['location']);
    $this->assertArrayNotHasKey('position', $tips['views-ui-view-admin']);

    $this->runUpdates();

    $updated_legacy_tour_config = $this->container->get('config.factory')->get('tour.tour.views-ui');
    $updated_tips = $updated_legacy_tour_config->get('tips');

    // Confirm that views-ui-view-admin uses `selector` instead of `data-id`.
    $this->assertSame('#views-display-extra-actions', $updated_tips['views-ui-view-admin']['selector']);

    // Confirm that views-ui-format uses `selector` instead of `data-class`.
    $this->assertSame('.views-ui-display-tab-bucket.format', $updated_tips['views-ui-format']['selector']);

    // Assert that the deprecated attributes key has been removed now that it is
    // empty.
    $this->assertArrayNotHasKey('attributes', $updated_tips['views-ui-view-admin']);

    $this->assertSame('left-start', $updated_tips['views-ui-view-admin']['position']);
    $this->assertArrayNotHasKey('location', $updated_tips['views-ui-view-admin']);
  }

}

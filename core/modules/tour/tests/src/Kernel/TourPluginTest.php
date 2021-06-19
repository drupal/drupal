<?php

namespace Drupal\Tests\tour\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\tour\Entity\Tour;
use PHPUnit\Framework\Error\Warning;

/**
 * Tests the functionality of tour plugins.
 *
 * @group tour
 */
class TourPluginTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['tour'];

  /**
   * Stores the tour plugin manager.
   *
   * @var \Drupal\tour\TipPluginManager
   */
  protected $pluginManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['tour']);
    $this->pluginManager = $this->container->get('plugin.manager.tour.tip');
  }

  /**
   * Tests tour plugins.
   */
  public function testTourPlugins() {
    $this->assertCount(1, $this->pluginManager->getDefinitions(), 'Only tour plugins for the enabled modules were returned.');
  }

  /**
   * Test that warnings and deprecations are triggered.
   *
   * @group legacy
   */
  public function testDeprecatedMethodWarningsErrors() {
    \Drupal::service('module_installer')->install(['tour_legacy_test']);
    $tip = Tour::load('tour-test')->getTips()[0];

    // These are E_USER_WARNING severity errors that supplement existing
    // deprecation errors. These warnings are triggered when methods are called
    // that are designed to be backwards compatible, but aren't able to 100%
    // promise this due to the many ways that tip plugins can be extended.
    try {
      $tip->getOutput();
      $this->fail('No getOutput() warning triggered.');
    }
    catch (Warning $e) {
      $this->assertSame('Drupal\tourTipPluginInterface::getOutput is deprecated. Use getBody() instead. See https://www.drupal.org/node/3204096', $e->getMessage());
    }

    try {
      $tip->getAttributes();
      $this->fail('No getAttributes() warning triggered.');
    }
    catch (Warning $e) {
      $this->assertSame('Drupal\tour\TipPluginInterface::getAttributes is deprecated. Tour tip plugins should implement Drupal\tour\TourTipPluginInterface and Tour configs should use the \'selector\' property instead of \'attributes\' to target an element.', $e->getMessage());
    }

    // Remove PHPUnits conversion of warning to exceptions.
    set_error_handler(function () {});
    $tip = Tour::load('tour-test-legacy')->getTips()[3];
    $attributes = $tip->getAttributes();
    restore_error_handler();
    $this->assertSame([
      'foo' => 'bar',
      'data-class' => 'tour-test-7',
      'data-aria-describedby' => 'tour-tip-tour-test-legacy-7-contents',
      'data-aria-labelledby' => 'tour-tip-tour-test-legacy-7-label',
    ], $attributes);

    $this->expectDeprecation('Implementing Drupal\tour\TipPluginInterface without also implementing Drupal\tour\TourTipPluginInterface is deprecated in drupal:9.2.0. See https://www.drupal.org/node/3204096');
    $this->expectDeprecation("The tour.tip 'attributes' config schema property is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. Instead of 'data-class' and 'data-id' attributes, use 'selector' to specify the element a tip attaches to. See https://www.drupal.org/node/3204093");
    $this->expectDeprecation("The tour.tip 'location' config schema property is deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. Instead use 'position'. The value must be a valid placement accepted by PopperJS. See https://www.drupal.org/node/3204093");

    try {
      \Drupal::entityTypeManager()
        ->getViewBuilder('tour')
        ->viewMultiple([Tour::load('tour-test-legacy')], 'full');
      $this->fail('No deprecated interface warning triggered.');
    }
    catch (Warning $e) {
      $this->assertSame('The tour tips only support data-class and data-id attributes and they will have to be upgraded manually. See https://www.drupal.org/node/3204093', $e->getMessage());
    }
  }

}

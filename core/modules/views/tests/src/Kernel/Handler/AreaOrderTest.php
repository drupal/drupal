<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Kernel\Handler;

use Drupal\Tests\block\Traits\BlockCreationTrait;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;

/**
 * Tests the view area handler.
 *
 * @group views
 * @see \Drupal\views\Plugin\views\area\View
 */
class AreaOrderTest extends ViewsKernelTestBase {

  use BlockCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user', 'block'];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_area_order'];

  /**
   * {@inheritdoc}
   */
  protected function setUpFixtures() {
    // Install the themes used for this test.
    $this->container->get('theme_installer')->install(['olivero']);

    $this->placeBlock('system_branding_block', [
      'id' => 'id_olivero_branding',
      'theme' => 'olivero',
      'plugin' => 'system_branding_block',
      'weight' => 1,
    ]);

    $this->placeBlock('system_powered_by_block', [
      'id' => 'id_olivero_powered',
      'theme' => 'olivero',
      'weight' => 2,
    ]);

    parent::setUpFixtures();
  }

  /**
   * Tests the order of the handlers.
   */
  public function testAreaOrder(): void {
    $view = Views::getView('test_area_order');
    $renderable = $view->buildRenderable();
    $output = $this->render($renderable);

    $position_powered = strpos($output, 'block-id-olivero-powered');
    $position_branding = strpos($output, 'block-id-olivero-branding');

    $this->assertNotEquals(0, $position_powered, 'ID olivero-powered found.');
    $this->assertNotEquals(0, $position_branding, 'ID olivero-branding found');

    // Make sure "powered" is before "branding", so it reflects the position
    // in the configuration, and not the weight of the blocks.
    $this->assertLessThan($position_branding, $position_powered);
  }

}

<?php

namespace Drupal\Tests\views\Kernel\Handler;

use Drupal\block\Entity\Block;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;
use Drupal\views\Views;

/**
 * Tests the view area handler.
 *
 * @group views
 * @see \Drupal\views\Plugin\views\area\View
 */
class AreaOrderTest extends ViewsKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('user', 'block');

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_area_order');

  /**
   * {@inheritdoc}
   */
  protected function setUpFixtures() {
    Block::create(
      [
        'id' => 'bartik_branding',
        'theme' => 'bartik',
        'plugin' => 'system_branding_block',
        'weight' => 1,
      ]
    )->save();

    Block::create(
      [
        'id' => 'bartik_powered',
        'theme' => 'bartik',
        'plugin' => 'system_powered_by_block',
        'weight' => 2,
      ]
    )->save();

    parent::setUpFixtures();
  }

  /**
   * Tests the order of the handlers.
   */
  public function testAreaOrder() {
    $renderer = $this->container->get('renderer');
    $view = Views::getView('test_area_order');
    $renderable = $view->buildRenderable();
    $output = $this->render($renderable);

    $position_powered = strpos($output, 'block-bartik-powered');
    $position_branding = strpos($output, 'block-bartik-branding');

    $this->assertNotEquals(0, $position_powered, 'ID bartik-powered found.');
    $this->assertNotEquals(0, $position_branding, 'ID bartik-branding found');

    // Make sure "powered" is before "branding", so it reflects the position
    // in the configuration, and not the weight of the blocks.
    $this->assertTrue($position_powered < $position_branding, 'Block bartik-powered is positioned before block bartik-branding');
  }

}

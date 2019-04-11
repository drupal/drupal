<?php

namespace Drupal\Tests\settings_tray\Kernel;

use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\block\Traits\BlockCreationTrait;

/**
 * Tests Settings Tray BC routes.
 *
 * @group settings_tray
 * @group legacy
 */
class BcRoutesTest extends KernelTestBase {

  use BlockCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'settings_tray',
    'system',
  ];

  /**
   * @expectedDeprecation The 'entity.block.off_canvas_form' route is deprecated since version 8.5.x and will be removed in 9.0.0. Use the 'entity.block.settings_tray_form' route instead.
   */
  public function testOffCanvasFormRouteBc() {
    $block = $this->placeBlock('system_powered_by_block');
    $url_for_current_route = Url::fromRoute('entity.block.settings_tray_form', ['block' => $block->id()])->toString(TRUE)->getGeneratedUrl();
    $url_for_bc_route = Url::fromRoute('entity.block.off_canvas_form', ['block' => $block->id()])->toString(TRUE)->getGeneratedUrl();
    $this->assertSame($url_for_current_route, $url_for_bc_route);
  }

}

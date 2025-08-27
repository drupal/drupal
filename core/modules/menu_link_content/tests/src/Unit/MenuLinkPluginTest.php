<?php

declare(strict_types=1);

namespace Drupal\Tests\menu_link_content\Unit;

use Drupal\menu_link_content\Plugin\Menu\MenuLinkContent;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Drupal\menu_link_content\Plugin\Menu\MenuLinkContent.
 */
#[CoversClass(MenuLinkContent::class)]
#[Group('Menu')]
class MenuLinkPluginTest extends UnitTestCase {

  /**
   * Tests get instance reflection.
   *
   * @legacy-covers ::getUuid
   */
  public function testGetInstanceReflection(): void {
    /** @var \Drupal\menu_link_content\Plugin\Menu\MenuLinkContent $menu_link_content_plugin */
    $menu_link_content_plugin = $this->prophesize(MenuLinkContent::class);
    $menu_link_content_plugin->getDerivativeId()->willReturn('test_id');
    $menu_link_content_plugin = $menu_link_content_plugin->reveal();

    $class = new \ReflectionClass(MenuLinkContent::class);
    $instance_method = $class->getMethod('getUuid');

    $this->assertEquals('test_id', $instance_method->invoke($menu_link_content_plugin));
  }

}

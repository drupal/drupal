<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Unit\Plugin\display;

use Drupal\Tests\UnitTestCase;
use Drupal\views\Plugin\views\display\Page;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Routing\Route;

/**
 * Tests Drupal\views\Plugin\views\display\Page.
 */
#[CoversClass(Page::class)]
#[Group('views')]
class PageTest extends UnitTestCase {

  /**
   * Tests build basic renderable.
   */
  public function testBuildBasicRenderable(): void {
    $route = new Route('/test-view');
    $route->setDefault('view_id', 'test_view');
    $route->setOption('_view_display_plugin_id', 'page');
    $route->setOption('_view_display_show_admin_links', TRUE);

    $result = Page::buildBasicRenderable('test_view', 'page_1', [], $route);

    $this->assertEquals('test_view', $result['#view_id']);
    $this->assertEquals('page', $result['#view_display_plugin_id']);
    $this->assertEquals(TRUE, $result['#view_display_show_admin_links']);
  }

  /**
   * Tests build basic renderable with missing route.
   */
  public function testBuildBasicRenderableWithMissingRoute(): void {
    $this->expectException(\BadFunctionCallException::class);
    Page::buildBasicRenderable('test_view', 'page_1', []);
  }

}

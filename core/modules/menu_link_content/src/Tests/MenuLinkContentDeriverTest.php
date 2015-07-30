<?php

/**
 * @file
 * Contains \Drupal\menu_link_content\Tests\MenuLinkContentDeriverTest.
 */

namespace Drupal\menu_link_content\Tests;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\StringTranslation\TranslationWrapper;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\simpletest\KernelTestBase;
use Symfony\Component\Routing\Route;

/**
 * Tests the menu link content deriver.
 *
 * @group menu_link_content
 */
class MenuLinkContentDeriverTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['menu_link_content', 'link', 'system', 'menu_link_content_dynamic_route'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('menu_link_content');
    $this->installSchema('system', 'router');
  }

  /**
   * Tests the rediscovering.
   */
  public function testRediscover() {
    \Drupal::state()->set('menu_link_content_dynamic_route.routes', [
      'route_name_1' => new Route('/example-path'),
    ]);
    \Drupal::service('router.builder')->rebuild();

    // Set up a custom menu link pointing to a specific path.
    MenuLinkContent::create([
      'title' => '<script>alert("Welcome to the discovered jungle!")</script>',
      'link' => [['uri' => 'internal:/example-path']],
      'menu_name' => 'tools',
    ])->save();
    $menu_tree = \Drupal::menuTree()->load('tools', new MenuTreeParameters());
    $this->assertEqual(1, count($menu_tree));
    /** @var \Drupal\Core\Menu\MenuLinkTreeElement $tree_element */
    $tree_element = reset($menu_tree);
    $this->assertEqual('route_name_1', $tree_element->link->getRouteName());

    // Change the underlying route and trigger the rediscovering.
    \Drupal::state()->set('menu_link_content_dynamic_route.routes', [
      'route_name_2' => new Route('/example-path'),
    ]);
    \Drupal::service('router.builder')->rebuild();

    // Ensure that the new route name / parameters are captured by the tree.
    $menu_tree = \Drupal::menuTree()->load('tools', new MenuTreeParameters());
    $this->assertEqual(1, count($menu_tree));
    /** @var \Drupal\Core\Menu\MenuLinkTreeElement $tree_element */
    $tree_element = reset($menu_tree);
    $this->assertEqual('route_name_2', $tree_element->link->getRouteName());
    $title = $tree_element->link->getTitle();
    $this->assertFalse($title instanceof TranslationWrapper);
    $this->assertIdentical('<script>alert("Welcome to the discovered jungle!")</script>', $title);
    $this->assertFalse(SafeMarkup::isSafe($title));
  }

}

<?php

namespace Drupal\Tests\menu_link_content\Kernel;

use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\KernelTests\KernelTestBase;
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
  public static $modules = [
    'menu_link_content',
    'link',
    'system',
    'menu_link_content_dynamic_route',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('menu_link_content');
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
    $parent = MenuLinkContent::create([
      'title' => '<script>alert("Welcome to the discovered jungle!")</script>',
      'link' => [['uri' => 'internal:/example-path']],
      'menu_name' => 'tools',
    ]);
    $parent->save();
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
    $this->assertFalse($title instanceof TranslatableMarkup);
    $this->assertIdentical('<script>alert("Welcome to the discovered jungle!")</script>', $title);

    // Create a hierarchy.
    \Drupal::state()->set('menu_link_content_dynamic_route.routes', [
      'route_name_1' => new Route('/example-path'),
      'route_name_2' => new Route('/example-path/child'),
    ]);
    $child = MenuLinkContent::create([
      'title' => 'Child',
      'link' => [['uri' => 'entity:/example-path/child']],
      'menu_name' => 'tools',
      'parent' => 'menu_link_content:' . $parent->uuid(),
    ]);
    $child->save();
    $parent->set('link', [['uri' => 'entity:/example-path']]);
    $parent->save();
    $menu_tree = \Drupal::menuTree()->load('tools', new MenuTreeParameters());
    $this->assertEqual(1, count($menu_tree));
    /** @var \Drupal\Core\Menu\MenuLinkTreeElement $tree_element */
    $tree_element = reset($menu_tree);
    $this->assertTrue($tree_element->hasChildren);
    $this->assertEqual(1, count($tree_element->subtree));

    // Edit child element link to use 'internal' instead of 'entity'.
    $child->set('link', [['uri' => 'internal:/example-path/child']]);
    $child->save();
    \Drupal::service('plugin.manager.menu.link')->rebuild();

    $menu_tree = \Drupal::menuTree()->load('tools', new MenuTreeParameters());
    $this->assertEqual(1, count($menu_tree));
    /** @var \Drupal\Core\Menu\MenuLinkTreeElement $tree_element */
    $tree_element = reset($menu_tree);
    $this->assertTrue($tree_element->hasChildren);
    $this->assertEqual(1, count($tree_element->subtree));
  }

}

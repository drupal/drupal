<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Menu\MenuLinkTreeTest.
 */

namespace Drupal\system\Tests\Menu;

use Drupal\Core\Menu\MenuLinkTreeElement;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\simpletest\KernelTestBase;
use Drupal\Tests\Core\Menu\MenuLinkMock;

/**
 * Tests the menu link tree.
 *
 * @group Menu
 *
 * @see \Drupal\Core\Menu\MenuLinkTree
 */
class MenuLinkTreeTest extends KernelTestBase {

  /**
   * The tested menu link tree.
   *
   * @var \Drupal\Core\Menu\MenuLinkTree
   */
  protected $linkTree;

  /**
   * The menu link plugin manager.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface $menuLinkManager
   */
  protected $menuLinkManager;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'system',
    'menu_test',
    'menu_link_content',
    'field',
    'link',
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', array('router'));
    $this->installEntitySchema('menu_link_content');

    $this->linkTree = $this->container->get('menu.link_tree');
    $this->menuLinkManager = $this->container->get('plugin.manager.menu.link');
  }

  /**
   * Tests deleting all the links in a menu.
   */
  public function testDeleteLinksInMenu() {
    \Drupal::entityManager()->getStorage('menu')->create(array('id' => 'menu1'))->save();
    \Drupal::entityManager()->getStorage('menu')->create(array('id' => 'menu2'))->save();

    \Drupal::entityManager()->getStorage('menu_link_content')->create(array('link' => ['uri' => 'internal:/menu_name_test'], 'menu_name' => 'menu1', 'bundle' => 'menu_link_content'))->save();
    \Drupal::entityManager()->getStorage('menu_link_content')->create(array('link' => ['uri' => 'internal:/menu_name_test'], 'menu_name' => 'menu1', 'bundle' => 'menu_link_content'))->save();
    \Drupal::entityManager()->getStorage('menu_link_content')->create(array('link' => ['uri' => 'internal:/menu_name_test'], 'menu_name' => 'menu2', 'bundle' => 'menu_link_content'))->save();

    $output = $this->linkTree->load('menu1', new MenuTreeParameters());
    $this->assertEqual(count($output), 2);
    $output = $this->linkTree->load('menu2', new MenuTreeParameters());
    $this->assertEqual(count($output), 1);

    $this->menuLinkManager->deleteLinksInMenu('menu1');

    $output = $this->linkTree->load('menu1', new MenuTreeParameters());
    $this->assertEqual(count($output), 0);

    $output = $this->linkTree->load('menu2', new MenuTreeParameters());
    $this->assertEqual(count($output), 1);
  }

  /**
   * Tests creating links with an expected tree structure.
   */
  public function testCreateLinksInMenu() {
     // This creates a tree with the following structure:
     // - 1
     // - 2
     //   - 3
     //     - 4
     // - 5
     //   - 7
     // - 6
     // - 8
     // With link 6 being the only external link.

    $links = array(
      1 => MenuLinkMock::create(array('id' => 'test.example1', 'route_name' => 'example1', 'title' => 'foo', 'parent' => '')),
      2 => MenuLinkMock::create(array('id' => 'test.example2', 'route_name' => 'example2', 'title' => 'bar', 'parent' => 'test.example1', 'route_parameters' => array('foo' => 'bar'))),
      3 => MenuLinkMock::create(array('id' => 'test.example3', 'route_name' => 'example3', 'title' => 'baz', 'parent' => 'test.example2', 'route_parameters' => array('baz' => 'qux'))),
      4 => MenuLinkMock::create(array('id' => 'test.example4', 'route_name' => 'example4', 'title' => 'qux', 'parent' => 'test.example3')),
      5 => MenuLinkMock::create(array('id' => 'test.example5', 'route_name' => 'example5', 'title' => 'foofoo', 'parent' => '')),
      6 => MenuLinkMock::create(array('id' => 'test.example6', 'route_name' => '', 'url' => 'https://drupal.org/', 'title' => 'barbar', 'parent' => '')),
      7 => MenuLinkMock::create(array('id' => 'test.example7', 'route_name' => 'example7', 'title' => 'bazbaz', 'parent' => '')),
      8 => MenuLinkMock::create(array('id' => 'test.example8', 'route_name' => 'example8', 'title' => 'quxqux', 'parent' => '')),
    );
    foreach ($links as $instance) {
      $this->menuLinkManager->addDefinition($instance->getPluginId(), $instance->getPluginDefinition());
    }
    $parameters = new MenuTreeParameters();
    $tree = $this->linkTree->load('mock', $parameters);

    $count = function(array $tree) {
      $sum = function ($carry, MenuLinkTreeElement $item) {
        return $carry + $item->count();
      };
      return array_reduce($tree, $sum);
    };

    $this->assertEqual($count($tree), 8);
    $parameters = new MenuTreeParameters();
    $parameters->setRoot('test.example2');
    $tree = $this->linkTree->load($instance->getMenuName(), $parameters);
    $top_link = reset($tree);
    $this->assertEqual(count($top_link->subtree), 1);
    $child = reset($top_link->subtree);
    $this->assertEqual($child->link->getPluginId(), $links[3]->getPluginId());
    $height = $this->linkTree->getSubtreeHeight('test.example2');
    $this->assertEqual($height, 3);
  }

}

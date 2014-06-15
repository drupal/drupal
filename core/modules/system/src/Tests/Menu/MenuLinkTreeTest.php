<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Menu\MenuLinkTreeTest.
 */

namespace Drupal\system\Tests\Menu;

use Drupal\simpletest\KernelTestBase;

/**
 * Tests the menu link tree.
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
   * The menu link plugin maanger
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface $menuLinkManager
   */
  protected $menuLinkManager;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system', 'menu_test', 'menu_link_content', 'field');

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Tests \Drupal\Core\Menu\MenuLinkTree',
      'description' => '',
      'group' => 'Menu',
    );
  }

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
    \Drupal::service('router.builder')->rebuild();

    \Drupal::entityManager()->getStorage('menu')->create(array('id' => 'menu1'))->save();
    \Drupal::entityManager()->getStorage('menu')->create(array('id' => 'menu2'))->save();

    \Drupal::entityManager()->getStorage('menu_link_content')->create(array('route_name' => 'menu_test.menu_name_test', 'menu_name' => 'menu1', 'bundle' => 'menu_link_content'))->save();
    \Drupal::entityManager()->getStorage('menu_link_content')->create(array('route_name' => 'menu_test.menu_name_test', 'menu_name' => 'menu1', 'bundle' => 'menu_link_content'))->save();
    \Drupal::entityManager()->getStorage('menu_link_content')->create(array('route_name' => 'menu_test.menu_name_test', 'menu_name' => 'menu2', 'bundle' => 'menu_link_content'))->save();

    $output = $this->linkTree->buildTree('menu1');
    $this->assertEqual(count($output), 2);
    $output = $this->linkTree->buildTree('menu2');
    $this->assertEqual(count($output), 1);

    $this->menuLinkManager->deleteLinksInMenu('menu1');
    $this->linkTree->resetStaticCache();

    $output = $this->linkTree->buildTree('menu1');
    $this->assertEqual(count($output), 0);

    $output = $this->linkTree->buildTree('menu2');
    $this->assertEqual(count($output), 1);
  }

  /**
   * Tests finding the parent depth limit.
   */
  public function testGetParentDepthLimit() {
    \Drupal::service('router.builder')->rebuild();

    $storage = \Drupal::entityManager()->getStorage('menu_link_content');

    // root
    // - child1
    // -- child2
    // --- child3
    // ---- child4
    $root = $storage->create(array('route_name' => 'menu_test.menu_name_test', 'menu_name' => 'menu1', 'bundle' => 'menu_link_content'));
    $root->save();
    $child1 = $storage->create(array('route_name' => 'menu_test.menu_name_test', 'menu_name' => 'menu1', 'bundle' => 'menu_link_content', 'parent' => $root->getPluginId()));
    $child1->save();
    $child2 = $storage->create(array('route_name' => 'menu_test.menu_name_test', 'menu_name' => 'menu1', 'bundle' => 'menu_link_content', 'parent' => $child1->getPluginId()));
    $child2->save();
    $child3 = $storage->create(array('route_name' => 'menu_test.menu_name_test', 'menu_name' => 'menu1', 'bundle' => 'menu_link_content', 'parent' => $child2->getPluginId()));
    $child3->save();
    $child4 = $storage->create(array('route_name' => 'menu_test.menu_name_test', 'menu_name' => 'menu1', 'bundle' => 'menu_link_content', 'parent' => $child3->getPluginId()));
    $child4->save();

    $this->assertEqual($this->linkTree->getParentDepthLimit($root->getPluginId()), 4);
    $this->assertEqual($this->linkTree->getParentDepthLimit($child1->getPluginId()), 5);
    $this->assertEqual($this->linkTree->getParentDepthLimit($child2->getPluginId()), 6);
    $this->assertEqual($this->linkTree->getParentDepthLimit($child3->getPluginId()), 7);
    $this->assertEqual($this->linkTree->getParentDepthLimit($child4->getPluginId()), 8);
  }

}

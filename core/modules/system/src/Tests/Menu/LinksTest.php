<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Menu\LinksTest.
 */

namespace Drupal\system\Tests\Menu;

use Drupal\locale\TranslationString;
use Drupal\simpletest\WebTestBase;

/**
 * Tests for menu links.
 *
 * @todo: move this under menu_link_content module.
 */
class LinksTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('router_test', 'menu_link_content');

  /**
   * The menu link plugin mananger
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface $menuLinkManager
   */
  protected $menuLinkManager;

  public static function getInfo() {
    return array(
      'name' => 'Menu links',
      'description' => 'Test handling of menu links hierarchies.',
      'group' => 'Menu',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->menuLinkManager = $this->container->get('plugin.manager.menu.link');

    entity_create('menu', array(
      'id' => 'menu_test',
      'label' => 'Test menu',
      'description' => 'Description text',
    ))->save();
  }

  /**
   * Create a simple hierarchy of links.
   */
  function createLinkHierarchy($module = 'menu_test') {
    // First remove all the menu links in the menu.
    $this->menuLinkManager->deleteLinksInMenu('menu_test');

    // Then create a simple link hierarchy:
    // - parent
    //   - child-1
    //      - child-1-1
    //      - child-1-2
    //   - child-2
    $base_options = array(
      'title' => 'Menu link test',
      'provider' => $module,
      'menu_name' => 'menu_test',
      'bundle' => 'menu_link_content'
    );

    $parent = $base_options + array(
      'route_name' => 'menu_test.hierarchy_parent',
    );
    $link = entity_create('menu_link_content', $parent);
    $link->save();
    $links['parent'] = $link->getPluginId();

    $child_1 = $base_options + array(
      'route_name' => 'menu_test.hierarchy_parent_child',
      'parent' => $links['parent'],
    );
    $link = entity_create('menu_link_content', $child_1);
    $link->save();
    $links['child-1'] = $link->getPluginId();

    $child_1_1 = $base_options + array(
      'route_name' => 'menu_test.hierarchy_parent_child2',
      'parent' => $links['child-1'],
    );
    $link = entity_create('menu_link_content', $child_1_1);
    $link->save();
    $links['child-1-1'] = $link->getPluginId();

    $child_1_2 = $base_options + array(
      'route_name' => 'menu_test.hierarchy_parent_child2',
      'parent' => $links['child-1'],
    );
    $link = entity_create('menu_link_content', $child_1_2);
    $link->save();
    $links['child-1-2'] = $link->getPluginId();

    $child_2 = $base_options + array(
      'route_name' => 'menu_test.hierarchy_parent_child',
      'parent' => $links['parent'],
    );
    $link = entity_create('menu_link_content', $child_2);
    $link->save();
    $links['child-2'] = $link->getPluginId();

    return $links;
  }

  /**
   * Assert that at set of links is properly parented.
   */
  function assertMenuLinkParents($links, $expected_hierarchy) {
    foreach ($expected_hierarchy as $id => $parent) {
      /* @var \Drupal\Core\Menu\MenuLinkInterface $menu_link_plugin  */
      $menu_link_plugin = $this->menuLinkManager->createInstance($links[$id]);
      $expected_parent = isset($links[$parent]) ? $links[$parent] : '';

      $this->assertEqual($menu_link_plugin->getParent(), $expected_parent, format_string('Menu link %id has parent of %parent, expected %expected_parent.', array('%id' => $id, '%parent' => $menu_link_plugin->getParent(), '%expected_parent' => $expected_parent)));
    }
  }

  /**
   * Test automatic reparenting of menu links.
   */
  function testMenuLinkReparenting($module = 'menu_test') {
    // Check the initial hierarchy.
    $links = $this->createLinkHierarchy($module);

    $expected_hierarchy = array(
      'parent' => '',
      'child-1' => 'parent',
      'child-1-1' => 'child-1',
      'child-1-2' => 'child-1',
      'child-2' => 'parent',
    );
    $this->assertMenuLinkParents($links, $expected_hierarchy);

    // Start over, and move child-1 under child-2, and check that all the
    // childs of child-1 have been moved too.
    $links = $this->createLinkHierarchy($module);
    /* @var \Drupal\Core\Menu\MenuLinkInterface $menu_link_plugin  */
    $this->menuLinkManager->updateLink($links['child-1'], array('parent' => $links['child-2']));
    // Verify that the entity was updated too.
    /* @var \Drupal\Core\Menu\MenuLinkInterface $menu_link_plugin  */
    $menu_link_plugin = $this->menuLinkManager->createInstance($links['child-1']);
    $entity = entity_load_by_uuid('menu_link_content', $menu_link_plugin->getDerivativeId());
    $this->assertEqual($entity->getParentId(), $links['child-2']);

    $expected_hierarchy = array(
      'parent' => '',
      'child-1' => 'child-2',
      'child-1-1' => 'child-1',
      'child-1-2' => 'child-1',
      'child-2' => 'parent',
    );
    $this->assertMenuLinkParents($links, $expected_hierarchy);

    // Start over, and delete child-1, and check that the children of child-1
    // have been reassigned to the parent.
    $links = $this->createLinkHierarchy($module);
    $this->menuLinkManager->deleteLink($links['child-1']);

    $expected_hierarchy = array(
      'parent' => FALSE,
      'child-1-1' => 'parent',
      'child-1-2' => 'parent',
      'child-2' => 'parent',
    );
    $this->assertMenuLinkParents($links, $expected_hierarchy);

    // @todo - figure out what makes sense to test in terms of automatic
    //   re-parenting.
  }

  /**
   * Tests uninstalling a module providing default links.
   */
  public function XtestModuleUninstalledMenuLinks() {
    \Drupal::moduleHandler()->install(array('menu_test'));
    \Drupal::service('router.builder')->rebuild();
    menu_link_rebuild_defaults();
    $menu_links = $this->menuLinkManager->loadLinksByRoute('menu_test.menu_test');
    $this->assertEqual(count($menu_links), 1);
    $menu_link = reset($menu_links);
    $this->assertEqual($menu_link->getPluginId(), 'menu_test');

    // Uninstall the module and ensure the menu link got removed.
    \Drupal::moduleHandler()->uninstall(array('menu_test'));
    menu_link_rebuild_defaults();
    $menu_links = $this->menuLinkManager->loadLinksByRoute('menu_test.menu_test');
    $this->assertEqual(count($menu_links), 0);
  }

}

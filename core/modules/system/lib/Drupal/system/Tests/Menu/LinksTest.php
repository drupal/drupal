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
 */
class LinksTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('router_test');

  public static function getInfo() {
    return array(
      'name' => 'Menu links',
      'description' => 'Test handling of menu links hierarchies.',
      'group' => 'Menu',
    );
  }

  /**
   * Create a simple hierarchy of links.
   */
  function createLinkHierarchy($module = 'menu_test') {
    // First remove all the menu links.
    $menu_links = menu_link_load_multiple();
    menu_link_delete_multiple(array_keys($menu_links), TRUE, TRUE);

    // Then create a simple link hierarchy:
    // - $parent
    //   - $child-1
    //      - $child-1-1
    //      - $child-1-2
    //   - $child-2
    $base_options = array(
      'link_title' => 'Menu link test',
      'module' => $module,
      'menu_name' => 'menu_test',
    );

    $links['parent'] = $base_options + array(
      'link_path' => 'menu-test/parent',
    );
    $links['parent'] = entity_create('menu_link', $links['parent']);
    $links['parent']->save();

    $links['child-1'] = $base_options + array(
      'link_path' => 'menu-test/parent/child-1',
      'plid' => $links['parent']['mlid'],
    );
    $links['child-1'] = entity_create('menu_link', $links['child-1']);
    $links['child-1']->save();

    $links['child-1-1'] = $base_options + array(
      'link_path' => 'menu-test/parent/child-1/child-1-1',
      'plid' => $links['child-1']['mlid'],
    );
    $links['child-1-1'] = entity_create('menu_link', $links['child-1-1']);
    $links['child-1-1']->save();

    $links['child-1-2'] = $base_options + array(
      'link_path' => 'menu-test/parent/child-1/child-1-2',
      'plid' => $links['child-1']['mlid'],
    );
    $links['child-1-2'] = entity_create('menu_link', $links['child-1-2']);
    $links['child-1-2']->save();

    $links['child-2'] = $base_options + array(
      'link_path' => 'menu-test/parent/child-2',
      'plid' => $links['parent']['mlid'],
    );
    $links['child-2'] = entity_create('menu_link', $links['child-2']);
    $links['child-2']->save();

    return $links;
  }

  /**
   * Assert that at set of links is properly parented.
   */
  function assertMenuLinkParents($links, $expected_hierarchy) {
    foreach ($expected_hierarchy as $child => $parent) {
      $mlid = $links[$child]['mlid'];
      $plid = $parent ? $links[$parent]['mlid'] : 0;

      $menu_link = menu_link_load($mlid);
      menu_link_save($menu_link);
      $this->assertEqual($menu_link['plid'], $plid, format_string('Menu link %mlid has parent of %plid, expected %expected_plid.', array('%mlid' => $mlid, '%plid' => $menu_link['plid'], '%expected_plid' => $plid)));
    }
  }

  /**
   * Test automatic reparenting of menu links.
   */
  function testMenuLinkReparenting($module = 'menu_test') {
    // Check the initial hierarchy.
    $links = $this->createLinkHierarchy($module);

    $expected_hierarchy = array(
      'parent' => FALSE,
      'child-1' => 'parent',
      'child-1-1' => 'child-1',
      'child-1-2' => 'child-1',
      'child-2' => 'parent',
    );
    $this->assertMenuLinkParents($links, $expected_hierarchy);

    // Start over, and move child-1 under child-2, and check that all the
    // childs of child-1 have been moved too.
    $links = $this->createLinkHierarchy($module);
    $links['child-1']['plid'] = $links['child-2']['mlid'];
    menu_link_save($links['child-1']);

    $expected_hierarchy = array(
      'parent' => FALSE,
      'child-1' => 'child-2',
      'child-1-1' => 'child-1',
      'child-1-2' => 'child-1',
      'child-2' => 'parent',
    );
    $this->assertMenuLinkParents($links, $expected_hierarchy);

    // Start over, and delete child-1, and check that the children of child-1
    // have been reassigned to the parent. menu_link_delete() will cowardly
    // refuse to delete a menu link defined by the system module, so skip the
    // test in that case.
    if ($module != 'system') {
      $links = $this->createLinkHierarchy($module);
      menu_link_delete($links['child-1']['mlid']);

      $expected_hierarchy = array(
        'parent' => FALSE,
        'child-1-1' => 'parent',
        'child-1-2' => 'parent',
        'child-2' => 'parent',
      );
      $this->assertMenuLinkParents($links, $expected_hierarchy);
    }

    // Start over, forcefully delete child-1 from the database, simulating a
    // database crash. Check that the children of child-1 have been reassigned
    // to the parent, going up on the old path hierarchy stored in each of the
    // links.
    $links = $this->createLinkHierarchy($module);
    // Don't do that at home.
    db_delete('menu_links')
      ->condition('mlid', $links['child-1']['mlid'])
      ->execute();

    $expected_hierarchy = array(
      'parent' => FALSE,
      'child-1-1' => 'parent',
      'child-1-2' => 'parent',
      'child-2' => 'parent',
    );
    $this->assertMenuLinkParents($links, $expected_hierarchy);

    // Start over, forcefully delete the parent from the database, simulating a
    // database crash. Check that the children of parent are now top-level.
    $links = $this->createLinkHierarchy($module);
    // Don't do that at home.
    db_delete('menu_links')
      ->condition('mlid', $links['parent']['mlid'])
      ->execute();

    $expected_hierarchy = array(
      'child-1-1' => 'child-1',
      'child-1-2' => 'child-1',
      'child-2' => FALSE,
    );
    $this->assertMenuLinkParents($links, $expected_hierarchy);
  }

  /**
   * Tests automatic reparenting of menu links derived from hook_menu_link_defaults.
   */
  function testMenuLinkRouterReparenting() {
    // Run all the standard parenting tests on menu links derived from
    // menu routers.
    $this->testMenuLinkReparenting('system');

    // Additionnaly, test reparenting based on path.
    $links = $this->createLinkHierarchy('system');

    // Move child-1-2 has a child of child-2, making the link hierarchy
    // inconsistent with the path hierarchy.
    $links['child-1-2']['plid'] = $links['child-2']['mlid'];
    menu_link_save($links['child-1-2']);

    // Check the new hierarchy.
    $expected_hierarchy = array(
      'parent' => FALSE,
      'child-1' => 'parent',
      'child-1-1' => 'child-1',
      'child-2' => 'parent',
      'child-1-2' => 'child-2',
    );
    $this->assertMenuLinkParents($links, $expected_hierarchy);

    // Now delete 'parent' directly from the database, simulating a database
    // crash. 'child-1' and 'child-2' should get moved to the
    // top-level.
    // Don't do that at home.
    db_delete('menu_links')
      ->condition('mlid', $links['parent']['mlid'])
      ->execute();
    $expected_hierarchy = array(
      'child-1' => FALSE,
      'child-1-1' => 'child-1',
      'child-2' => FALSE,
      'child-1-2' => 'child-2',
    );
    $this->assertMenuLinkParents($links, $expected_hierarchy);

    // Now delete 'child-2' directly from the database, simulating a database
    // crash. 'child-1-2' will get reparented to the top.
    // Don't do that at home.
    db_delete('menu_links')
      ->condition('mlid', $links['child-2']['mlid'])
      ->execute();
    $expected_hierarchy = array(
      'child-1' => FALSE,
      'child-1-1' => 'child-1',
      'child-1-2' => FALSE,
    );
    $this->assertMenuLinkParents($links, $expected_hierarchy);
  }

  /**
   * Tests the router system integration (route_name and route_parameters).
   */
  public function testRouterIntegration() {
    $menu_link = entity_create('menu_link', array(
      'link_path' => 'router_test/test1',
    ));
    $menu_link->save();
    $this->assertEqual($menu_link->route_name, 'router_test.1');
    $this->assertEqual($menu_link->route_parameters, array());

    $menu_link = entity_create('menu_link', array(
      'link_path' => 'router_test/test3/test',
    ));
    $menu_link->save();
    $this->assertEqual($menu_link->route_name, 'router_test.3');
    $this->assertEqual($menu_link->route_parameters, array('value' => 'test'));

    $menu_link = entity_load('menu_link', $menu_link->id());
    $this->assertEqual($menu_link->route_name, 'router_test.3');
    $this->assertEqual($menu_link->route_parameters, array('value' => 'test'));
  }

  /**
   * Tests uninstall a module providing default links.
   */
  public function testModuleUninstalledMenuLinks() {
    \Drupal::moduleHandler()->install(array('menu_test'));
    \Drupal::service('router.builder')->rebuild();
    menu_link_rebuild_defaults();
    $result = $menu_link = \Drupal::entityQuery('menu_link')->condition('machine_name', 'menu_test')->execute();
    $menu_links = \Drupal::entityManager()->getStorageController('menu_link')->loadMultiple($result);
    $this->assertEqual(count($menu_links), 1);
    $menu_link = reset($menu_links);
    $this->assertEqual($menu_link->machine_name, 'menu_test');

    // Uninstall the module and ensure the menu link got removed.
    \Drupal::moduleHandler()->uninstall(array('menu_test'));
    $result = $menu_link = \Drupal::entityQuery('menu_link')->condition('machine_name', 'menu_test')->execute();
    $menu_links = \Drupal::entityManager()->getStorageController('menu_link')->loadMultiple($result);
    $this->assertEqual(count($menu_links), 0);
  }

}

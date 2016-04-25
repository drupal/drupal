<?php

namespace Drupal\menu_link_content\Tests;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\simpletest\WebTestBase;
use Drupal\system\Entity\Menu;

/**
 * Tests handling of menu links hierarchies.
 *
 * @group Menu
 */
class LinksTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('router_test', 'menu_link_content');

  /**
   * The menu link plugin manager.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface $menuLinkManager
   */
  protected $menuLinkManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->menuLinkManager = \Drupal::service('plugin.manager.menu.link');

    Menu::create(array(
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
    );

    $parent = $base_options + array(
      'link' => ['uri' => 'internal:/menu-test/hierarchy/parent'],
    );
    $link = MenuLinkContent::create($parent);
    $link->save();
    $links['parent'] = $link->getPluginId();

    $child_1 = $base_options + array(
      'link' => ['uri' => 'internal:/menu-test/hierarchy/parent/child'],
      'parent' => $links['parent'],
    );
    $link = MenuLinkContent::create($child_1);
    $link->save();
    $links['child-1'] = $link->getPluginId();

    $child_1_1 = $base_options + array(
      'link' => ['uri' => 'internal:/menu-test/hierarchy/parent/child2/child'],
      'parent' => $links['child-1'],
    );
    $link = MenuLinkContent::create($child_1_1);
    $link->save();
    $links['child-1-1'] = $link->getPluginId();

    $child_1_2 = $base_options + array(
      'link' => ['uri' => 'internal:/menu-test/hierarchy/parent/child2/child'],
      'parent' => $links['child-1'],
    );
    $link = MenuLinkContent::create($child_1_2);
    $link->save();
    $links['child-1-2'] = $link->getPluginId();

    $child_2 = $base_options + array(
      'link' => ['uri' => 'internal:/menu-test/hierarchy/parent/child'],
      'parent' => $links['parent'],
    );
    $link = MenuLinkContent::create($child_2);
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

      $this->assertEqual($menu_link_plugin->getParent(), $expected_parent, SafeMarkup::format('Menu link %id has parent of %parent, expected %expected_parent.', array('%id' => $id, '%parent' => $menu_link_plugin->getParent(), '%expected_parent' => $expected_parent)));
    }
  }

  /**
   * Assert that a link entity's created timestamp is set.
   */
  public function testCreateLink() {
    $options = array(
      'menu_name' => 'menu_test',
      'bundle' => 'menu_link_content',
      'link' => [['uri' => 'internal:/']],
    );
    $link = MenuLinkContent::create($options);
    $link->save();
    // Make sure the changed timestamp is set.
    $this->assertEqual($link->getChangedTime(), REQUEST_TIME, 'Creating a menu link sets the "changed" timestamp.');
    $options = array(
      'title' => 'Test Link',
    );
    $link->link->options = $options;
    $link->changed->value = 0;
    $link->save();
    // Make sure the changed timestamp is updated.
    $this->assertEqual($link->getChangedTime(), REQUEST_TIME, 'Changing a menu link sets "changed" timestamp.');
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
    // children of child-1 have been moved too.
    $links = $this->createLinkHierarchy($module);
    /* @var \Drupal\Core\Menu\MenuLinkInterface $menu_link_plugin  */
    $this->menuLinkManager->updateDefinition($links['child-1'], array('parent' => $links['child-2']));
    // Verify that the entity was updated too.
    $menu_link_plugin = $this->menuLinkManager->createInstance($links['child-1']);
    $entity = \Drupal::entityManager()->loadEntityByUuid('menu_link_content', $menu_link_plugin->getDerivativeId());
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
    $this->menuLinkManager->removeDefinition($links['child-1']);

    $expected_hierarchy = array(
      'parent' => FALSE,
      'child-1-1' => 'parent',
      'child-1-2' => 'parent',
      'child-2' => 'parent',
    );
    $this->assertMenuLinkParents($links, $expected_hierarchy);

    // @todo Figure out what makes sense to test in terms of automatic
    //   re-parenting. https://www.drupal.org/node/2309531
  }

  /**
   * Tests uninstalling a module providing default links.
   */
  public function testModuleUninstalledMenuLinks() {
    \Drupal::service('module_installer')->install(array('menu_test'));
    \Drupal::service('router.builder')->rebuild();
    \Drupal::service('plugin.manager.menu.link')->rebuild();
    $menu_links = $this->menuLinkManager->loadLinksByRoute('menu_test.menu_test');
    $this->assertEqual(count($menu_links), 1);
    $menu_link = reset($menu_links);
    $this->assertEqual($menu_link->getPluginId(), 'menu_test');

    // Uninstall the module and ensure the menu link got removed.
    \Drupal::service('module_installer')->uninstall(array('menu_test'));
    \Drupal::service('plugin.manager.menu.link')->rebuild();
    $menu_links = $this->menuLinkManager->loadLinksByRoute('menu_test.menu_test');
    $this->assertEqual(count($menu_links), 0);
  }

}

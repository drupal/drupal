<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Menu\MenuTreeStorageTest.
 */

namespace Drupal\system\Tests\Menu;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Menu\MenuTreeStorage;
use Drupal\simpletest\KernelTestBase;

/**
 * Tests the menu tree storage.
 *
 * @group Menu
 *
 * @see \Drupal\Core\Menu\MenuTreeStorage
 */
class MenuTreeStorageTest extends KernelTestBase {

  /**
   * The tested tree storage.
   *
   * @var \Drupal\Core\Menu\MenuTreeStorage
   */
  protected $treeStorage;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system', 'menu_link_content');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->treeStorage = new MenuTreeStorage($this->container->get('database'), $this->container->get('cache.menu'), 'menu_tree');
    $this->connection = $this->container->get('database');
    $this->installEntitySchema('menu_link_content');
  }

  /**
   * Tests the tree storage when no tree was built yet.
   */
  public function testBasicMethods() {
    $this->doTestEmptyStorage();
    $this->doTestTable();
  }

  /**
   * Ensures that there are no menu links by default.
   */
  protected function doTestEmptyStorage() {
    $this->assertEqual(0, $this->treeStorage->countMenuLinks());
  }

  /**
   * Ensures that table gets created on the fly.
   */
  protected function doTestTable() {
    // Test that we can create a tree storage with an arbitrary table name and
    // that selecting from the storage creates the table.
    $tree_storage = new MenuTreeStorage($this->container->get('database'), $this->container->get('cache.menu'), 'test_menu_tree');
    $this->assertFalse($this->connection->schema()->tableExists('test_menu_tree'), 'Test table is not yet created');
    $tree_storage->countMenuLinks();
    $this->assertTrue($this->connection->schema()->tableExists('test_menu_tree'), 'Test table was created');
  }

  /**
   * Tests with a simple linear hierarchy.
   */
  public function testSimpleHierarchy() {
    // Add some links with parent on the previous one and test some values.
    // <tools>
    // - test1
    // -- test2
    // --- test3
    $this->addMenuLink('test1', '');
    $this->assertMenuLink('test1', array('has_children' => 0, 'depth' => 1));

    $this->addMenuLink('test2', 'test1');
    $this->assertMenuLink('test1', array('has_children' => 1, 'depth' => 1), array(), array('test2'));
    $this->assertMenuLink('test2', array('has_children' => 0, 'depth' => 2), array('test1'));

    $this->addMenuLink('test3', 'test2');
    $this->assertMenuLink('test1', array('has_children' => 1, 'depth' => 1), array(), array('test2', 'test3'));
    $this->assertMenuLink('test2', array('has_children' => 1, 'depth' => 2), array('test1'), array('test3'));
    $this->assertMenuLink('test3', array('has_children' => 0, 'depth' => 3), array('test2', 'test1'));
  }

  /**
   * Tests the tree with moving links inside the hierarchy.
   */
  public function testMenuLinkMoving() {
    // Before the move.
    // <tools>
    // - test1
    // -- test2
    // --- test3
    // - test4
    // -- test5
    // --- test6

    $this->addMenuLink('test1', '');
    $this->addMenuLink('test2', 'test1');
    $this->addMenuLink('test3', 'test2');
    $this->addMenuLink('test4', '');
    $this->addMenuLink('test5', 'test4');
    $this->addMenuLink('test6', 'test5');

    $this->assertMenuLink('test1', array('has_children' => 1, 'depth' => 1), array(), array('test2', 'test3'));
    $this->assertMenuLink('test2', array('has_children' => 1, 'depth' => 2), array('test1'), array('test3'));
    $this->assertMenuLink('test4', array('has_children' => 1, 'depth' => 1), array(), array('test5', 'test6'));
    $this->assertMenuLink('test5', array('has_children' => 1, 'depth' => 2), array('test4'), array('test6'));
    $this->assertMenuLink('test6', array('has_children' => 0, 'depth' => 3), array('test5', 'test4'));

    $this->moveMenuLink('test2', 'test5');
    // After the 1st move.
    // <tools>
    // - test1
    // - test4
    // -- test5
    // --- test2
    // ---- test3
    // --- test6

    $this->assertMenuLink('test1', array('has_children' => 0, 'depth' => 1));
    $this->assertMenuLink('test2', array('has_children' => 1, 'depth' => 3), array('test5', 'test4'), array('test3'));
    $this->assertMenuLink('test3', array('has_children' => 0, 'depth' => 4), array('test2', 'test5', 'test4'));
    $this->assertMenuLink('test4', array('has_children' => 1, 'depth' => 1), array(), array('test5', 'test2', 'test3', 'test6'));
    $this->assertMenuLink('test5', array('has_children' => 1, 'depth' => 2), array('test4'), array('test2', 'test3', 'test6'));
    $this->assertMenuLink('test6', array('has_children' => 0, 'depth' => 3), array('test5', 'test4'));

    $this->moveMenuLink('test4', 'test1');
    $this->moveMenuLink('test3', 'test1');
    // After the next 2 moves.
    // <tools>
    // - test1
    // -- test3
    // -- test4
    // --- test5
    // ---- test2
    // ---- test6

    $this->assertMenuLink('test1', array('has_children' => 1, 'depth' => 1), array(), array('test4', 'test5', 'test2', 'test3', 'test6'));
    $this->assertMenuLink('test2', array('has_children' => 0, 'depth' => 4), array('test5', 'test4', 'test1'));
    $this->assertMenuLink('test3', array('has_children' => 0, 'depth' => 2), array('test1'));
    $this->assertMenuLink('test4', array('has_children' => 1, 'depth' => 2), array('test1'), array('test2', 'test5', 'test6'));
    $this->assertMenuLink('test5', array('has_children' => 1, 'depth' => 3), array('test4', 'test1'), array('test2', 'test6'));
    $this->assertMenuLink('test6', array('has_children' => 0, 'depth' => 4), array('test5', 'test4', 'test1'));

    // Deleting a link in the middle should re-attach child links to the parent.
    $this->treeStorage->delete('test4');
    // After the delete.
    // <tools>
    // - test1
    // -- test3
    // -- test5
    // --- test2
    // --- test6
    $this->assertMenuLink('test1', array('has_children' => 1, 'depth' => 1), array(), array('test5', 'test2', 'test3', 'test6'));
    $this->assertMenuLink('test2', array('has_children' => 0, 'depth' => 3), array('test5', 'test1'));
    $this->assertMenuLink('test3', array('has_children' => 0, 'depth' => 2), array('test1'));
    $this->assertFalse($this->treeStorage->load('test4'));
    $this->assertMenuLink('test5', array('has_children' => 1, 'depth' => 2), array('test1'), array('test2', 'test6'));
    $this->assertMenuLink('test6', array('has_children' => 0, 'depth' => 3), array('test5', 'test1'));
  }

  /**
   * Tests with disabled child links.
   */
  public function testMenuDisabledChildLinks() {
    // Add some links with parent on the previous one and test some values.
    // <tools>
    // - test1
    // -- test2 (disabled)

    $this->addMenuLink('test1', '');
    $this->assertMenuLink('test1', array('has_children' => 0, 'depth' => 1));

    $this->addMenuLink('test2', 'test1', '<front>', array(), 'tools', array('enabled' => 0));
    // The 1st link does not have any visible children, so has_children is 0.
    $this->assertMenuLink('test1', array('has_children' => 0, 'depth' => 1));
    $this->assertMenuLink('test2', array('has_children' => 0, 'depth' => 2, 'enabled' => 0), array('test1'));

    // Add more links with parent on the previous one.
    // <footer>
    // - footerA
    // ===============
    // <tools>
    // - test1
    // -- test2 (disabled)
    // --- test3
    // ---- test4
    // ----- test5
    // ------ test6
    // ------- test7
    // -------- test8
    // --------- test9
    $this->addMenuLink('footerA', '', '<front>', array(), 'footer');
    $visible_children = array();
    for ($i = 3; $i <= $this->treeStorage->maxDepth(); $i++) {
      $parent = $i - 1;
      $this->addMenuLink("test$i", "test$parent");
      $visible_children[] = "test$i";
    }
    // The 1st link does not have any visible children, so has_children is still
    // 0. However, it has visible links below it that will be found.
    $this->assertMenuLink('test1', array('has_children' => 0, 'depth' => 1), array(), $visible_children);
    // This should fail since test9 would end up at greater than max depth.
    try {
      $this->moveMenuLink('test1', 'footerA');
      $this->fail('Exception was not thrown');
    }
    catch (PluginException $e) {
      $this->pass($e->getMessage());
    }
    // The opposite move should work, and change the has_children flag.
    $this->moveMenuLink('footerA', 'test1');
    $visible_children[] = 'footerA';
    $this->assertMenuLink('test1', array('has_children' => 1, 'depth' => 1), array(), $visible_children);
  }

  /**
   * Tests the loadTreeData method.
   */
  public function testLoadTree() {
    $this->addMenuLink('test1', '');
    $this->addMenuLink('test2', 'test1');
    $this->addMenuLink('test3', 'test2');
    $this->addMenuLink('test4');
    $this->addMenuLink('test5', 'test4');

    $data = $this->treeStorage->loadTreeData('tools', new MenuTreeParameters());
    $tree = $data['tree'];
    $this->assertEqual(count($tree['test1']['subtree']), 1);
    $this->assertEqual(count($tree['test1']['subtree']['test2']['subtree']), 1);
    $this->assertEqual(count($tree['test1']['subtree']['test2']['subtree']['test3']['subtree']), 0);
    $this->assertEqual(count($tree['test4']['subtree']), 1);
    $this->assertEqual(count($tree['test4']['subtree']['test5']['subtree']), 0);

    $parameters = new MenuTreeParameters();
    $parameters->setActiveTrail(array('test4', 'test5'));
    $data = $this->treeStorage->loadTreeData('tools', $parameters);
    $tree = $data['tree'];
    $this->assertEqual(count($tree['test1']['subtree']), 1);
    $this->assertFalse($tree['test1']['in_active_trail']);
    $this->assertEqual(count($tree['test1']['subtree']['test2']['subtree']), 1);
    $this->assertFalse($tree['test1']['subtree']['test2']['in_active_trail']);
    $this->assertEqual(count($tree['test1']['subtree']['test2']['subtree']['test3']['subtree']), 0);
    $this->assertFalse($tree['test1']['subtree']['test2']['subtree']['test3']['in_active_trail']);
    $this->assertEqual(count($tree['test4']['subtree']), 1);
    $this->assertTrue($tree['test4']['in_active_trail']);
    $this->assertEqual(count($tree['test4']['subtree']['test5']['subtree']), 0);
    $this->assertTrue($tree['test4']['subtree']['test5']['in_active_trail']);
  }

  /**
   * Tests finding the subtree height with content menu links.
   */
  public function testSubtreeHeight() {

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

    $this->assertEqual($this->treeStorage->getSubtreeHeight($root->getPluginId()), 5);
    $this->assertEqual($this->treeStorage->getSubtreeHeight($child1->getPluginId()), 4);
    $this->assertEqual($this->treeStorage->getSubtreeHeight($child2->getPluginId()), 3);
    $this->assertEqual($this->treeStorage->getSubtreeHeight($child3->getPluginId()), 2);
    $this->assertEqual($this->treeStorage->getSubtreeHeight($child4->getPluginId()), 1);
  }

  /**
   * Tests MenuTreeStorage::loadByProperties().
   */
  public function testLoadByProperties() {
    $tests = array(
      array('foo' => 'bar'),
      array(0 => 'wrong'),
    );
    $message = 'An invalid property name throws an exception.';
    foreach ($tests as $properties) {
      try {
        $this->treeStorage->loadByProperties($properties);
        $this->fail($message);
      }
      catch (\InvalidArgumentException $e) {
        $this->assertTrue(preg_match('/^An invalid property name, .+ was specified. Allowed property names are:/', $e->getMessage()), 'Found expected exception message.');
        $this->pass($message);
      }
    }
    $this->addMenuLink('test_link.1', '', 'test', array(), 'menu1');
    $properties = array('menu_name' => 'menu1');
    $links = $this->treeStorage->loadByProperties($properties);
    $this->assertEqual('menu1', $links['test_link.1']['menu_name']);
    $this->assertEqual('test', $links['test_link.1']['route_name']);
  }

  /**
   * Adds a link with the given ID and supply defaults.
   */
  protected function addMenuLink($id, $parent = '', $route_name = 'test', $route_parameters = array(), $menu_name = 'tools', $extra = array()) {
    $link = array(
      'id' => $id,
      'menu_name' => $menu_name,
      'route_name' => $route_name,
      'route_parameters' => $route_parameters,
      'title_arguments' => array(),
      'title' => 'test',
      'parent' => $parent,
      'options' => array(),
      'metadata' => array(),
    ) + $extra;
    $this->treeStorage->save($link);
  }

  /**
   * Moves the link with the given ID so it's under a new parent.
   *
   * @param string $id
   *   The ID of the menu link to move.
   * @param string $new_parent
   *   The ID of the new parent link.
   */
  protected function moveMenuLink($id, $new_parent) {
    $menu_link = $this->treeStorage->load($id);
    $menu_link['parent'] = $new_parent;
    $this->treeStorage->save($menu_link);
  }

  /**
   * Tests that a link's stored representation matches the expected values.
   *
   * @param string $id
   *   The ID of the menu link to test
   * @param array $expected_properties
   *   A keyed array of column names and values like has_children and depth.
   * @param array $parents
   *   An ordered array of the IDs of the menu links that are the parents.
   * @param array $children
   *   Array of child IDs that are visible (enabled == 1).
   */
  protected function assertMenuLink($id, array $expected_properties, array $parents = array(), array $children = array()) {
    $query = $this->connection->select('menu_tree');
    $query->fields('menu_tree');
    $query->condition('id', $id);
    foreach ($expected_properties as $field => $value) {
      $query->condition($field, $value);
    }
    $all = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
    $this->assertEqual(count($all), 1, "Found link $id matching all the expected properties");
    $raw = reset($all);

    // Put the current link onto the front.
    array_unshift($parents, $raw['id']);

    $query = $this->connection->select('menu_tree');
    $query->fields('menu_tree', array('id', 'mlid'));
    $query->condition('id', $parents, 'IN');
    $found_parents = $query->execute()->fetchAllKeyed(0, 1);

    $this->assertEqual(count($parents), count($found_parents), 'Found expected number of parents');
    $this->assertEqual($raw['depth'], count($found_parents), 'Number of parents is the same as the depth');

    $materialized_path = $this->treeStorage->getRootPathIds($id);
    $this->assertEqual(array_values($materialized_path), array_values($parents), 'Parents match the materialized path');
    // Check that the selected mlid values of the parents are in the correct
    // column, including the link's own.
    for ($i = $raw['depth']; $i >= 1; $i--) {
      $parent_id = array_shift($parents);
      $this->assertEqual($raw["p$i"], $found_parents[$parent_id], "mlid of parent matches at column p$i");
    }
    for ($i = $raw['depth'] + 1; $i <= $this->treeStorage->maxDepth(); $i++) {
      $this->assertEqual($raw["p$i"], 0, "parent is 0 at column p$i greater than depth");
    }
    if ($parents) {
      $this->assertEqual($raw['parent'], end($parents), 'Ensure that the parent field is set properly');
    }
    $found_children = array_keys($this->treeStorage->loadAllChildren($id));
    // We need both these checks since the 2nd will pass if there are extra
    // IDs loaded in $found_children.
    $this->assertEqual(count($children), count($found_children), "Found expected number of children for $id");
    $this->assertEqual(array_intersect($children, $found_children), $children, 'Child IDs match');
  }

}

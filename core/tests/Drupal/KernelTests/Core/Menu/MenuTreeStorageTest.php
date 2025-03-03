<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Menu;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Database\Statement\FetchAs;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Menu\MenuTreeStorage;
use Drupal\KernelTests\KernelTestBase;

// cspell:ignore mlid

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
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->treeStorage = new MenuTreeStorage($this->container->get('database'), $this->container->get('cache.menu'), $this->container->get('cache_tags.invalidator'), 'menu_tree');
    $this->connection = $this->container->get('database');
  }

  /**
   * Tests the tree storage when no tree was built yet.
   */
  public function testBasicMethods(): void {
    $this->doTestEmptyStorage();
    $this->doTestTable();
  }

  /**
   * Ensures that there are no menu links by default.
   */
  protected function doTestEmptyStorage(): void {
    $this->assertEquals(0, $this->treeStorage->countMenuLinks());
  }

  /**
   * Ensures that table gets created on the fly.
   */
  protected function doTestTable(): void {
    // Test that we can create a tree storage with an arbitrary table name and
    // that selecting from the storage creates the table.
    $tree_storage = new MenuTreeStorage($this->container->get('database'), $this->container->get('cache.menu'), $this->container->get('cache_tags.invalidator'), 'test_menu_tree');
    $this->assertFalse($this->connection->schema()->tableExists('test_menu_tree'), 'Test table is not yet created');
    $tree_storage->countMenuLinks();
    $this->assertTrue($this->connection->schema()->tableExists('test_menu_tree'), 'Test table was created');
  }

  /**
   * Tests with a simple linear hierarchy.
   */
  public function testSimpleHierarchy(): void {
    // Add some links with parent on the previous one and test some values.
    // <tools>
    // - test1
    // -- test2
    // --- test3
    $this->addMenuLink('test1', '');
    $this->assertMenuLink('test1', ['has_children' => 0, 'depth' => 1]);

    $this->addMenuLink('test2', 'test1');
    $this->assertMenuLink('test1', ['has_children' => 1, 'depth' => 1], [], ['test2']);
    $this->assertMenuLink('test2', ['has_children' => 0, 'depth' => 2], ['test1']);

    $this->addMenuLink('test3', 'test2');
    $this->assertMenuLink('test1', ['has_children' => 1, 'depth' => 1], [], ['test2', 'test3']);
    $this->assertMenuLink('test2', ['has_children' => 1, 'depth' => 2], ['test1'], ['test3']);
    $this->assertMenuLink('test3', ['has_children' => 0, 'depth' => 3], ['test2', 'test1']);
  }

  /**
   * Tests the tree with moving links inside the hierarchy.
   */
  public function testMenuLinkMoving(): void {
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

    $this->assertMenuLink('test1', ['has_children' => 1, 'depth' => 1], [], ['test2', 'test3']);
    $this->assertMenuLink('test2', ['has_children' => 1, 'depth' => 2], ['test1'], ['test3']);
    $this->assertMenuLink('test4', ['has_children' => 1, 'depth' => 1], [], ['test5', 'test6']);
    $this->assertMenuLink('test5', ['has_children' => 1, 'depth' => 2], ['test4'], ['test6']);
    $this->assertMenuLink('test6', ['has_children' => 0, 'depth' => 3], ['test5', 'test4']);

    $this->moveMenuLink('test2', 'test5');
    // After the 1st move.
    // <tools>
    // - test1
    // - test4
    // -- test5
    // --- test2
    // ---- test3
    // --- test6

    $this->assertMenuLink('test1', ['has_children' => 0, 'depth' => 1]);
    $this->assertMenuLink('test2', ['has_children' => 1, 'depth' => 3], ['test5', 'test4'], ['test3']);
    $this->assertMenuLink('test3', ['has_children' => 0, 'depth' => 4], ['test2', 'test5', 'test4']);
    $this->assertMenuLink('test4', ['has_children' => 1, 'depth' => 1], [], ['test5', 'test2', 'test3', 'test6']);
    $this->assertMenuLink('test5', ['has_children' => 1, 'depth' => 2], ['test4'], ['test2', 'test3', 'test6']);
    $this->assertMenuLink('test6', ['has_children' => 0, 'depth' => 3], ['test5', 'test4']);

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

    $this->assertMenuLink('test1', ['has_children' => 1, 'depth' => 1], [], ['test4', 'test5', 'test2', 'test3', 'test6']);
    $this->assertMenuLink('test2', ['has_children' => 0, 'depth' => 4], ['test5', 'test4', 'test1']);
    $this->assertMenuLink('test3', ['has_children' => 0, 'depth' => 2], ['test1']);
    $this->assertMenuLink('test4', ['has_children' => 1, 'depth' => 2], ['test1'], ['test2', 'test5', 'test6']);
    $this->assertMenuLink('test5', ['has_children' => 1, 'depth' => 3], ['test4', 'test1'], ['test2', 'test6']);
    $this->assertMenuLink('test6', ['has_children' => 0, 'depth' => 4], ['test5', 'test4', 'test1']);

    // Deleting a link in the middle should re-attach child links to the parent.
    $this->treeStorage->delete('test4');
    // After the delete.
    // <tools>
    // - test1
    // -- test3
    // -- test5
    // --- test2
    // --- test6
    $this->assertMenuLink('test1', ['has_children' => 1, 'depth' => 1], [], ['test5', 'test2', 'test3', 'test6']);
    $this->assertMenuLink('test2', ['has_children' => 0, 'depth' => 3], ['test5', 'test1']);
    $this->assertMenuLink('test3', ['has_children' => 0, 'depth' => 2], ['test1']);
    $this->assertFalse($this->treeStorage->load('test4'));
    $this->assertMenuLink('test5', ['has_children' => 1, 'depth' => 2], ['test1'], ['test2', 'test6']);
    $this->assertMenuLink('test6', ['has_children' => 0, 'depth' => 3], ['test5', 'test1']);
  }

  /**
   * Tests with disabled child links.
   */
  public function testMenuDisabledChildLinks(): void {
    // Add some links with parent on the previous one and test some values.
    // <tools>
    // - test1
    // -- test2 (disabled)

    $this->addMenuLink('test1', '');
    $this->assertMenuLink('test1', ['has_children' => 0, 'depth' => 1]);

    $this->addMenuLink('test2', 'test1', '<front>', [], 'tools', ['enabled' => 0]);
    // The 1st link does not have any visible children, so has_children is 0.
    $this->assertMenuLink('test1', ['has_children' => 0, 'depth' => 1]);
    $this->assertMenuLink('test2', ['has_children' => 0, 'depth' => 2, 'enabled' => 0], ['test1']);

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
    $this->addMenuLink('footerA', '', '<front>', [], 'footer');
    $visible_children = [];
    for ($i = 3; $i <= $this->treeStorage->maxDepth(); $i++) {
      $parent = $i - 1;
      $this->addMenuLink("test$i", "test$parent");
      $visible_children[] = "test$i";
    }
    // The 1st link does not have any visible children, so has_children is still
    // 0. However, it has visible links below it that will be found.
    $this->assertMenuLink('test1', ['has_children' => 0, 'depth' => 1], [], $visible_children);
    // This should fail since test9 would end up at greater than max depth.
    try {
      $this->moveMenuLink('test1', 'footerA');
      $this->fail('Exception was not thrown');
    }
    catch (PluginException) {
      // Expected exception; just continue testing.
    }
    // The opposite move should work, and change the has_children flag.
    $this->moveMenuLink('footerA', 'test1');
    $visible_children[] = 'footerA';
    $this->assertMenuLink('test1', ['has_children' => 1, 'depth' => 1], [], $visible_children);
  }

  /**
   * Tests the loadTreeData method.
   */
  public function testLoadTree(): void {
    $this->addMenuLink('test1', '', 'test1');
    $this->addMenuLink('test2', 'test1', 'test2');
    $this->addMenuLink('test3', 'test2', 'test3');
    $this->addMenuLink('test4', '', 'test4');
    $this->addMenuLink('test5', 'test4', NULL);

    $data = $this->treeStorage->loadTreeData('tools', new MenuTreeParameters());
    $tree = $data['tree'];
    $this->assertCount(1, $tree['test1']['subtree']);
    $this->assertCount(1, $tree['test1']['subtree']['test2']['subtree']);
    $this->assertCount(0, $tree['test1']['subtree']['test2']['subtree']['test3']['subtree']);
    $this->assertCount(1, $tree['test4']['subtree']);
    $this->assertCount(0, $tree['test4']['subtree']['test5']['subtree']);

    // Ensure that route names element exists.
    $this->assertNotEmpty($data['route_names']);

    // Ensure that the actual route names are set.
    $this->assertContains('test1', $data['route_names']);
    $this->assertNotContains('test5', $data['route_names']);

    $parameters = new MenuTreeParameters();
    $parameters->setActiveTrail(['test4', 'test5']);
    $data = $this->treeStorage->loadTreeData('tools', $parameters);
    $tree = $data['tree'];
    $this->assertCount(1, $tree['test1']['subtree']);
    $this->assertFalse($tree['test1']['in_active_trail']);
    $this->assertCount(1, $tree['test1']['subtree']['test2']['subtree']);
    $this->assertFalse($tree['test1']['subtree']['test2']['in_active_trail']);
    $this->assertCount(0, $tree['test1']['subtree']['test2']['subtree']['test3']['subtree']);
    $this->assertFalse($tree['test1']['subtree']['test2']['subtree']['test3']['in_active_trail']);
    $this->assertCount(1, $tree['test4']['subtree']);
    $this->assertTrue($tree['test4']['in_active_trail']);
    $this->assertCount(0, $tree['test4']['subtree']['test5']['subtree']);
    $this->assertTrue($tree['test4']['subtree']['test5']['in_active_trail']);

    // Add some conditions to ensure that conditions work as expected.
    $parameters = new MenuTreeParameters();
    $parameters->addCondition('parent', 'test1');
    $data = $this->treeStorage->loadTreeData('tools', $parameters);
    $this->assertCount(1, $data['tree']);
    $this->assertEquals('test2', $data['tree']['test2']['definition']['id']);
    $this->assertEquals([], $data['tree']['test2']['subtree']);

    // Test for only enabled links.
    $link = $this->treeStorage->load('test3');
    $link['enabled'] = FALSE;
    $this->treeStorage->save($link);
    $link = $this->treeStorage->load('test4');
    $link['enabled'] = FALSE;
    $this->treeStorage->save($link);
    $link = $this->treeStorage->load('test5');
    $link['enabled'] = FALSE;
    $this->treeStorage->save($link);

    $parameters = new MenuTreeParameters();
    $parameters->onlyEnabledLinks();
    $data = $this->treeStorage->loadTreeData('tools', $parameters);
    $this->assertCount(1, $data['tree']);
    $this->assertEquals('test1', $data['tree']['test1']['definition']['id']);
    $this->assertCount(1, $data['tree']['test1']['subtree']);
    $this->assertEquals('test2', $data['tree']['test1']['subtree']['test2']['definition']['id']);
    $this->assertEquals([], $data['tree']['test1']['subtree']['test2']['subtree']);

  }

  /**
   * Tests finding the subtree height with content menu links.
   */
  public function testSubtreeHeight(): void {
    // root
    // - child1
    // -- child2
    // --- child3
    // ---- child4
    $this->addMenuLink('root');
    $this->addMenuLink('child1', 'root');
    $this->addMenuLink('child2', 'child1');
    $this->addMenuLink('child3', 'child2');
    $this->addMenuLink('child4', 'child3');

    $this->assertEquals(5, $this->treeStorage->getSubtreeHeight('root'));
    $this->assertEquals(4, $this->treeStorage->getSubtreeHeight('child1'));
    $this->assertEquals(3, $this->treeStorage->getSubtreeHeight('child2'));
    $this->assertEquals(2, $this->treeStorage->getSubtreeHeight('child3'));
    $this->assertEquals(1, $this->treeStorage->getSubtreeHeight('child4'));
  }

  /**
   * Ensure hierarchy persists after a menu rebuild.
   */
  public function testMenuRebuild(): void {
    // root
    // - child1
    // -- child2
    // --- child3
    // ---- child4
    $this->addMenuLink('root');
    $this->addMenuLink('child1', 'root');
    $this->addMenuLink('child2', 'child1');
    $this->addMenuLink('child3', 'child2');
    $this->addMenuLink('child4', 'child3');

    $this->assertEquals(5, $this->treeStorage->getSubtreeHeight('root'));
    $this->assertEquals(4, $this->treeStorage->getSubtreeHeight('child1'));
    $this->assertEquals(3, $this->treeStorage->getSubtreeHeight('child2'));
    $this->assertEquals(2, $this->treeStorage->getSubtreeHeight('child3'));
    $this->assertEquals(1, $this->treeStorage->getSubtreeHeight('child4'));

    // Intentionally leave child3 out to mimic static or external links.
    $definitions = $this->treeStorage->loadMultiple(['root', 'child1', 'child2', 'child4']);
    $this->treeStorage->rebuild($definitions);
    $this->assertEquals(5, $this->treeStorage->getSubtreeHeight('root'));
    $this->assertEquals(4, $this->treeStorage->getSubtreeHeight('child1'));
    $this->assertEquals(3, $this->treeStorage->getSubtreeHeight('child2'));
    $this->assertEquals(2, $this->treeStorage->getSubtreeHeight('child3'));
    $this->assertEquals(1, $this->treeStorage->getSubtreeHeight('child4'));
  }

  /**
   * Tests MenuTreeStorage::loadByProperties().
   */
  public function testLoadByProperties(): void {
    $tests = [
      ['foo' => 'bar'],
      [0 => 'wrong'],
    ];
    $message = 'An invalid property name throws an exception.';
    foreach ($tests as $properties) {
      try {
        $this->treeStorage->loadByProperties($properties);
        $this->fail($message);
      }
      catch (\InvalidArgumentException $e) {
        $this->assertMatchesRegularExpression('/^An invalid property name, .+ was specified. Allowed property names are:/', $e->getMessage(), 'Found expected exception message.');
      }
    }
    $this->addMenuLink('test_link.1', '', 'test', [], 'menu1');
    $properties = ['menu_name' => 'menu1'];
    $links = $this->treeStorage->loadByProperties($properties);
    $this->assertEquals('menu1', $links['test_link.1']['menu_name']);
    $this->assertEquals('test', $links['test_link.1']['route_name']);
  }

  /**
   * Adds a link with the given ID and supply defaults.
   */
  protected function addMenuLink($id, $parent = '', $route_name = 'test', $route_parameters = [], $menu_name = 'tools', $extra = []): void {
    $link = [
      'id' => $id,
      'menu_name' => $menu_name,
      'route_name' => $route_name,
      'route_parameters' => $route_parameters,
      'title' => 'test',
      'parent' => $parent,
      'options' => [],
      'metadata' => [],
    ] + $extra;
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
  protected function moveMenuLink($id, $new_parent): void {
    $menu_link = $this->treeStorage->load($id);
    $menu_link['parent'] = $new_parent;
    $this->treeStorage->save($menu_link);
  }

  /**
   * Tests that a link's stored representation matches the expected values.
   *
   * @param string $id
   *   The ID of the menu link to test.
   * @param array $expected_properties
   *   A keyed array of column names and values like has_children and depth.
   * @param array $parents
   *   An ordered array of the IDs of the menu links that are the parents.
   * @param array $children
   *   Array of child IDs that are visible (enabled == 1).
   *
   * @internal
   */
  protected function assertMenuLink(string $id, array $expected_properties, array $parents = [], array $children = []): void {
    $query = $this->connection->select('menu_tree');
    $query->fields('menu_tree');
    $query->condition('id', $id);
    foreach ($expected_properties as $field => $value) {
      $query->condition($field, $value);
    }
    $all = $query->execute()->fetchAll(FetchAs::Associative);
    $this->assertCount(1, $all, "Found link $id matching all the expected properties");
    $raw = reset($all);

    // Put the current link onto the front.
    array_unshift($parents, $raw['id']);

    $query = $this->connection->select('menu_tree');
    $query->fields('menu_tree', ['id', 'mlid']);
    $query->condition('id', $parents, 'IN');
    $found_parents = $query->execute()->fetchAllKeyed(0, 1);

    $this->assertSameSize($parents, $found_parents, 'Found expected number of parents');
    $this->assertCount((int) $raw['depth'], $found_parents, 'Number of parents is the same as the depth');

    $materialized_path = $this->treeStorage->getRootPathIds($id);
    $this->assertEquals(array_values($parents), array_values($materialized_path), 'Parents match the materialized path');
    // Check that the selected mlid values of the parents are in the correct
    // column, including the link's own.
    for ($i = $raw['depth']; $i >= 1; $i--) {
      $parent_id = array_shift($parents);
      $this->assertEquals($found_parents[$parent_id], $raw["p{$i}"], "mlid of parent matches at column p{$i}");
    }
    for ($i = $raw['depth'] + 1; $i <= $this->treeStorage->maxDepth(); $i++) {
      $this->assertEquals(0, $raw["p{$i}"], "parent is 0 at column p{$i} greater than depth");
    }
    if ($parents) {
      $this->assertEquals(end($parents), $raw['parent'], 'Ensure that the parent field is set properly');
    }
    // Verify that the child IDs match.
    $this->assertEqualsCanonicalizing($children, array_keys($this->treeStorage->loadAllChildren($id)));
  }

}

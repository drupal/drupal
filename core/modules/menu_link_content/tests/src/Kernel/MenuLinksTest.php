<?php

namespace Drupal\Tests\menu_link_content\Kernel;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\entity_test\Entity\EntityTestExternal;
use Drupal\KernelTests\KernelTestBase;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\system\Entity\Menu;
use Drupal\user\Entity\User;

/**
 * Tests handling of menu links hierarchies.
 *
 * @group Menu
 */
class MenuLinksTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'link',
    'menu_link_content',
    'router_test',
    'system',
    'user',
  ];

  /**
   * The menu link plugin manager.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuLinkManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->menuLinkManager = \Drupal::service('plugin.manager.menu.link');

    $this->installSchema('system', ['sequences']);
    $this->installSchema('user', ['users_data']);
    $this->installEntitySchema('entity_test_external');
    $this->installEntitySchema('menu_link_content');
    $this->installEntitySchema('user');

    Menu::create([
      'id' => 'menu_test',
      'label' => 'Test menu',
      'description' => 'Description text',
    ])->save();
  }

  /**
   * Create a simple hierarchy of links.
   */
  public function createLinkHierarchy($module = 'menu_test') {
    // First remove all the menu links in the menu.
    $this->menuLinkManager->deleteLinksInMenu('menu_test');

    // Then create a simple link hierarchy:
    // - parent
    //   - child-1
    //     - child-1-1
    //     - child-1-2
    //   - child-2
    $base_options = [
      'title' => 'Menu link test',
      'provider' => $module,
      'menu_name' => 'menu_test',
    ];

    $parent = $base_options + [
      'link' => ['uri' => 'internal:/menu-test/hierarchy/parent'],
    ];
    $link = MenuLinkContent::create($parent);
    $link->save();
    $links['parent'] = $link->getPluginId();

    $child_1 = $base_options + [
      'link' => ['uri' => 'internal:/menu-test/hierarchy/parent/child'],
      'parent' => $links['parent'],
    ];
    $link = MenuLinkContent::create($child_1);
    $link->save();
    $links['child-1'] = $link->getPluginId();

    $child_1_1 = $base_options + [
      'link' => ['uri' => 'internal:/menu-test/hierarchy/parent/child2/child'],
      'parent' => $links['child-1'],
    ];
    $link = MenuLinkContent::create($child_1_1);
    $link->save();
    $links['child-1-1'] = $link->getPluginId();

    $child_1_2 = $base_options + [
      'link' => ['uri' => 'internal:/menu-test/hierarchy/parent/child2/child'],
      'parent' => $links['child-1'],
    ];
    $link = MenuLinkContent::create($child_1_2);
    $link->save();
    $links['child-1-2'] = $link->getPluginId();

    $child_2 = $base_options + [
      'link' => ['uri' => 'internal:/menu-test/hierarchy/parent/child'],
      'parent' => $links['parent'],
    ];
    $link = MenuLinkContent::create($child_2);
    $link->save();
    $links['child-2'] = $link->getPluginId();

    return $links;
  }

  /**
   * Assert that at set of links is properly parented.
   *
   * @internal
   */
  public function assertMenuLinkParents(array $links, array $expected_hierarchy): void {
    foreach ($expected_hierarchy as $id => $parent) {
      /** @var \Drupal\Core\Menu\MenuLinkInterface $menu_link_plugin  */
      $menu_link_plugin = $this->menuLinkManager->createInstance($links[$id]);
      $expected_parent = $links[$parent] ?? '';

      $this->assertEquals($expected_parent, $menu_link_plugin->getParent(), new FormattableMarkup('Menu link %id has parent of %parent, expected %expected_parent.', ['%id' => $id, '%parent' => $menu_link_plugin->getParent(), '%expected_parent' => $expected_parent]));
    }
  }

  /**
   * Assert that a link entity's created timestamp is set.
   */
  public function testCreateLink() {
    $options = [
      'menu_name' => 'menu_test',
      'bundle' => 'menu_link_content',
      'link' => [['uri' => 'internal:/']],
      'title' => 'Link test',
    ];
    $link = MenuLinkContent::create($options);
    $link->save();
    // Make sure the changed timestamp is set.
    $this->assertEquals(REQUEST_TIME, $link->getChangedTime(), 'Creating a menu link sets the "changed" timestamp.');
    $options = [
      'title' => 'Test Link',
    ];
    $link->link->options = $options;
    $link->changed->value = 0;
    $link->save();
    // Make sure the changed timestamp is updated.
    $this->assertEquals(REQUEST_TIME, $link->getChangedTime(), 'Changing a menu link sets "changed" timestamp.');
  }

  /**
   * Tests that menu link pointing to entities get removed on entity remove.
   */
  public function testMenuLinkOnEntityDelete() {

    // Create user.
    $user = User::create(['name' => 'username']);
    $user->save();

    // Create External test entity.
    $external_entity = EntityTestExternal::create();
    $external_entity->save();
    // Ensure an external entity can be deleted.
    $external_entity->delete();

    // Create "canonical" menu link pointing to the user.
    $menu_link_content = MenuLinkContent::create([
      'title' => 'username profile',
      'menu_name' => 'menu_test',
      'link' => [['uri' => 'entity:user/' . $user->id()]],
      'bundle' => 'menu_test',
    ]);
    $menu_link_content->save();

    // Create "collection" menu link pointing to the user listing page.
    $menu_link_content_collection = MenuLinkContent::create([
      'title' => 'users listing',
      'menu_name' => 'menu_test',
      'link' => [['uri' => 'internal:/' . $user->toUrl('collection')->getInternalPath()]],
      'bundle' => 'menu_test',
    ]);
    $menu_link_content_collection->save();

    // Check is menu links present in the menu.
    $menu_tree_condition = (new MenuTreeParameters())->addCondition('route_name', 'entity.user.canonical');
    $this->assertCount(1, \Drupal::menuTree()->load('menu_test', $menu_tree_condition));
    $menu_tree_condition_collection = (new MenuTreeParameters())->addCondition('route_name', 'entity.user.collection');
    $this->assertCount(1, \Drupal::menuTree()->load('menu_test', $menu_tree_condition_collection));

    // Delete the user.
    $user->delete();

    // The "canonical" menu item has to be deleted.
    $this->assertCount(0, \Drupal::menuTree()->load('menu_test', $menu_tree_condition));

    // The "collection" menu item should still present in the menu.
    $this->assertCount(1, \Drupal::menuTree()->load('menu_test', $menu_tree_condition_collection));
  }

  /**
   * Tests automatic reparenting of menu links.
   */
  public function testMenuLinkReparenting($module = 'menu_test') {
    // Check the initial hierarchy.
    $links = $this->createLinkHierarchy($module);

    $expected_hierarchy = [
      'parent' => '',
      'child-1' => 'parent',
      'child-1-1' => 'child-1',
      'child-1-2' => 'child-1',
      'child-2' => 'parent',
    ];
    $this->assertMenuLinkParents($links, $expected_hierarchy);

    // Start over, and move child-1 under child-2, and check that all the
    // children of child-1 have been moved too.
    $links = $this->createLinkHierarchy($module);
    $this->menuLinkManager->updateDefinition($links['child-1'], ['parent' => $links['child-2']]);
    // Verify that the entity was updated too.
    /** @var \Drupal\Core\Menu\MenuLinkInterface $menu_link_plugin  */
    $menu_link_plugin = $this->menuLinkManager->createInstance($links['child-1']);
    $entity = \Drupal::service('entity.repository')->loadEntityByUuid('menu_link_content', $menu_link_plugin->getDerivativeId());
    $this->assertEquals($links['child-2'], $entity->getParentId());

    $expected_hierarchy = [
      'parent' => '',
      'child-1' => 'child-2',
      'child-1-1' => 'child-1',
      'child-1-2' => 'child-1',
      'child-2' => 'parent',
    ];
    $this->assertMenuLinkParents($links, $expected_hierarchy);

    // Start over, and delete child-1, and check that the children of child-1
    // have been reassigned to the parent.
    $links = $this->createLinkHierarchy($module);
    $this->menuLinkManager->removeDefinition($links['child-1']);

    $expected_hierarchy = [
      'parent' => FALSE,
      'child-1-1' => 'parent',
      'child-1-2' => 'parent',
      'child-2' => 'parent',
    ];
    $this->assertMenuLinkParents($links, $expected_hierarchy);

    // Try changing the parent at the entity level.
    $definition = $this->menuLinkManager->getDefinition($links['child-1-2']);
    $entity = MenuLinkContent::load($definition['metadata']['entity_id']);
    $entity->parent->value = '';
    $entity->save();

    $expected_hierarchy = [
      'parent' => '',
      'child-1-1' => 'parent',
      'child-1-2' => '',
      'child-2' => 'parent',
    ];
    $this->assertMenuLinkParents($links, $expected_hierarchy);

    // @todo Figure out what makes sense to test in terms of automatic
    //   re-parenting. https://www.drupal.org/node/2309531
  }

  /**
   * Tests the MenuLinkContent::preDelete function.
   */
  public function testMenuLinkContentReparenting() {
    // Add new menu items in a hierarchy.
    $parent = MenuLinkContent::create([
      'title' => $this->randomMachineName(8),
      'link' => [['uri' => 'internal:/']],
      'menu_name' => 'main',
    ]);
    $parent->save();
    $child1 = MenuLinkContent::create([
      'title' => $this->randomMachineName(8),
      'link' => [['uri' => 'internal:/']],
      'menu_name' => 'main',
      'parent' => 'menu_link_content:' . $parent->uuid(),
    ]);
    $child1->save();
    $child2 = MenuLinkContent::create([
      'title' => $this->randomMachineName(8),
      'link' => [['uri' => 'internal:/']],
      'menu_name' => 'main',
      'parent' => 'menu_link_content:' . $child1->uuid(),
    ]);
    $child2->save();

    // Delete the middle child.
    $child1->delete();
    // Refresh $child2.
    $child2 = MenuLinkContent::load($child2->id());
    // Test the reference in the child.
    $this->assertSame('menu_link_content:' . $parent->uuid(), $child2->getParentId());
  }

  /**
   * Tests uninstalling a module providing default links.
   */
  public function testModuleUninstalledMenuLinks() {
    \Drupal::service('module_installer')->install(['menu_test']);
    \Drupal::service('plugin.manager.menu.link')->rebuild();
    $menu_links = $this->menuLinkManager->loadLinksByRoute('menu_test.menu_test');
    $this->assertCount(1, $menu_links);
    $menu_link = reset($menu_links);
    $this->assertEquals('menu_test', $menu_link->getPluginId());

    // Uninstall the module and ensure the menu link got removed.
    \Drupal::service('module_installer')->uninstall(['menu_test']);
    \Drupal::service('plugin.manager.menu.link')->rebuild();
    $menu_links = $this->menuLinkManager->loadLinksByRoute('menu_test.menu_test');
    $this->assertCount(0, $menu_links);
  }

  /**
   * Tests handling of pending revisions.
   *
   * @coversDefaultClass \Drupal\menu_link_content\Plugin\Validation\Constraint\MenuTreeHierarchyConstraintValidator
   */
  public function testPendingRevisions() {
    /** @var \Drupal\Core\Entity\RevisionableStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('menu_link_content');

    // Add new menu items in a hierarchy.
    $default_root_1_title = $this->randomMachineName(8);
    $root_1 = $storage->create([
      'title' => $default_root_1_title,
      'link' => [['uri' => 'internal:/#root_1']],
      'menu_name' => 'menu_test',
    ]);
    $root_1->save();
    $default_child1_title = $this->randomMachineName(8);
    $child1 = $storage->create([
      'title' => $default_child1_title,
      'link' => [['uri' => 'internal:/#child1']],
      'menu_name' => 'menu_test',
      'parent' => 'menu_link_content:' . $root_1->uuid(),
    ]);
    $child1->save();
    $default_child2_title = $this->randomMachineName(8);
    $child2 = $storage->create([
      'title' => $default_child2_title,
      'link' => [['uri' => 'internal:/#child2']],
      'menu_name' => 'menu_test',
      'parent' => 'menu_link_content:' . $child1->uuid(),
    ]);
    $child2->save();
    $default_root_2_title = $this->randomMachineName(8);
    $root_2 = $storage->create([
      'title' => $default_root_2_title,
      'link' => [['uri' => 'internal:/#root_2']],
      'menu_name' => 'menu_test',
    ]);
    $root_2->save();

    // Check that changing the title and the link in a pending revision is
    // allowed.
    $pending_child1_title = $this->randomMachineName(8);
    $child1_pending_revision = $storage->createRevision($child1, FALSE);
    $child1_pending_revision->set('title', $pending_child1_title);
    $child1_pending_revision->set('link', [['uri' => 'internal:/#test']]);

    $violations = $child1_pending_revision->validate();
    $this->assertEmpty($violations);
    $child1_pending_revision->save();

    $storage->resetCache();
    $child1_pending_revision = $storage->loadRevision($child1_pending_revision->getRevisionId());
    $this->assertFalse($child1_pending_revision->isDefaultRevision());
    $this->assertEquals($pending_child1_title, $child1_pending_revision->getTitle());
    $this->assertEquals('/#test', $child1_pending_revision->getUrlObject()->toString());

    // Check that saving a pending revision does not affect the menu tree.
    $menu_tree = \Drupal::menuTree()->load('menu_test', new MenuTreeParameters());
    $parent_link = reset($menu_tree);
    $this->assertEquals($default_root_1_title, $parent_link->link->getTitle());
    $this->assertEquals('/#root_1', $parent_link->link->getUrlObject()->toString());

    $child1_link = reset($parent_link->subtree);
    $this->assertEquals($default_child1_title, $child1_link->link->getTitle());
    $this->assertEquals('/#child1', $child1_link->link->getUrlObject()->toString());

    $child2_link = reset($child1_link->subtree);
    $this->assertEquals($default_child2_title, $child2_link->link->getTitle());
    $this->assertEquals('/#child2', $child2_link->link->getUrlObject()->toString());

    // Check that changing the parent in a pending revision is not allowed.
    $child2_pending_revision = $storage->createRevision($child2, FALSE);
    $child2_pending_revision->set('parent', $child1->id());
    $violations = $child2_pending_revision->validate();
    $this->assertCount(1, $violations);
    $this->assertEquals('You can only change the hierarchy for the published version of this menu link.', $violations[0]->getMessage());
    $this->assertEquals('menu_parent', $violations[0]->getPropertyPath());

    // Check that changing the weight in a pending revision is not allowed.
    $child2_pending_revision = $storage->createRevision($child2, FALSE);
    $child2_pending_revision->set('weight', 500);
    $violations = $child2_pending_revision->validate();
    $this->assertCount(1, $violations);
    $this->assertEquals('You can only change the hierarchy for the published version of this menu link.', $violations[0]->getMessage());
    $this->assertEquals('weight', $violations[0]->getPropertyPath());

    // Check that changing both the parent and the weight in a pending revision
    // is not allowed.
    $child2_pending_revision = $storage->createRevision($child2, FALSE);
    $child2_pending_revision->set('parent', $child1->id());
    $child2_pending_revision->set('weight', 500);
    $violations = $child2_pending_revision->validate();
    $this->assertCount(2, $violations);
    $this->assertEquals('You can only change the hierarchy for the published version of this menu link.', $violations[0]->getMessage());
    $this->assertEquals('You can only change the hierarchy for the published version of this menu link.', $violations[1]->getMessage());
    $this->assertEquals('menu_parent', $violations[0]->getPropertyPath());
    $this->assertEquals('weight', $violations[1]->getPropertyPath());

    // Check that changing the parent of a term which didn't have a parent
    // initially is not allowed in a pending revision.
    $root_2_pending_revision = $storage->createRevision($root_2, FALSE);
    $root_2_pending_revision->set('parent', $root_1->id());
    $violations = $root_2_pending_revision->validate();
    $this->assertCount(1, $violations);
    $this->assertEquals('You can only change the hierarchy for the published version of this menu link.', $violations[0]->getMessage());
    $this->assertEquals('menu_parent', $violations[0]->getPropertyPath());
  }

}

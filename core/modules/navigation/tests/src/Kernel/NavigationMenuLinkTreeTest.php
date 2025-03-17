<?php

declare(strict_types=1);

namespace Drupal\Tests\navigation\Kernel;

use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\KernelTests\KernelTestBase;
use Drupal\navigation\Menu\NavigationMenuLinkTree;

/**
 * Tests \Drupal\navigation\Menu\NavigationMenuLinkTree.
 *
 * @group navigation
 *
 * @see \Drupal\navigation\Menu\NavigationMenuLinkTree
 */
class NavigationMenuLinkTreeTest extends KernelTestBase {

  /**
   * The tested navigation menu link tree.
   *
   * @var \Drupal\navigation\Menu\NavigationMenuLinkTree
   */
  protected NavigationMenuLinkTree $linkTree;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'layout_builder',
    'layout_discovery',
    'link',
    'menu_link_content',
    'menu_test',
    'navigation',
    'navigation_test',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('menu_link_content');

    $this->linkTree = $this->container->get('navigation.menu_tree');
  }

  /**
   * Tests the hook_navigation_menu_link_tree_alter logic.
   */
  public function testNavigationMenuLinkTreeAlter(): void {
    /** @var \Drupal\system\MenuStorage $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('menu');
    $storage->create(['id' => 'menu1', 'label' => 'Menu 1'])->save();
    $storage->create(['id' => 'menu2', 'label' => 'Menu 2'])->save();

    \Drupal::entityTypeManager()
      ->getStorage('menu_link_content')
      ->create([
        'link' => ['uri' => 'internal:/menu_name_test'],
        'menu_name' => 'menu1',
        'bundle' => 'menu_link_content',
        'title' => 'Link test',
      ])->save();
    \Drupal::entityTypeManager()
      ->getStorage('menu_link_content')
      ->create([
        'link' => ['uri' => 'internal:/menu_name_test'],
        'menu_name' => 'menu1',
        'bundle' => 'menu_link_content',
        'title' => 'Link test',
      ])->save();
    \Drupal::entityTypeManager()
      ->getStorage('menu_link_content')
      ->create([
        'link' => ['uri' => 'internal:/menu_name_test'],
        'menu_name' => 'menu2',
        'bundle' => 'menu_link_content',
        'title' => 'Link test',
      ])->save();

    $output = $this->linkTree->load('menu1', new MenuTreeParameters());
    $this->assertCount(2, $output);
    $output = $this->linkTree->transform($output, []);
    $this->assertCount(0, $output);
    $output = $this->linkTree->load('menu2', new MenuTreeParameters());
    $this->assertCount(1, $output);
    $output = $this->linkTree->transform($output, []);
    $this->assertCount(1, $output);
    $item = reset($output);
    $this->assertSame('New Link Title', $item->link->getTitle());
  }

}

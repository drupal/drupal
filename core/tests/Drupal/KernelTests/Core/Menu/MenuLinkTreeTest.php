<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Menu;

use Drupal\Core\Menu\InaccessibleMenuLink;
use Drupal\Core\Menu\MenuLinkTreeElement;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\Session\UserSession;
use Drupal\KernelTests\KernelTestBase;
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
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuLinkManager;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'menu_test',
    'menu_link_content',
    'field',
    'link',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('menu_link_content');

    $this->linkTree = $this->container->get('menu.link_tree');
    $this->menuLinkManager = $this->container->get('plugin.manager.menu.link');
  }

  /**
   * Tests deleting all the links in a menu.
   */
  public function testDeleteLinksInMenu(): void {
    /** @var \Drupal\system\MenuStorage $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('menu');
    $storage->create(['id' => 'menu1', 'label' => 'Menu 1'])->save();
    $storage->create(['id' => 'menu2', 'label' => 'Menu 2'])->save();

    \Drupal::entityTypeManager()->getStorage('menu_link_content')->create(['link' => ['uri' => 'internal:/menu_name_test'], 'menu_name' => 'menu1', 'bundle' => 'menu_link_content', 'title' => 'Link test'])->save();
    \Drupal::entityTypeManager()->getStorage('menu_link_content')->create(['link' => ['uri' => 'internal:/menu_name_test'], 'menu_name' => 'menu1', 'bundle' => 'menu_link_content', 'title' => 'Link test'])->save();
    \Drupal::entityTypeManager()->getStorage('menu_link_content')->create(['link' => ['uri' => 'internal:/menu_name_test'], 'menu_name' => 'menu2', 'bundle' => 'menu_link_content', 'title' => 'Link test'])->save();

    $output = $this->linkTree->load('menu1', new MenuTreeParameters());
    $this->assertCount(2, $output);
    $output = $this->linkTree->load('menu2', new MenuTreeParameters());
    $this->assertCount(1, $output);

    $this->menuLinkManager->deleteLinksInMenu('menu1');

    $output = $this->linkTree->load('menu1', new MenuTreeParameters());
    $this->assertCount(0, $output);

    $output = $this->linkTree->load('menu2', new MenuTreeParameters());
    $this->assertCount(1, $output);
  }

  /**
   * Tests creating links with an expected tree structure.
   */
  public function testCreateLinksInMenu(): void {
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

    $links = [
      1 => MenuLinkMock::create(['id' => 'test.example1', 'route_name' => 'example1', 'title' => 'foo', 'parent' => '']),
      2 => MenuLinkMock::create(['id' => 'test.example2', 'route_name' => 'example2', 'title' => 'bar', 'parent' => 'test.example1', 'route_parameters' => ['foo' => 'bar']]),
      3 => MenuLinkMock::create(['id' => 'test.example3', 'route_name' => 'example3', 'title' => 'baz', 'parent' => 'test.example2', 'route_parameters' => ['baz' => 'qux']]),
      4 => MenuLinkMock::create(['id' => 'test.example4', 'route_name' => 'example4', 'title' => 'qux', 'parent' => 'test.example3']),
      5 => MenuLinkMock::create(['id' => 'test.example5', 'route_name' => 'example5', 'title' => 'title5', 'parent' => '']),
      6 => MenuLinkMock::create(['id' => 'test.example6', 'route_name' => '', 'url' => 'https://www.drupal.org/', 'title' => 'bar_bar', 'parent' => '']),
      7 => MenuLinkMock::create(['id' => 'test.example7', 'route_name' => 'example7', 'title' => 'title7', 'parent' => '']),
      8 => MenuLinkMock::create(['id' => 'test.example8', 'route_name' => 'example8', 'title' => 'title8', 'parent' => '']),
    ];
    foreach ($links as $instance) {
      $this->menuLinkManager->addDefinition($instance->getPluginId(), $instance->getPluginDefinition());
    }
    $parameters = new MenuTreeParameters();
    $tree = $this->linkTree->load('mock', $parameters);

    $count = function (array $tree) {
      $sum = function ($carry, MenuLinkTreeElement $item) {
        return $carry + $item->count();
      };
      return array_reduce($tree, $sum);
    };

    $this->assertEquals(8, $count($tree));
    $parameters = new MenuTreeParameters();
    $parameters->setRoot('test.example2');
    $tree = $this->linkTree->load($instance->getMenuName(), $parameters);
    $top_link = reset($tree);
    $this->assertCount(1, $top_link->subtree);
    $child = reset($top_link->subtree);
    $this->assertEquals($links[3]->getPluginId(), $child->link->getPluginId());
    $height = $this->linkTree->getSubtreeHeight('test.example2');
    $this->assertEquals(3, $height);
  }

  /**
   * Tests user/login and user/logout links.
   */
  public function testUserLoginAndUserLogoutLinks(): void {
    $account_switcher = $this->container->get('account_switcher');

    $login_menu_link = MenuLinkMock::create(['id' => 'user_login_example', 'route_name' => 'user.login']);
    $logout_menu_link = MenuLinkMock::create(['id' => 'user_logout_example', 'route_name' => 'user.logout']);

    $this->menuLinkManager->addDefinition($login_menu_link->getPluginId(), $login_menu_link->getPluginDefinition());
    $this->menuLinkManager->addDefinition($logout_menu_link->getPluginId(), $logout_menu_link->getPluginDefinition());

    // Returns the accessible links from transformed 'mock' menu tree.
    $get_accessible_links = function () {
      $parameters = new MenuTreeParameters();
      $manipulators = [
        ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ];

      $tree = $this->linkTree->load('mock', $parameters);
      $this->linkTree->transform($tree, $manipulators);

      return array_keys(
        array_filter($tree, function (MenuLinkTreeElement $element) {
          return !$element->link instanceof InaccessibleMenuLink;
        })
      );
    };

    // Check that anonymous can only access the login link.
    $account_switcher->switchTo(new AnonymousUserSession());
    $this->assertSame(['user_login_example'], $get_accessible_links());

    // Ensure that also user 1 does not see the login link.
    $account_switcher->switchTo(new UserSession(['uid' => 1]));
    $this->assertSame(['user_logout_example'], $get_accessible_links());
  }

}

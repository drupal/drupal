<?php

namespace Drupal\Tests\menu_link_content\Kernel;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\KernelTests\KernelTestBase;

/**
 * Ensures that the menu tree adapts to path alias changes.
 *
 * @group menu_link_content
 */
class PathAliasMenuLinkContentTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['menu_link_content', 'system', 'link', 'test_page_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('menu_link_content');

    // Ensure that the weight of module_link_content is higher than system.
    // @see menu_link_content_install()
    module_set_weight('menu_link_content', 1);
  }

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    parent::register($container);

    $definition = $container->getDefinition('path_processor_alias');
    $definition
      ->addTag('path_processor_inbound', ['priority' => 100]);
  }


  /**
   * Tests the path aliasing changing.
   */
  public function testPathAliasChange() {
    \Drupal::service('router.builder')->rebuild();

    /** @var \Drupal\Core\Path\AliasStorageInterface $path_alias_storage */
    $path_alias_storage = \Drupal::service('path.alias_storage');
    $alias = $path_alias_storage->save('/test-page', '/my-blog');
    $pid = $alias['pid'];

    $menu_link_content = MenuLinkContent::create([
      'title' => 'Menu title',
      'link' => ['uri' => 'internal:/my-blog'],
      'menu_name' => 'tools',
    ]);
    $menu_link_content->save();

    $tree = \Drupal::menuTree()->load('tools', new MenuTreeParameters());
    $this->assertEqual('test_page_test.test_page', $tree[$menu_link_content->getPluginId()]->link->getPluginDefinition()['route_name']);

    // Saving an alias should clear the alias manager cache.
    $path_alias_storage->save('/test-render-title', '/my-blog', LanguageInterface::LANGCODE_NOT_SPECIFIED, $pid);

    $tree = \Drupal::menuTree()->load('tools', new MenuTreeParameters());
    $this->assertEqual('test_page_test.render_title', $tree[$menu_link_content->getPluginId()]->link->getPluginDefinition()['route_name']);

    // Delete the alias.
    $path_alias_storage->delete(['pid' => $pid]);
    $tree = \Drupal::menuTree()->load('tools', new MenuTreeParameters());
    $this->assertTrue(isset($tree[$menu_link_content->getPluginId()]));
    $this->assertEqual('', $tree[$menu_link_content->getPluginId()]->link->getRouteName());
    // Verify the plugin now references a path that does not match any route.
    $this->assertEqual('base:my-blog', $tree[$menu_link_content->getPluginId()]->link->getUrlObject()->getUri());
  }

}

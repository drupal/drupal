<?php

declare(strict_types=1);

namespace Drupal\Tests\navigation\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests \Drupal\navigation\NavigationContentLinks.
 *
 * @group navigation
 * @see \Drupal\navigation\NavigationContentLinks
 */
class NavigationContentLinksTest extends KernelTestBase {

  use ContentTypeCreationTrait;
  use MediaTypeCreationTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'navigation',
    'node',
    'file',
    'media',
    'layout_builder',
    'system',
    'views',
    'user',
    'field',
    'media_test_source',
    'image',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installConfig(['node', 'file']);

    $this->createContentType(['type' => 'article']);
    $this->createContentType(['type' => 'blog']);
    $this->createContentType(['type' => 'landing_page']);

    $this->installEntitySchema('media');
    $this->createMediaType('test', ['id' => 'document', 'label' => 'Document']);
    $this->createMediaType('test', ['id' => 'image', 'label' => 'Image']);
    $this->createMediaType('test', ['id' => 'special', 'label' => 'Special']);
  }

  /**
   * Tests if the expected navigation content links are added/removed correctly.
   */
  public function testNavigationContentLinks(): void {
    $module_installer = \Drupal::service('module_installer');
    $menu_link_manager = \Drupal::service('plugin.manager.menu.link');
    $links = $menu_link_manager->getDefinitions();

    // Assert that the "Create" link is added to the menu.
    $this->assertArrayHasKey('navigation.create', $links);
    $this->assertEquals('node.add_page', $links['navigation.create']['route_name']);
    $this->assertEquals('Create', $links['navigation.create']['title']);

    // Assert that the "Content" link is added to the menu.
    $this->assertArrayHasKey('navigation.content', $links);
    $this->assertEquals('system.admin_content', $links['navigation.content']['route_name']);
    $this->assertEquals('Content', $links['navigation.content']['title']);

    // Assert that the "Article" submenu link is added to the menu.
    $this->assertArrayHasKey('navigation.content.node_type.article', $links);
    $this->assertEquals('node.add', $links['navigation.content.node_type.article']['route_name']);
    $this->assertEquals('article', $links['navigation.content.node_type.article']['title']);

    // Assert that the "Blog" submenu link is added to the menu.
    $this->assertArrayHasKey('navigation.content.node_type.blog', $links);
    $this->assertEquals('node.add', $links['navigation.content.node_type.blog']['route_name']);
    $this->assertEquals('blog', $links['navigation.content.node_type.blog']['title']);

    // Assert that the "Landing Page" submenu link is added to the menu.
    $this->assertArrayHasKey('navigation.content.node_type.landing_page', $links);
    $this->assertEquals('node.add', $links['navigation.content.node_type.landing_page']['route_name']);
    $this->assertEquals('landing_page', $links['navigation.content.node_type.landing_page']['title']);

    // Assert that the "Create User" submenu link is added to the menu.
    $this->assertArrayHasKey('navigation.create.user', $links);
    $this->assertEquals('user.admin_create', $links['navigation.create.user']['route_name']);
    $this->assertEquals('User', $links['navigation.create.user']['title']);

    // Assert that the "Document" media type link is added to the menu.
    $this->assertArrayHasKey('navigation.content.media_type.document', $links);
    $this->assertEquals('entity.media.add_form', $links['navigation.content.media_type.document']['route_name']);
    $this->assertEquals('Document', $links['navigation.content.media_type.document']['title']);

    // Assert that the "Image" media type link is added to the menu.
    $this->assertArrayHasKey('navigation.content.media_type.image', $links);
    $this->assertEquals('entity.media.add_form', $links['navigation.content.media_type.image']['route_name']);
    $this->assertEquals('Image', $links['navigation.content.media_type.image']['title']);

    // Assert that the "Special" media type link is not added to the menu.
    $this->assertArrayNotHasKey('navigation.content.media_type.special', $links);

    // Assert that the "Media" link is added.
    $this->assertArrayHasKey('navigation.media', $links);
    $this->assertEquals('entity.media.collection', $links['navigation.media']['route_name']);
    $this->assertEquals('Media', $links['navigation.media']['title']);

    // Assert that the "Files" link is added.
    $this->assertArrayHasKey('navigation.files', $links);
    $this->assertEquals('view.files.page_1', $links['navigation.files']['route_name']);
    $this->assertEquals('Files', $links['navigation.files']['title']);

    // Assert that "Blocks" link is not added.
    $this->assertArrayNotHasKey('navigation.blocks', $links);

    // Install the block_content module and rebuild the menu links.
    $module_installer->install(['block_content']);
    // Rebuild the links after module installation.
    $menu_link_manager->rebuild();
    $links = $menu_link_manager->getDefinitions();

    // Assert that "Blocks" link is added.
    $this->assertArrayHasKey('navigation.blocks', $links);
    $this->assertEquals('entity.block_content.collection', $links['navigation.blocks']['route_name']);
    $this->assertEquals('Blocks', $links['navigation.blocks']['title']);
  }

}

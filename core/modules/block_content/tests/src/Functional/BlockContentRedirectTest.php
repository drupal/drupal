<?php

namespace Drupal\Tests\block_content\Functional;

/**
 * Ensures that custom block type functions work correctly.
 *
 * @group block_content
 * @group legacy
 */
class BlockContentRedirectTest extends BlockContentTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the deprecation message from the old block-type page.
   *
   * @group legacy
   */
  public function testBlockContentTypeRedirect() {
    $this->drupalLogin($this->adminUser);
    $this->expectDeprecation('The path /admin/structure/block/block-content/types is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Use /admin/structure/block-content. See https://www.drupal.org/node/3320855');
    $this->drupalGet('/admin/structure/block/block-content/types');
    $this->assertSession()
      ->pageTextContains("You have been redirected from admin/structure/block/block-content/types. Update links, shortcuts, and bookmarks to use admin/structure/block-content.");
  }

  /**
   * Tests the deprecation message from the old block library page.
   *
   * @group legacy
   */
  public function testBlockLibraryRedirect() {
    $this->drupalLogin($this->adminUser);
    $this->expectDeprecation('The path /admin/structure/block/block-content is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Use /admin/content/block. See https://www.drupal.org/node/3320855');
    $this->drupalGet('admin/structure/block/block-content');
    $this->assertSession()
      ->pageTextContains("You have been redirected from admin/structure/block/block-content. Update links, shortcuts, and bookmarks to use admin/content/block.");
  }

  /**
   * Tests the deprecation message from the old block edit page.
   *
   * @group legacy
   */
  public function testBlockContentEditRedirect(): void {
    $block = $this->createBlockContent();
    $this->drupalLogin($this->adminUser);
    $this->expectDeprecation('The path /block/{block_content} is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Use /admin/content/block/{block_content}. See https://www.drupal.org/node/3320855');
    $this->drupalGet("/block/{$block->id()}");
    $this->assertSession()
      ->pageTextContains("You have been redirected from block/{$block->id()}. Update links, shortcuts, and bookmarks to use admin/content/block/{$block->id()}.");
  }

  /**
   * Tests the deprecation message from the old block delete page.
   *
   * @group legacy
   */
  public function testBlockContentDeleteRedirect(): void {
    $block = $this->createBlockContent();
    $this->drupalLogin($this->adminUser);
    $this->expectDeprecation('The path /block/{block_content} is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Use /admin/content/block/{block_content}. See https://www.drupal.org/node/3320855');
    $this->drupalGet("/block/{$block->id()}/delete");
    $this->assertSession()
      ->pageTextContains("You have been redirected from block/{$block->id()}/delete. Update links, shortcuts, and bookmarks to use admin/content/block/{$block->id()}/delete.");
  }

}

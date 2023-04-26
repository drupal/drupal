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
  public function testBlockContentEditRedirect(): void {
    $block = $this->createBlockContent();
    $this->drupalLogin($this->adminUser);
    $this->expectDeprecation('The path /block/{block_content} is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Use /admin/content/block/{block_content}. See https://www.drupal.org/node/2317981.');
    $this->drupalGet("/block/{$block->id()}");
    $base_path = parse_url($this->baseUrl, PHP_URL_PATH) ?? '';
    $this->assertSession()
      ->pageTextContains("You have been redirected from $base_path/block/{$block->id()}. Update links, shortcuts, and bookmarks to use $base_path/admin/content/block/{$block->id()}.");
  }

  /**
   * Tests the deprecation message from the old block library page.
   *
   * @group legacy
   */
  public function testBlockContentDeleteRedirect(): void {
    $block = $this->createBlockContent();
    $this->drupalLogin($this->adminUser);
    $this->expectDeprecation('The path /block/{block_content}/delete is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. Use /admin/content/block/{block_content}/delete. See https://www.drupal.org/node/2317981.');
    $this->drupalGet("/block/{$block->id()}/delete");
    $base_path = parse_url($this->baseUrl, PHP_URL_PATH) ?? '';
    $this->assertSession()
      ->pageTextContains("You have been redirected from $base_path/block/{$block->id()}/delete. Update links, shortcuts, and bookmarks to use $base_path/admin/content/block/{$block->id()}/delete.");
  }

}

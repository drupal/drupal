<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Block;

use Drupal\block\BlockInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests clear cache block behavior.
 *
 * @group Block
 *
 * @see \Drupal\system\Plugin\Block\ClearCacheBlock
 */
class ClearCacheBlockTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The clear cache block instance.
   *
   * @var \Drupal\block\BlockInterface
   */
  protected BlockInterface $clearCacheBlock;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $admin_user = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($admin_user);
    $this->clearCacheBlock = $this->placeBlock('system_clear_cache_block', [
      'label' => 'Clear cache block',
    ]);
  }

  /**
   * Tests block behavior and access based on permissions.
   */
  public function testCacheClearBlock(): void {
    $this->drupalGet('<front>');
    $this->assertSession()->pageTextContains('Clear cache block');
    $page = $this->getSession()->getPage();
    $page->pressButton('Clear all caches');
    $this->assertSession()->statusMessageContains('Caches cleared.');

    // Confirm that access is not allowed for non-authorized users.
    $this->drupalLogout();
    $this->drupalGet('<front>');
    $this->assertSession()->pageTextNotContains('Clear cache block');
  }

}

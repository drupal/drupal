<?php

declare(strict_types=1);

namespace Drupal\Tests\block\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests block module's installation.
 *
 * @group block
 */
class BlockInstallTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests cache tag invalidation after installing the block module.
   */
  public function testCacheTagInvalidationUponInstallation(): void {
    // Warm the page cache.
    $this->drupalGet('');
    $this->assertSession()->pageTextNotContains('Powered by Drupal');
    $this->assertSession()->responseHeaderNotContains('X-Drupal-Cache-Tags', 'config:block_list');

    // Install the block module, and place the "Powered by Drupal" block.
    $this->container->get('module_installer')->install(['block', 'shortcut']);
    $this->rebuildContainer();
    $this->drupalPlaceBlock('system_powered_by_block');

    // Check the same page, block.module's hook_install() should have
    // invalidated the 'rendered' cache tag to make blocks show up.
    $this->drupalGet('');
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', 'config:block_list');
    $this->assertSession()->pageTextContains('Powered by Drupal');
  }

}

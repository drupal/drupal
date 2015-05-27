<?php

/**
 * @file
 * Contains \Drupal\block\Tests\BlockInstallTest.
 */

namespace Drupal\block\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests block module's installation.
 *
 * @group block
 */
class BlockInstallTest extends WebTestBase {

  public function testCacheTagInvalidationUponInstallation() {
    // Warm the page cache.
    $this->drupalGet('');
    $this->assertNoText('Powered by Drupal');
    $this->assertNoCacheTag('config:block_list');

    // Install the block module, and place the "Powered by Drupal" block.
    $this->container->get('module_installer')->install(['block', 'shortcut']);
    $this->rebuildContainer();
    $this->container->get('router.builder')->rebuild();
    $this->drupalPlaceBlock('system_powered_by_block');

    // Check the same page, block.module's hook_install() should have
    // invalidated the 'rendered' cache tag to make blocks show up.
    $this->drupalGet('');
    $this->assertCacheTag('config:block_list');
    $this->assertText('Powered by Drupal');
  }

}

<?php

namespace Drupal\Tests\workspaces\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests uninstalling the Workspaces module.
 *
 * @group workspaces
 */
class WorkspacesUninstallTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['workspaces'];

  /**
   * Tests deleting workspace entities and uninstalling Workspaces module.
   */
  public function testUninstallingWorkspace() {
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/admin/modules/uninstall');
    $session = $this->assertSession();
    $session->linkExists('Remove workspaces');
    $this->clickLink('Remove workspaces');
    $session->pageTextContains('Are you sure you want to delete all workspaces?');
    $this->drupalPostForm('/admin/modules/uninstall/entity/workspace', [], 'Delete all workspaces');
    $this->drupalPostForm('admin/modules/uninstall', ['uninstall[workspaces]' => TRUE], 'Uninstall');
    $this->drupalPostForm(NULL, [], 'Uninstall');
    $session->pageTextContains('The selected modules have been uninstalled.');
    $session->pageTextNotContains('Workspaces');
  }

}

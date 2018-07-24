<?php

namespace Drupal\Tests\workspace\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests uninstalling the Workspace module.
 *
 * @group workspace
 */
class WorkspaceUninstallTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

  /**
   * {@inheritdoc}
   */
  public static $modules = ['workspace'];

  /**
   * Tests deleting workspace entities and uninstalling Workspace module.
   */
  public function testUninstallingWorkspace() {
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/admin/modules/uninstall');
    $session = $this->assertSession();
    $session->linkExists('Remove workspaces');
    $this->clickLink('Remove workspaces');
    $session->pageTextContains('Are you sure you want to delete all workspaces?');
    $this->drupalPostForm('/admin/modules/uninstall/entity/workspace', [], 'Delete all workspaces');
    $this->drupalPostForm('admin/modules/uninstall', ['uninstall[workspace]' => TRUE], 'Uninstall');
    $this->drupalPostForm(NULL, [], 'Uninstall');
    $session->pageTextContains('The selected modules have been uninstalled.');
    $session->pageTextNotContains('Workspace');
  }

}

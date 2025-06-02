<?php

declare(strict_types=1);

namespace Drupal\Tests\workspaces\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;

/**
 * Tests uninstalling the Workspaces module.
 *
 * @group workspaces
 */
class WorkspacesUninstallTest extends BrowserTestBase {
  use ContentTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['workspaces', 'node', 'workspaces_ui'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $permissions = [
      'administer workspaces',
      'administer modules',
    ];

    $this->drupalLogin($this->drupalCreateUser($permissions));
  }

  /**
   * Tests deleting workspace entities and uninstalling Workspaces module.
   */
  public function testUninstallingWorkspace(): void {
    $this->createContentType(['type' => 'article']);
    $this->drupalGet('admin/modules/uninstall');
    $this->submitForm(['uninstall[workspaces_ui]' => TRUE], 'Uninstall');
    $this->submitForm([], 'Uninstall');
    $this->submitForm(['uninstall[workspaces]' => TRUE], 'Uninstall');
    $this->submitForm([], 'Uninstall');
    $session = $this->assertSession();
    $session->pageTextContains('The selected modules have been uninstalled.');
    $session->pageTextNotContains('Workspaces');

    $this->assertFalse(\Drupal::database()->schema()->fieldExists('node_revision', 'workspace'));

    // Verify that the revision metadata key has been removed.
    $this->rebuildContainer();
    $entity_type = \Drupal::entityDefinitionUpdateManager()->getEntityType('node');
    $revision_metadata_keys = $entity_type->get('revision_metadata_keys');
    $this->assertArrayNotHasKey('workspace', $revision_metadata_keys);
  }

}

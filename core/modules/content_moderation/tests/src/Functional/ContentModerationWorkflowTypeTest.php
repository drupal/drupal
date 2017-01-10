<?php

namespace Drupal\Tests\content_moderation\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test the workflow type plugin in the content_moderation module.
 *
 * @group content_moderation
 */
class ContentModerationWorkflowTypeTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'content_moderation',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $admin = $this->drupalCreateUser([
      'administer workflows',
    ]);
    $this->drupalLogin($admin);
  }

  /**
   * Test creating a new workflow using the content moderation plugin.
   */
  public function testNewWorkflow() {
    $entity_bundle_info = \Drupal::service('entity_type.bundle.info');

    $this->drupalPostForm('admin/config/workflow/workflows/add', [
      'label' => 'Test Workflow',
      'id' => 'test_workflow',
      'workflow_type' => 'content_moderation',
    ], 'Save');
    $this->assertSession()->pageTextContains('Created the Test Workflow Workflow. In order for the workflow to be enabled there needs to be at least one state.');

    // Ensure after a workflow is created, the bundle information can be
    // refreshed.
    $entity_bundle_info->clearCachedBundles();
    $this->assertNotEmpty($entity_bundle_info->getAllBundleInfo());

    $this->submitForm([
      'label' => 'Test State',
      'id' => 'test_state',
      'type_settings[content_moderation][published]' => TRUE,
      'type_settings[content_moderation][default_revision]' => FALSE,
    ], 'Save');
    $this->assertSession()->pageTextContains('Created Test State state.');
  }

}

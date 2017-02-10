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

    // Make sure the test workflow includes the default states and transitions.
    $this->assertSession()->pageTextContains('Draft');
    $this->assertSession()->pageTextContains('Published');
    $this->assertSession()->pageTextContains('Create New Draft');
    $this->assertSession()->pageTextContains('Publish');

    // Ensure after a workflow is created, the bundle information can be
    // refreshed.
    $entity_bundle_info->clearCachedBundles();
    $this->assertNotEmpty($entity_bundle_info->getAllBundleInfo());

    $this->clickLink('Add a new state');
    $this->submitForm([
      'label' => 'Test State',
      'id' => 'test_state',
      'type_settings[content_moderation][published]' => TRUE,
      'type_settings[content_moderation][default_revision]' => FALSE,
    ], 'Save');
    $this->assertSession()->pageTextContains('Created Test State state.');

    // Ensure that the published settings cannot be changed.
    $this->drupalGet('admin/config/workflow/workflows/manage/test_workflow/state/published');
    $this->assertSession()->fieldDisabled('type_settings[content_moderation][published]');
    $this->assertSession()->fieldDisabled('type_settings[content_moderation][default_revision]');

    // Ensure that the draft settings cannot be changed.
    $this->drupalGet('admin/config/workflow/workflows/manage/test_workflow/state/draft');
    $this->assertSession()->fieldDisabled('type_settings[content_moderation][published]');
    $this->assertSession()->fieldDisabled('type_settings[content_moderation][default_revision]');
  }

}

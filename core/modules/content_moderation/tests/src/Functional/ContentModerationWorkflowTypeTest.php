<?php

declare(strict_types=1);

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
  protected static $modules = [
    'content_moderation',
    'node',
    'entity_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $admin = $this->drupalCreateUser([
      'administer workflows',
    ]);
    $this->drupalLogin($admin);
  }

  /**
   * Tests creating a new workflow using the content moderation plugin.
   */
  public function testNewWorkflow(): void {
    $types[] = $this->createContentType();
    $types[] = $this->createContentType();
    $types[] = $this->createContentType();

    $entity_bundle_info = \Drupal::service('entity_type.bundle.info');

    $this->drupalGet('admin/config/workflow/workflows/add');
    $this->submitForm([
      'label' => 'Test',
      'id' => 'test',
      'workflow_type' => 'content_moderation',
    ], 'Save');

    $session = $this->assertSession();
    // Make sure the test workflow includes the default states and transitions.
    $session->pageTextContains('Draft');
    $session->pageTextContains('Published');
    $session->pageTextContains('Create New Draft');
    $session->pageTextContains('Publish');

    $session->linkByHrefNotExists('/admin/config/workflow/workflows/manage/test/state/draft/delete');
    $session->linkByHrefNotExists('/admin/config/workflow/workflows/manage/test/state/published/delete');

    // Ensure after a workflow is created, the bundle information can be
    // refreshed.
    $entity_bundle_info->clearCachedBundles();
    $this->assertNotEmpty($entity_bundle_info->getAllBundleInfo());

    $this->clickLink('Add a new state');
    $this->submitForm([
      'label' => 'Test State',
      'id' => 'test_state',
      'type_settings[published]' => TRUE,
      'type_settings[default_revision]' => FALSE,
    ], 'Save');
    $session->pageTextContains('Created Test State state.');
    $session->linkByHrefExists('/admin/config/workflow/workflows/manage/test/state/test_state/delete');

    // Check there is a link to delete a default transition.
    $session->linkByHrefExists('/admin/config/workflow/workflows/manage/test/transition/publish/delete');
    // Delete the transition.
    $this->drupalGet('/admin/config/workflow/workflows/manage/test/transition/publish/delete');
    $this->submitForm([], 'Delete');
    // The link to delete the transition should now be gone.
    $session->linkByHrefNotExists('/admin/config/workflow/workflows/manage/test/transition/publish/delete');

    // Ensure that the published settings cannot be changed.
    $this->drupalGet('admin/config/workflow/workflows/manage/test/state/published');
    $session->fieldDisabled('type_settings[published]');
    $session->fieldDisabled('type_settings[default_revision]');

    // Ensure that the draft settings cannot be changed.
    $this->drupalGet('admin/config/workflow/workflows/manage/test/state/draft');
    $session->fieldDisabled('type_settings[published]');
    $session->fieldDisabled('type_settings[default_revision]');

    $this->drupalGet('admin/config/workflow/workflows/manage/test/type/node');
    $session->pageTextContains('Select the content types for the Test workflow');
    foreach ($types as $type) {
      $session->pageTextContains($type->label());
      $session->elementContains('css', sprintf('.form-item-bundles-%s label', $type->id()), sprintf('Update %s', $type->label()));
    }

    // Ensure warning message are displayed for unsupported features.
    $this->drupalGet('admin/config/workflow/workflows/manage/test/type/entity_test_rev');
    $this->assertSession()->pageTextContains('Test entity - revisions entities do not support publishing statuses. For example, even after transitioning from a published workflow state to an unpublished workflow state they will still be visible to site visitors.');
  }

}

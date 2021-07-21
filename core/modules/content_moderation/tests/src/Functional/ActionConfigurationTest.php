<?php

namespace Drupal\Tests\content_moderation\Functional;

use Drupal\system\Entity\Action;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;

/**
 * Tests actions configuration by adding, editing, and deleting the action.
 *
 * @group content_moderation
 */
class ActionConfigurationTest extends BrowserTestBase {

  use ContentTypeCreationTrait;
  use ContentModerationTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'action',
    'node',
    'content_moderation',
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
    $this->createContentType(['type' => 'page']);
    $workflow = $this->createEditorialWorkflow();
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'page');
    $workflow->save();
  }

  /**
   * Tests configuration of action through administration interface.
   */
  public function testActionConfiguration() {
    // Create a user with permission to view the actions administration pages.
    $user = $this->drupalCreateUser(['administer actions']);
    $this->drupalLogin($user);
    $assert_session = $this->assertSession();

    $this->drupalGet('admin/config/system/actions');
    $edit = [
      'action' => 'moderation_state_change:node',
    ];
    $this->submitForm($edit, t('Create'));
    $assert_session->statusCodeEquals(200);

    // Make a POST request to the individual action configuration page.
    $edit = [];
    $action_label = $this->randomMachineName();
    $edit['label'] = $action_label;
    $edit['id'] = strtolower($action_label);
    $edit['workflow'] = 'editorial';
    $edit['state'] = 'draft';
    $edit['revision_log_message'] = 'Move to draft';
    $this->submitForm($edit, t('Save'));
    $assert_session->statusCodeEquals(200);

    $action_id = $edit['id'];

    // Make sure that the new complex action was saved properly.
    $assert_session->pageTextContains(t('The action has been successfully saved.'));
    $assert_session->pageTextContains($action_label);

    // Make another POST request to the action edit page.
    $this->clickLink(t('Configure'));
    $assert_session->statusCodeEquals(200);
    $assert_session->fieldValueEquals('workflow', 'editorial');
    $assert_session->fieldValueEquals('state', 'draft');
    $assert_session->fieldValueEquals('revision_log_message', 'Move to draft');

    $edit = [];
    $new_action_label = $this->randomMachineName();
    $edit['label'] = $new_action_label;
    $edit['workflow'] = 'editorial';
    $edit['state'] = 'published';
    $edit['revision_log_message'] = 'Publish content';
    $this->submitForm($edit, t('Save'));
    $assert_session->statusCodeEquals(200);

    // Make sure that the action updated properly.
    $assert_session->pageTextContains(t('The action has been successfully saved.'));
    $assert_session->pageTextNotContains($action_label);
    $assert_session->pageTextContains($new_action_label);

    $this->clickLink(t('Configure'));
    $assert_session->statusCodeEquals(200);
    $assert_session->fieldValueEquals('workflow', 'editorial');
    $assert_session->fieldValueEquals('state', 'published');
    $assert_session->fieldValueEquals('revision_log_message', 'Publish content');

    // Make sure that deletions work properly.
    $this->drupalGet('admin/config/system/actions');
    $this->clickLink(t('Delete'));
    $assert_session->statusCodeEquals(200);
    $edit = [];
    $this->submitForm($edit, t('Delete'));
    $assert_session->statusCodeEquals(200);

    // Make sure that the action was actually deleted.
    $assert_session->responseContains(t('The action %action has been deleted.', ['%action' => $new_action_label]));
    $this->drupalGet('admin/config/system/actions');
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextNotContains($new_action_label);

    $action = Action::load($action_id);
    $this->assertEmpty($action, 'Make sure the action is gone after being deleted.');
  }

}

<?php

namespace Drupal\Tests\content_moderation\Functional;

use Drupal\simpletest\ContentTypeCreationTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\workflows\Entity\Workflow;

/**
 * Test revision revert.
 *
 * @group content_moderation
 */
class ModerationRevisionRevertTest extends BrowserTestBase {

  use ContentTypeCreationTrait;

  /**
   * Modules to enable.
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
  public function setUp() {
    parent::setUp();

    $moderated_bundle = $this->createContentType(['type' => 'moderated_bundle']);
    $moderated_bundle->save();

    $workflow = Workflow::load('editorial');
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'moderated_bundle');
    $workflow->save();

    $admin = $this->drupalCreateUser([
      'access content overview',
      'administer nodes',
      'bypass node access',
      'view all revisions',
      'use editorial transition create_new_draft',
      'use editorial transition publish',
    ]);
    $this->drupalLogin($admin);
  }

  /**
   * Test that reverting a revision works.
   */
  public function testEditingAfterRevertRevision() {
    // Create a draft.
    $this->drupalPostForm('node/add/moderated_bundle', [
      'title[0][value]' => 'First draft node',
      'moderation_state[0][state]' => 'draft',
    ], t('Save'));

    // Now make it published.
    $this->drupalPostForm('node/1/edit', [
      'title[0][value]' => 'Published node',
      'moderation_state[0][state]' => 'published',
    ], t('Save'));

    // Check the editing form that show the published title.
    $this->drupalGet('node/1/edit');
    $this->assertSession()
      ->pageTextContains('Published node');

    // Revert the first revision.
    $revision_url = 'node/1/revisions/1/revert';
    $this->drupalGet($revision_url);
    $this->assertSession()->elementExists('css', '.form-submit');
    $this->click('.form-submit');

    // Check that it reverted.
    $this->drupalGet('node/1/edit');
    $this->assertSession()
      ->pageTextContains('First draft node');
    // Try to save the node.
    $this->drupalPostForm('node/1/edit', [
      'moderation_state[0][state]' => 'draft',
    ], t('Save'));

    // Check if the submission passed the EntityChangedConstraintValidator.
    $this->assertSession()
      ->pageTextNotContains('The content has either been modified by another user, or you have already submitted modifications. As a result, your changes cannot be saved.');

    // Check the node has been saved.
    $this->assertSession()
      ->pageTextContains('moderated_bundle First draft node has been updated');
  }

}

<?php

declare(strict_types=1);

namespace Drupal\Tests\content_moderation\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;

/**
 * Test revision revert.
 *
 * @group content_moderation
 */
class ModerationRevisionRevertTest extends BrowserTestBase {

  use ContentTypeCreationTrait;
  use ContentModerationTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_moderation',
    'node',
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

    $moderated_bundle = $this->createContentType(['type' => 'moderated_bundle']);
    $moderated_bundle->save();

    $workflow = $this->createEditorialWorkflow();
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'moderated_bundle');
    $workflow->save();

    /** @var \Drupal\Core\Routing\RouteBuilderInterface $router_builder */
    $router_builder = $this->container->get('router.builder');
    $router_builder->rebuildIfNeeded();

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
   * Tests that reverting a revision works.
   */
  public function testEditingAfterRevertRevision(): void {
    // Create a draft.
    $this->drupalGet('node/add/moderated_bundle');
    $this->submitForm([
      'title[0][value]' => 'First draft node',
      'moderation_state[0][state]' => 'draft',
    ], 'Save');

    // Now make it published.
    $this->drupalGet('node/1/edit');
    $this->submitForm([
      'title[0][value]' => 'Published node',
      'moderation_state[0][state]' => 'published',
    ], 'Save');

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
    $this->drupalGet('node/1/edit');
    $this->submitForm(['moderation_state[0][state]' => 'draft'], 'Save');

    // Check if the submission passed the EntityChangedConstraintValidator.
    $this->assertSession()
      ->pageTextNotContains('The content has either been modified by another user, or you have already submitted modifications. As a result, your changes cannot be saved.');

    // Check the node has been saved.
    $this->assertSession()
      ->pageTextContains('moderated_bundle First draft node has been updated');
  }

}

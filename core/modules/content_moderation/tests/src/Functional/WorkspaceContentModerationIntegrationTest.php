<?php

namespace Drupal\Tests\content_moderation\Functional;

use Drupal\Tests\workspaces\Functional\WorkspaceTestUtilities;
use Drupal\workspaces\Entity\Workspace;

/**
 * Tests Workspaces together with Content Moderation.
 *
 * @group content_moderation
 * @group workspaces
 */
class WorkspaceContentModerationIntegrationTest extends ModerationStateTestBase {

  use WorkspaceTestUtilities;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'workspaces'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalLogin($this->rootUser);

    // Enable moderation on Article node type.
    $this->createContentTypeFromUi('Article', 'article', TRUE);

    $this->setupWorkspaceSwitcherBlock();
  }

  /**
   * Tests moderating nodes in a workspace.
   */
  public function testModerationInWorkspace() {
    $stage = Workspace::load('stage');
    $this->switchToWorkspace($stage);

    // Create two nodes, a published and a draft one.
    $this->drupalPostForm('node/add/article', [
      'title[0][value]' => 'First article - published',
      'moderation_state[0][state]' => 'published',
    ], 'Save');
    $this->drupalPostForm('node/add/article', [
      'title[0][value]' => 'Second article - draft',
      'moderation_state[0][state]' => 'draft',
    ], 'Save');

    $first_article = $this->drupalGetNodeByTitle('First article - published', TRUE);
    $this->assertEquals('published', $first_article->moderation_state->value);

    $second_article = $this->drupalGetNodeByTitle('Second article - draft', TRUE);
    $this->assertEquals('draft', $second_article->moderation_state->value);

    // Check that neither of them are visible in Live.
    $this->switchToLive();
    $this->drupalGet('<front>');
    $this->assertNoText('First article');
    $this->assertNoText('Second article');

    // Switch back to Stage.
    $this->switchToWorkspace($stage);

    // Take the first node through various moderation states.
    $this->drupalGet('/node/1/edit');
    $this->assertEquals('Current state Published', $this->cssSelect('#edit-moderation-state-0-current')[0]->getText());

    $this->drupalPostForm(NULL, [
      'title[0][value]' => 'First article - draft',
      'moderation_state[0][state]' => 'draft',
    ], 'Save');

    $this->drupalGet('/node/1');
    $this->assertText('First article - draft');

    $this->drupalGet('/node/1/edit');
    $this->assertEquals('Current state Draft', $this->cssSelect('#edit-moderation-state-0-current')[0]->getText());

    $this->drupalPostForm(NULL, [
      'title[0][value]' => 'First article - published',
      'moderation_state[0][state]' => 'published',
    ], 'Save');

    $this->drupalPostForm('/node/1/edit', [
      'title[0][value]' => 'First article - archived',
      'moderation_state[0][state]' => 'archived',
    ], 'Save');

    $this->drupalGet('/node/1');
    $this->assertText('First article - archived');

    // Get the second node to a default revision state and publish the
    // workspace.
    $this->drupalPostForm('/node/2/edit', [
      'title[0][value]' => 'Second article - published',
      'moderation_state[0][state]' => 'published',
    ], 'Save');

    $stage->publish();

    // The admin user can see unpublished nodes.
    $this->drupalGet('/node/1');
    $this->assertText('First article - archived');

    $this->drupalGet('/node/2');
    $this->assertText('Second article - published');
  }

}

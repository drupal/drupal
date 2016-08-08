<?php

namespace Drupal\content_moderation\Tests;

/**
 * Tests the moderation form, specifically on nodes.
 *
 * @group content_moderation
 */
class ModerationFormTest extends ModerationStateTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalLogin($this->adminUser);
    $this->createContentTypeFromUi('Moderated content', 'moderated_content', TRUE, [
      'draft',
      'published',
    ], 'draft');
    $this->grantUserPermissionToCreateContentOfType($this->adminUser, 'moderated_content');
  }

  /**
   * Tests the moderation form that shows on the latest version page.
   *
   * The latest version page only shows if there is a forward revision. There
   * is only a forward revision if a draft revision is created on a node where
   * the default revision is not a published moderation state.
   *
   * @see \Drupal\content_moderation\EntityOperations
   * @see \Drupal\content_moderation\Tests\ModerationStateBlockTest::testCustomBlockModeration
   */
  public function testModerationForm() {
    // Create new moderated content in draft.
    $this->drupalPostForm('node/add/moderated_content', [
      'title[0][value]' => 'Some moderated content',
      'body[0][value]' => 'First version of the content.',
    ], t('Save and Create New Draft'));

    $node = $this->drupalGetNodeByTitle('Some moderated content');
    $canonical_path = sprintf('node/%d', $node->id());
    $edit_path = sprintf('node/%d/edit', $node->id());
    $latest_version_path = sprintf('node/%d/latest', $node->id());

    $this->assertTrue($this->adminUser->hasPermission('edit any moderated_content content'));

    // The latest version page should not show, because there is no forward
    // revision.
    $this->drupalGet($latest_version_path);
    $this->assertResponse(403);

    // Update the draft.
    $this->drupalPostForm($edit_path, [
      'body[0][value]' => 'Second version of the content.',
    ], t('Save and Create New Draft'));

    // The latest version page should not show, because there is still no
    // forward revision.
    $this->drupalGet($latest_version_path);
    $this->assertResponse(403);

    // Publish the draft.
    $this->drupalPostForm($edit_path, [
      'body[0][value]' => 'Third version of the content.',
    ], t('Save and Publish'));

    // The published view should not have a moderation form, because it is the
    // default revision.
    $this->drupalGet($canonical_path);
    $this->assertResponse(200);
    $this->assertNoText('Status', 'The node view page has no moderation form.');

    // The latest version page should not show, because there is still no
    // forward revision.
    $this->drupalGet($latest_version_path);
    $this->assertResponse(403);

    // Make a forward revision.
    $this->drupalPostForm($edit_path, [
      'body[0][value]' => 'Fourth version of the content.',
    ], t('Save and Create New Draft'));

    // The published view should not have a moderation form, because it is the
    // default revision.
    $this->drupalGet($canonical_path);
    $this->assertResponse(200);
    $this->assertNoText('Status', 'The node view page has no moderation form.');

    // The latest version page should show the moderation form and have "Draft"
    // status, because the forward revision is in "Draft".
    $this->drupalGet($latest_version_path);
    $this->assertResponse(200);
    $this->assertText('Status', 'Form text found on the latest-version page.');
    $this->assertText('Draft', 'Correct status found on the latest-version page.');

    // Submit the moderation form to change status to published.
    $this->drupalPostForm($latest_version_path, [
      'new_state' => 'published',
    ], t('Apply'));

    // The latest version page should not show, because there is no
    // forward revision.
    $this->drupalGet($latest_version_path);
    $this->assertResponse(403);
  }

}

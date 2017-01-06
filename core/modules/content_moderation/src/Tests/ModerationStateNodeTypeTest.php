<?php

namespace Drupal\content_moderation\Tests;


/**
 * Tests moderation state node type integration.
 *
 * @group content_moderation
 */
class ModerationStateNodeTypeTest extends ModerationStateTestBase {

  /**
   * A node type without moderation state disabled.
   */
  public function testNotModerated() {
    $this->drupalLogin($this->adminUser);
    $this->createContentTypeFromUi('Not moderated', 'not_moderated');
    $this->assertText('The content type Not moderated has been added.');
    $this->grantUserPermissionToCreateContentOfType($this->adminUser, 'not_moderated');
    $this->drupalGet('node/add/not_moderated');
    $this->assertRaw('Save as unpublished');
    $this->drupalPostForm(NULL, [
      'title[0][value]' => 'Test',
    ], t('Save and publish'));
    $this->assertText('Not moderated Test has been created.');
  }

  /**
   * Tests enabling moderation on an existing node-type, with content.
   */
  public function testEnablingOnExistingContent() {
    // Create a node type that is not moderated.
    $this->drupalLogin($this->adminUser);
    $this->createContentTypeFromUi('Not moderated', 'not_moderated');
    $this->grantUserPermissionToCreateContentOfType($this->adminUser, 'not_moderated');

    // Create content.
    $this->drupalGet('node/add/not_moderated');
    $this->drupalPostForm(NULL, [
      'title[0][value]' => 'Test',
    ], t('Save and publish'));
    $this->assertText('Not moderated Test has been created.');

    // Now enable moderation state, ensuring all the expected links and tabs are
    // present.
    $this->drupalGet('admin/structure/types');
    $this->assertLinkByHref('admin/structure/types/manage/not_moderated/moderation');
    $this->drupalGet('admin/structure/types/manage/not_moderated');
    $this->assertLinkByHref('admin/structure/types/manage/not_moderated/moderation');
    $this->drupalGet('admin/structure/types/manage/not_moderated/moderation');
    $this->assertOptionSelected('edit-workflow', '');
    $this->assertNoLink('Delete');
    $edit['workflow'] = 'editorial';
    $this->drupalPostForm(NULL, $edit, t('Save'));

    // And make sure it works.
    $nodes = \Drupal::entityTypeManager()->getStorage('node')
      ->loadByProperties(['title' => 'Test']);
    if (empty($nodes)) {
      $this->fail('Could not load node with title Test');
      return;
    }
    $node = reset($nodes);
    $this->drupalGet('node/' . $node->id());
    $this->assertResponse(200);
    $this->assertLinkByHref('node/' . $node->id() . '/edit');
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertResponse(200);
    $this->assertRaw('Save and Create New Draft');
    $this->assertNoRaw('Save and publish');
  }

}

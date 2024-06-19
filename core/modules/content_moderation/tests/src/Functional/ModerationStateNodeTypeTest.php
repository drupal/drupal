<?php

declare(strict_types=1);

namespace Drupal\Tests\content_moderation\Functional;

/**
 * Tests moderation state node type integration.
 *
 * @group content_moderation
 * @group #slow
 */
class ModerationStateNodeTypeTest extends ModerationStateTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A node type without moderation state disabled.
   *
   * @covers \Drupal\content_moderation\EntityTypeInfo::formAlter
   * @covers \Drupal\content_moderation\Entity\Handler\NodeModerationHandler::enforceRevisionsBundleFormAlter
   */
  public function testNotModerated(): void {
    $this->drupalLogin($this->adminUser);
    $this->createContentTypeFromUi('Not moderated', 'not_moderated');
    $this->assertSession()->pageTextContains('The content type Not moderated has been added.');
    $this->grantUserPermissionToCreateContentOfType($this->adminUser, 'not_moderated');
    $this->drupalGet('node/add/not_moderated');
    $this->assertSession()->pageTextContains('Save');
    $this->submitForm([
      'title[0][value]' => 'Test',
    ], 'Save');
    $this->assertSession()->pageTextContains('Not moderated Test has been created.');
  }

  /**
   * Tests enabling moderation on an existing node-type, with content.
   *
   * @covers \Drupal\content_moderation\EntityTypeInfo::formAlter
   * @covers \Drupal\content_moderation\Entity\Handler\NodeModerationHandler::enforceRevisionsBundleFormAlter
   */
  public function testEnablingOnExistingContent(): void {
    $editor_permissions = [
      'administer workflows',
      'access administration pages',
      'administer content types',
      'administer nodes',
      'view latest version',
      'view any unpublished content',
      'access content overview',
      'use editorial transition create_new_draft',
    ];
    $publish_permissions = array_merge($editor_permissions, ['use editorial transition publish']);
    $editor = $this->drupalCreateUser($editor_permissions);
    $editor_with_publish = $this->drupalCreateUser($publish_permissions);

    // Create a node type that is not moderated.
    $this->drupalLogin($editor);
    $this->createContentTypeFromUi('Not moderated', 'not_moderated');
    $this->grantUserPermissionToCreateContentOfType($editor, 'not_moderated');
    $this->grantUserPermissionToCreateContentOfType($editor_with_publish, 'not_moderated');

    // Create content.
    $this->drupalGet('node/add/not_moderated');
    $this->submitForm([
      'title[0][value]' => 'Test',
    ], 'Save');
    $this->assertSession()->pageTextContains('Not moderated Test has been created.');

    // Check that the 'Create new revision' is not disabled.
    $this->drupalGet('/admin/structure/types/manage/not_moderated');
    $this->assertNull($this->assertSession()->fieldExists('options[revision]')->getAttribute('disabled'));

    // Now enable moderation state.
    $this->enableModerationThroughUi('not_moderated');

    // Check that the 'Create new revision' checkbox is checked and disabled.
    $this->drupalGet('/admin/structure/types/manage/not_moderated');
    $this->assertSession()->checkboxChecked('options[revision]');
    $this->assertSession()->fieldDisabled('options[revision]');

    // And make sure it works.
    $nodes = \Drupal::entityTypeManager()->getStorage('node')
      ->loadByProperties(['title' => 'Test']);
    if (empty($nodes)) {
      $this->fail('Could not load node with title Test');
      return;
    }
    $node = reset($nodes);
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkByHrefExists('node/' . $node->id() . '/edit');
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->optionExists('moderation_state[0][state]', 'draft');
    $this->assertSession()->optionNotExists('moderation_state[0][state]', 'published');

    $this->drupalLogin($editor_with_publish);
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->optionExists('moderation_state[0][state]', 'draft');
    $this->assertSession()->optionExists('moderation_state[0][state]', 'published');
  }

  /**
   * @covers \Drupal\content_moderation\Entity\Handler\NodeModerationHandler::enforceRevisionsBundleFormAlter
   */
  public function testEnforceRevisionsEntityFormAlter(): void {
    $this->drupalLogin($this->adminUser);
    $this->createContentTypeFromUi('Moderated', 'moderated');

    // Ensure checkboxes in the 'workflow' section can be altered, even when
    // 'revision' is enforced and disabled.
    $this->drupalGet('admin/structure/types/manage/moderated');
    $this->submitForm(['options[promote]' => TRUE], 'Save');
    $this->drupalGet('admin/structure/types/manage/moderated');
    $this->assertSession()->checkboxChecked('options[promote]');
  }

}

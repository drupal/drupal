<?php

namespace Drupal\Tests\content_moderation\Functional;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Url;

/**
 * Tests the moderation form, specifically on nodes.
 *
 * @group content_moderation
 */
class ModerationFormTest extends ModerationStateTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'node',
    'content_moderation',
    'locale',
    'content_translation',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalLogin($this->adminUser);
    $this->createContentTypeFromUi('Moderated content', 'moderated_content', TRUE);
    $this->grantUserPermissionToCreateContentOfType($this->adminUser, 'moderated_content');
  }

  /**
   * Tests the moderation form that shows on the latest version page.
   *
   * The latest version page only shows if there is a pending revision.
   *
   * @see \Drupal\content_moderation\EntityOperations
   * @see \Drupal\Tests\content_moderation\Functional\ModerationStateBlockTest::testCustomBlockModeration
   */
  public function testModerationForm() {
    // Test the states that appear by default when creating a new item of
    // content.
    $this->drupalGet('node/add/moderated_content');
    $this->assertSession()->optionExists('moderation_state[0][state]', 'draft');
    $this->assertSession()->optionExists('moderation_state[0][state]', 'published');
    $this->assertSession()->optionNotExists('moderation_state[0][state]', 'archived');
    // Previewing a new item of content should not change the available states.
    $this->submitForm([
      'moderation_state[0][state]' => 'published',
      'title[0][value]' => 'Some moderated content',
      'body[0][value]' => 'First version of the content.',
    ], 'Preview');
    $this->clickLink('Back to content editing');
    $this->assertSession()->optionExists('moderation_state[0][state]', 'draft');
    $this->assertSession()->optionExists('moderation_state[0][state]', 'published');
    $this->assertSession()->optionNotExists('moderation_state[0][state]', 'archived');

    // Create new moderated content in draft.
    $this->submitForm(['moderation_state[0][state]' => 'draft'], t('Save'));

    $node = $this->drupalGetNodeByTitle('Some moderated content');
    $canonical_path = sprintf('node/%d', $node->id());
    $edit_path = sprintf('node/%d/edit', $node->id());
    $latest_version_path = sprintf('node/%d/latest', $node->id());

    $this->assertTrue($this->adminUser->hasPermission('edit any moderated_content content'));

    // The canonical view should have a moderation form, because it is not the
    // live revision.
    $this->drupalGet($canonical_path);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertField('edit-new-state', 'The node view page has a moderation form.');

    // The latest version page should not show, because there is no pending
    // revision.
    $this->drupalGet($latest_version_path);
    $this->assertSession()->statusCodeEquals(403);

    // Update the draft.
    $this->drupalPostForm($edit_path, [
      'body[0][value]' => 'Second version of the content.',
      'moderation_state[0][state]' => 'draft',
    ], t('Save'));

    // The canonical view should have a moderation form, because it is not the
    // live revision.
    $this->drupalGet($canonical_path);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertField('edit-new-state', 'The node view page has a moderation form.');

    // Preview the draft.
    $this->drupalPostForm($edit_path, [
      'body[0][value]' => 'Second version of the content.',
      'moderation_state[0][state]' => 'draft',
    ], t('Preview'));

    // The preview view should not have a moderation form.
    $preview_url = Url::fromRoute('entity.node.preview', [
      'node_preview' => $node->uuid(),
      'view_mode_id' => 'full',
    ]);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertUrl($preview_url);
    $this->assertNoField('edit-new-state', 'The node preview page has no moderation form.');

    // The latest version page should not show, because there is still no
    // pending revision.
    $this->drupalGet($latest_version_path);
    $this->assertSession()->statusCodeEquals(403);

    // Publish the draft.
    $this->drupalPostForm($edit_path, [
      'body[0][value]' => 'Third version of the content.',
      'moderation_state[0][state]' => 'published',
    ], t('Save'));

    // Check widget default value.
    $this->drupalGet($edit_path);
    $this->assertFieldByName('moderation_state[0][state]', 'published', 'The moderation default value is set correctly.');

    // Preview the content while selecting the "draft" state and when the user
    // returns to the edit form, ensure all of the available transitions are
    // still those available from the "published" source state.
    $this->submitForm(['moderation_state[0][state]' => 'draft'], 'Preview');
    $this->clickLink('Back to content editing');
    $this->assertSession()->optionExists('moderation_state[0][state]', 'draft');
    $this->assertSession()->optionExists('moderation_state[0][state]', 'published');
    $this->assertSession()->optionExists('moderation_state[0][state]', 'archived');

    // The published view should not have a moderation form, because it is the
    // live revision.
    $this->drupalGet($canonical_path);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertNoField('edit-new-state', 'The node view page has no moderation form.');

    // The latest version page should not show, because there is still no
    // pending revision.
    $this->drupalGet($latest_version_path);
    $this->assertSession()->statusCodeEquals(403);

    // Make a pending revision.
    $this->drupalPostForm($edit_path, [
      'body[0][value]' => 'Fourth version of the content.',
      'moderation_state[0][state]' => 'draft',
    ], t('Save'));

    // The published view should not have a moderation form, because it is the
    // live revision.
    $this->drupalGet($canonical_path);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertNoField('edit-new-state', 'The node view page has no moderation form.');

    // The latest version page should show the moderation form and have "Draft"
    // status, because the pending revision is in "Draft".
    $this->drupalGet($latest_version_path);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertField('edit-new-state', 'The latest-version page has a moderation form.');
    $this->assertText('Draft', 'Correct status found on the latest-version page.');

    // Submit the moderation form to change status to published.
    $this->drupalPostForm($latest_version_path, [
      'new_state' => 'published',
    ], t('Apply'));

    // The latest version page should not show, because there is no
    // pending revision.
    $this->drupalGet($latest_version_path);
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Test moderation non-bundle entity type.
   */
  public function testNonBundleModerationForm() {
    $this->drupalLogin($this->rootUser);
    $this->workflow->getTypePlugin()->addEntityTypeAndBundle('entity_test_mulrevpub', 'entity_test_mulrevpub');
    $this->workflow->save();

    // Create new moderated content in draft.
    $this->drupalPostForm('entity_test_mulrevpub/add', ['moderation_state[0][state]' => 'draft'], t('Save'));

    // The latest version page should not show, because there is no pending
    // revision.
    $this->drupalGet('/entity_test_mulrevpub/manage/1/latest');
    $this->assertSession()->statusCodeEquals(403);

    // Update the draft.
    $this->drupalPostForm('entity_test_mulrevpub/manage/1/edit', ['moderation_state[0][state]' => 'draft'], t('Save'));

    // The latest version page should not show, because there is still no
    // pending revision.
    $this->drupalGet('/entity_test_mulrevpub/manage/1/latest');
    $this->assertSession()->statusCodeEquals(403);

    // Publish the draft.
    $this->drupalPostForm('entity_test_mulrevpub/manage/1/edit', ['moderation_state[0][state]' => 'published'], t('Save'));

    // The published view should not have a moderation form, because it is the
    // default revision.
    $this->drupalGet('entity_test_mulrevpub/manage/1');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertNoText('Status', 'The node view page has no moderation form.');

    // The latest version page should not show, because there is still no
    // pending revision.
    $this->drupalGet('entity_test_mulrevpub/manage/1/latest');
    $this->assertSession()->statusCodeEquals(403);

    // Make a pending revision.
    $this->drupalPostForm('entity_test_mulrevpub/manage/1/edit', ['moderation_state[0][state]' => 'draft'], t('Save'));

    // The published view should not have a moderation form, because it is the
    // default revision.
    $this->drupalGet('entity_test_mulrevpub/manage/1');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertNoText('Status', 'The node view page has no moderation form.');

    // The latest version page should show the moderation form and have "Draft"
    // status, because the pending revision is in "Draft".
    $this->drupalGet('entity_test_mulrevpub/manage/1/latest');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertText('Moderation state', 'Form text found on the latest-version page.');
    $this->assertText('Draft', 'Correct status found on the latest-version page.');

    // Submit the moderation form to change status to published.
    $this->drupalPostForm('entity_test_mulrevpub/manage/1/latest', [
      'new_state' => 'published',
    ], t('Apply'));

    // The latest version page should not show, because there is no
    // pending revision.
    $this->drupalGet('entity_test_mulrevpub/manage/1/latest');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests the revision author is updated when the moderation form is used.
   */
  public function testModerationFormSetsRevisionAuthor() {
    // Create new moderated content in published.
    $node = $this->createNode(['type' => 'moderated_content', 'moderation_state' => 'published']);
    // Make a pending revision.
    $node->title = $this->randomMachineName();
    $node->moderation_state->value = 'draft';
    $node->setRevisionCreationTime(12345);
    $node->save();

    $another_user = $this->drupalCreateUser($this->permissions);
    $this->grantUserPermissionToCreateContentOfType($another_user, 'moderated_content');
    $this->drupalLogin($another_user);
    $this->drupalPostForm(sprintf('node/%d/latest', $node->id()), [
      'new_state' => 'published',
    ], t('Apply'));

    $this->drupalGet(sprintf('node/%d/revisions', $node->id()));
    $this->assertText('by ' . $another_user->getAccountName());

    // Verify the revision creation time has been updated.
    $node = $node->load($node->id());
    $this->assertGreaterThan(12345, $node->getRevisionCreationTime());
  }

  /**
   * Tests translated and moderated nodes.
   */
  public function testContentTranslationNodeForm() {
    $this->drupalLogin($this->rootUser);

    // Add French language.
    $edit = [
      'predefined_langcode' => 'fr',
    ];
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add language'));

    // Enable content translation on articles.
    $this->drupalGet('admin/config/regional/content-language');
    $edit = [
      'entity_types[node]' => TRUE,
      'settings[node][moderated_content][translatable]' => TRUE,
      'settings[node][moderated_content][settings][language][language_alterable]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));

    // Adding languages requires a container rebuild in the test running
    // environment so that multilingual services are used.
    $this->rebuildContainer();

    // Create new moderated content in draft (revision 1).
    $this->drupalPostForm('node/add/moderated_content', [
      'title[0][value]' => 'Some moderated content',
      'body[0][value]' => 'First version of the content.',
      'moderation_state[0][state]' => 'draft',
    ], t('Save'));
    $this->assertNotEmpty($this->xpath('//ul[@class="entity-moderation-form"]'));

    $node = $this->drupalGetNodeByTitle('Some moderated content');
    $this->assertNotEmpty($node->language(), 'en');
    $edit_path = sprintf('node/%d/edit', $node->id());
    $translate_path = sprintf('node/%d/translations/add/en/fr', $node->id());
    $latest_version_path = sprintf('node/%d/latest', $node->id());
    $french = \Drupal::languageManager()->getLanguage('fr');

    $this->drupalGet($latest_version_path);
    $this->assertSession()->statusCodeEquals('403');
    $this->assertEmpty($this->xpath('//ul[@class="entity-moderation-form"]'));

    // Add french translation (revision 2).
    $this->drupalGet($translate_path);
    $this->assertSession()->optionExists('moderation_state[0][state]', 'draft');
    $this->assertSession()->optionExists('moderation_state[0][state]', 'published');
    $this->assertSession()->optionNotExists('moderation_state[0][state]', 'archived');
    $this->drupalPostForm(NULL, [
      'body[0][value]' => 'Second version of the content.',
      'moderation_state[0][state]' => 'published',
    ], t('Save (this translation)'));

    $this->drupalGet($latest_version_path, ['language' => $french]);
    $this->assertSession()->statusCodeEquals('403');
    $this->assertEmpty($this->xpath('//ul[@class="entity-moderation-form"]'));

    // Add french pending revision (revision 3).
    $this->drupalGet($edit_path, ['language' => $french]);
    $this->assertSession()->optionExists('moderation_state[0][state]', 'draft');
    $this->assertSession()->optionExists('moderation_state[0][state]', 'published');
    $this->assertSession()->optionExists('moderation_state[0][state]', 'archived');

    // Preview the content while selecting the "draft" state and when the user
    // returns to the edit form, ensure all of the available transitions are
    // still those available from the "published" source state.
    $this->submitForm(['moderation_state[0][state]' => 'draft'], 'Preview');
    $this->clickLink('Back to content editing');
    $this->assertSession()->optionExists('moderation_state[0][state]', 'draft');
    $this->assertSession()->optionExists('moderation_state[0][state]', 'published');
    $this->assertSession()->optionExists('moderation_state[0][state]', 'archived');

    $this->drupalPostForm(NULL, [
      'body[0][value]' => 'Third version of the content.',
      'moderation_state[0][state]' => 'draft',
    ], t('Save (this translation)'));

    $this->drupalGet($latest_version_path, ['language' => $french]);
    $this->assertNotEmpty($this->xpath('//ul[@class="entity-moderation-form"]'));

    $this->drupalGet($edit_path);
    $this->clickLink('Delete');
    $this->assertSession()->buttonExists('Delete');

    $this->drupalGet($latest_version_path);
    $this->assertEmpty($this->xpath('//ul[@class="entity-moderation-form"]'));

    // Publish the french pending revision (revision 4).
    $this->drupalGet($edit_path, ['language' => $french]);
    $this->assertSession()->optionExists('moderation_state[0][state]', 'draft');
    $this->assertSession()->optionExists('moderation_state[0][state]', 'published');
    $this->assertSession()->optionNotExists('moderation_state[0][state]', 'archived');
    $this->drupalPostForm(NULL, [
      'body[0][value]' => 'Fifth version of the content.',
      'moderation_state[0][state]' => 'published',
    ], t('Save (this translation)'));

    $this->drupalGet($latest_version_path, ['language' => $french]);
    $this->assertEmpty($this->xpath('//ul[@class="entity-moderation-form"]'));

    // Publish the English pending revision (revision 5).
    $this->drupalGet($edit_path);
    $this->assertSession()->optionExists('moderation_state[0][state]', 'draft');
    $this->assertSession()->optionExists('moderation_state[0][state]', 'published');
    $this->assertSession()->optionNotExists('moderation_state[0][state]', 'archived');
    $this->drupalPostForm(NULL, [
      'body[0][value]' => 'Sixth version of the content.',
      'moderation_state[0][state]' => 'published',
    ], t('Save (this translation)'));

    $this->drupalGet($latest_version_path);
    $this->assertEmpty($this->xpath('//ul[@class="entity-moderation-form"]'));

    // Make sure we are allowed to create a pending French revision.
    $this->drupalGet($edit_path, ['language' => $french]);
    $this->assertSession()->optionExists('moderation_state[0][state]', 'draft');
    $this->assertSession()->optionExists('moderation_state[0][state]', 'published');
    $this->assertSession()->optionExists('moderation_state[0][state]', 'archived');

    // Add an English pending revision (revision 6).
    $this->drupalGet($edit_path);
    $this->assertSession()->optionExists('moderation_state[0][state]', 'draft');
    $this->assertSession()->optionExists('moderation_state[0][state]', 'published');
    $this->assertSession()->optionExists('moderation_state[0][state]', 'archived');
    $this->drupalPostForm(NULL, [
      'body[0][value]' => 'Seventh version of the content.',
      'moderation_state[0][state]' => 'draft',
    ], t('Save (this translation)'));

    $this->drupalGet($latest_version_path);
    $this->assertNotEmpty($this->xpath('//ul[@class="entity-moderation-form"]'));
    $this->drupalGet($latest_version_path, ['language' => $french]);
    $this->assertEmpty($this->xpath('//ul[@class="entity-moderation-form"]'));

    // Publish the English pending revision (revision 7)
    $this->drupalGet($edit_path);
    $this->assertSession()->optionExists('moderation_state[0][state]', 'draft');
    $this->assertSession()->optionExists('moderation_state[0][state]', 'published');
    $this->assertSession()->optionNotExists('moderation_state[0][state]', 'archived');
    $this->drupalPostForm(NULL, [
      'body[0][value]' => 'Eighth version of the content.',
      'moderation_state[0][state]' => 'published',
    ], t('Save (this translation)'));

    $this->drupalGet($latest_version_path);
    $this->assertEmpty($this->xpath('//ul[@class="entity-moderation-form"]'));

    // Make sure we are allowed to create a pending French revision.
    $this->drupalGet($edit_path, ['language' => $french]);
    $this->assertSession()->optionExists('moderation_state[0][state]', 'draft');
    $this->assertSession()->optionExists('moderation_state[0][state]', 'published');
    $this->assertSession()->optionExists('moderation_state[0][state]', 'archived');

    // Make sure we are allowed to create a pending English revision.
    $this->drupalGet($edit_path);
    $this->assertSession()->optionExists('moderation_state[0][state]', 'draft');
    $this->assertSession()->optionExists('moderation_state[0][state]', 'published');
    $this->assertSession()->optionExists('moderation_state[0][state]', 'archived');

    // Create new moderated content (revision 1).
    $this->drupalPostForm('node/add/moderated_content', [
      'title[0][value]' => 'Third moderated content',
      'moderation_state[0][state]' => 'published',
    ], t('Save'));

    $node = $this->drupalGetNodeByTitle('Third moderated content');
    $this->assertNotEmpty($node->language(), 'en');
    $edit_path = sprintf('node/%d/edit', $node->id());
    $translate_path = sprintf('node/%d/translations/add/en/fr', $node->id());

    // Translate it, without updating data (revision 2).
    $this->drupalGet($translate_path);
    $this->assertSession()->optionExists('moderation_state[0][state]', 'draft');
    $this->assertSession()->optionExists('moderation_state[0][state]', 'published');
    $this->assertSession()->optionExists('moderation_state[0][state]', 'archived');
    $this->drupalPostForm(NULL, [
      'moderation_state[0][state]' => 'draft',
    ], t('Save (this translation)'));

    // Add another draft for the translation (revision 3).
    $this->drupalGet($edit_path, ['language' => $french]);
    $this->assertSession()->optionExists('moderation_state[0][state]', 'draft');
    $this->assertSession()->optionExists('moderation_state[0][state]', 'published');
    $this->assertSession()->optionNotExists('moderation_state[0][state]', 'archived');
    $this->drupalPostForm(NULL, [
      'moderation_state[0][state]' => 'draft',
    ], t('Save (this translation)'));

    // Updating and publishing the french translation is still possible.
    $this->drupalGet($edit_path, ['language' => $french]);
    $this->assertSession()->optionExists('moderation_state[0][state]', 'draft');
    $this->assertSession()->optionExists('moderation_state[0][state]', 'published');
    $this->assertSession()->optionNotExists('moderation_state[0][state]', 'archived');
    $this->drupalPostForm(NULL, [
      'moderation_state[0][state]' => 'published',
    ], t('Save (this translation)'));

    // Now the french translation is published, an english draft can be added.
    $this->drupalGet($edit_path);
    $this->assertSession()->optionExists('moderation_state[0][state]', 'draft');
    $this->assertSession()->optionExists('moderation_state[0][state]', 'published');
    $this->assertSession()->optionExists('moderation_state[0][state]', 'archived');
    $this->drupalPostForm(NULL, [
      'moderation_state[0][state]' => 'draft',
    ], t('Save (this translation)'));
  }

  /**
   * Test the moderation_state field when an alternative widget is set.
   */
  public function testAlternativeModerationStateWidget() {
    $entity_form_display = EntityFormDisplay::load('node.moderated_content.default');
    $entity_form_display->setComponent('moderation_state', [
      'type' => 'string_textfield',
      'region' => 'content',
    ]);
    $entity_form_display->save();
    $this->drupalPostForm('node/add/moderated_content', [
      'title[0][value]' => 'Test content',
      'moderation_state[0][value]' => 'published',
    ], 'Save');
    $this->assertSession()->pageTextContains('Moderated content Test content has been created.');
  }

  /**
   * Tests that workflows and states can not be deleted if they are in use.
   *
   * @covers \Drupal\content_moderation\Plugin\WorkflowType\ContentModeration::workflowHasData
   * @covers \Drupal\content_moderation\Plugin\WorkflowType\ContentModeration::workflowStateHasData
   */
  public function testWorkflowInUse() {
    $user = $this->createUser([
      'administer workflows',
      'create moderated_content content',
      'edit own moderated_content content',
      'use editorial transition create_new_draft',
      'use editorial transition publish',
      'use editorial transition archive',
    ]);
    $this->drupalLogin($user);
    $paths = [
      'archived_state' => 'admin/config/workflow/workflows/manage/editorial/state/archived/delete',
      'editorial_workflow' => 'admin/config/workflow/workflows/manage/editorial/delete',
    ];
    $messages = [
      'archived_state' => 'This workflow state is in use. You cannot remove this workflow state until you have removed all content using it.',
      'editorial_workflow' => 'This workflow is in use. You cannot remove this workflow until you have removed all content using it.',
    ];
    foreach ($paths as $path) {
      $this->drupalGet($path);
      $this->assertSession()->buttonExists('Delete');
    }
    // Create new moderated content in draft.
    $this->drupalPostForm('node/add/moderated_content', [
      'title[0][value]' => 'Some moderated content',
      'body[0][value]' => 'First version of the content.',
      'moderation_state[0][state]' => 'draft',
    ], 'Save');

    // The archived state is not used yet, so can still be deleted.
    $this->drupalGet($paths['archived_state']);
    $this->assertSession()->buttonExists('Delete');

    // The workflow is being used, so can't be deleted.
    $this->drupalGet($paths['editorial_workflow']);
    $this->assertSession()->buttonNotExists('Delete');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($messages['editorial_workflow']);

    $node = $this->drupalGetNodeByTitle('Some moderated content');
    $this->drupalPostForm('node/' . $node->id() . '/edit', [
      'moderation_state[0][state]' => 'published',
    ], 'Save');
    $this->drupalPostForm('node/' . $node->id() . '/edit', [
      'moderation_state[0][state]' => 'archived',
    ], 'Save');

    // Now the archived state is being used so it can not be deleted either.
    foreach ($paths as $type => $path) {
      $this->drupalGet($path);
      $this->assertSession()->buttonNotExists('Delete');
      $this->assertSession()->statusCodeEquals(200);
      $this->assertSession()->pageTextContains($messages[$type]);
    }
  }

}

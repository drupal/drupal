<?php

namespace Drupal\Tests\quickedit\FunctionalJavascript;

use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;

/**
 * @group quickedit
 */
class QuickEditIntegrationTest extends QuickEditJavascriptTestBase {

  use EntityReferenceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'editor',
    'ckeditor',
    'taxonomy',
    'block',
    'block_content',
    'hold_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * A user with permissions to edit Articles and use Quick Edit.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $contentAuthorUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Create text format, associate CKEditor.
    FilterFormat::create([
      'format' => 'some_format',
      'name' => 'Some format',
      'weight' => 0,
      'filters' => [
        'filter_html' => [
          'status' => 1,
          'settings' => [
            'allowed_html' => '<h2 id> <h3> <h4> <h5> <h6> <p> <br> <strong> <a href hreflang>',
          ],
        ],
      ],
    ])->save();
    Editor::create([
      'format' => 'some_format',
      'editor' => 'ckeditor',
    ])->save();

    // Create the Article node type.
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);

    // Add "tags" vocabulary + field to the Article node type.
    $vocabulary = Vocabulary::create([
      'name' => 'Tags',
      'vid' => 'tags',
    ]);
    $vocabulary->save();
    $field_name = 'field_' . $vocabulary->id();
    $handler_settings = [
      'target_bundles' => [
        $vocabulary->id() => $vocabulary->id(),
      ],
      'auto_create' => TRUE,
    ];
    $this->createEntityReferenceField('node', 'article', $field_name, 'Tags', 'taxonomy_term', 'default', $handler_settings, FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    // Add formatter & widget for "tags" field.
    \Drupal::entityTypeManager()
      ->getStorage('entity_form_display')
      ->load('node.article.default')
      ->setComponent($field_name, ['type' => 'entity_reference_autocomplete_tags'])
      ->save();
    \Drupal::entityTypeManager()
      ->getStorage('entity_view_display')
      ->load('node.article.default')
      ->setComponent($field_name, ['type' => 'entity_reference_label'])
      ->save();

    $this->drupalPlaceBlock('page_title_block');
    $this->drupalPlaceBlock('system_main_block');

    // Log in as a content author who can use Quick Edit and edit Articles.
    $this->contentAuthorUser = $this->drupalCreateUser([
      'access contextual links',
      'access toolbar',
      'access in-place editing',
      'access content',
      'create article content',
      'edit any article content',
      'use text format some_format',
      'edit terms in tags',
      'administer blocks',
    ]);
    $this->drupalLogin($this->contentAuthorUser);
  }

  /**
   * Tests if an article node can be in-place edited with Quick Edit.
   */
  public function testArticleNode() {
    $term = Term::create([
      'name' => 'foo',
      'vid' => 'tags',
    ]);
    $term->save();

    $node = $this->drupalCreateNode([
      'type' => 'article',
      'title' => t('My Test Node'),
      'body' => [
        'value' => '<p>Hello world!</p><p>I do not know what to say…</p><p>I wish I were eloquent.</p>',
        'format' => 'some_format',
      ],
      'field_tags' => [
        ['target_id' => $term->id()],
      ],
    ]);

    $this->drupalGet('node/' . $node->id());

    // Initial state.
    $this->awaitQuickEditForEntity('node', 1);
    $this->assertEntityInstanceStates([
      'node/1[0]' => 'closed',
    ]);
    $this->assertEntityInstanceFieldStates('node', 1, 0, [
      'node/1/title/en/full'      => 'inactive',
      'node/1/uid/en/full'        => 'inactive',
      'node/1/created/en/full'    => 'inactive',
      'node/1/body/en/full'       => 'inactive',
      'node/1/field_tags/en/full' => 'inactive',
    ]);

    // Start in-place editing of the article node.
    $this->startQuickEditViaToolbar('node', 1, 0);
    $this->assertEntityInstanceStates([
      'node/1[0]' => 'opened',
    ]);
    $this->assertQuickEditEntityToolbar((string) $node->label(), NULL);
    $this->assertEntityInstanceFieldStates('node', 1, 0, [
      'node/1/title/en/full'      => 'candidate',
      'node/1/uid/en/full'        => 'candidate',
      'node/1/created/en/full'    => 'candidate',
      'node/1/body/en/full'       => 'candidate',
      'node/1/field_tags/en/full' => 'candidate',
    ]);

    $assert_session = $this->assertSession();

    // Click the title field.
    $this->click('[data-quickedit-field-id="node/1/title/en/full"].quickedit-candidate');
    $assert_session->waitForElement('css', '.quickedit-toolbar-field div[id*="title"]');
    $this->assertQuickEditEntityToolbar((string) $node->label(), 'Title');
    $this->assertEntityInstanceFieldStates('node', 1, 0, [
      'node/1/title/en/full'      => 'active',
      'node/1/uid/en/full'        => 'candidate',
      'node/1/created/en/full'    => 'candidate',
      'node/1/body/en/full'       => 'candidate',
      'node/1/field_tags/en/full' => 'candidate',
    ]);
    $this->assertEntityInstanceFieldMarkup([
      'node/1/title/en/full' => '[contenteditable="true"]',
    ]);

    // Append something to the title.
    $this->typeInPlainTextEditor('[data-quickedit-field-id="node/1/title/en/full"].quickedit-candidate', ' Llamas are awesome!');
    $this->awaitEntityInstanceFieldState('node', 1, 0, 'title', 'en', 'changed');
    $this->assertEntityInstanceFieldStates('node', 1, 0, [
      'node/1/title/en/full'      => 'changed',
      'node/1/uid/en/full'        => 'candidate',
      'node/1/created/en/full'    => 'candidate',
      'node/1/body/en/full'       => 'candidate',
      'node/1/field_tags/en/full' => 'candidate',
    ]);

    // Click the body field.
    hold_test_response(TRUE);
    $this->click('[data-quickedit-entity-id="node/1"] .field--name-body');
    $assert_session->waitForElement('css', '.quickedit-toolbar-field div[id*="body"]');
    $this->assertQuickEditEntityToolbar((string) $node->label(), 'Body');
    $this->assertEntityInstanceFieldStates('node', 1, 0, [
      'node/1/title/en/full'      => 'saving',
      'node/1/uid/en/full'        => 'candidate',
      'node/1/created/en/full'    => 'candidate',
      'node/1/body/en/full'       => 'active',
      'node/1/field_tags/en/full' => 'candidate',
    ]);
    hold_test_response(FALSE);

    $this->assertEntityInstanceFieldMarkup([
      'node/1/body/en/full'       => '.cke_editable_inline',
      'node/1/field_tags/en/full' => ':not(.quickedit-editor-is-popup)',
    ]);
    $this->assertSession()->elementExists('css', '#quickedit-entity-toolbar .quickedit-toolgroup.wysiwyg-main > .cke_chrome .cke_top[role="presentation"] .cke_toolbar[role="toolbar"] .cke_toolgroup[role="presentation"] > .cke_button[title~="Bold"][role="button"]');

    // Wait for the validating & saving of the title to complete.
    $this->awaitEntityInstanceFieldState('node', 1, 0, 'title', 'en', 'candidate');

    // Click the tags field.
    hold_test_response(TRUE);
    $this->click('[data-quickedit-field-id="node/1/field_tags/en/full"]');
    $assert_session->waitForElement('css', '.quickedit-toolbar-field div[id*="tags"]');
    $this->assertQuickEditEntityToolbar((string) $node->label(), 'Tags');
    $this->assertEntityInstanceFieldStates('node', 1, 0, [
      'node/1/uid/en/full'        => 'candidate',
      'node/1/created/en/full'    => 'candidate',
      'node/1/body/en/full'       => 'candidate',
      'node/1/field_tags/en/full' => 'activating',
      'node/1/title/en/full'      => 'candidate',
    ]);
    $this->assertEntityInstanceFieldMarkup([
      'node/1/title/en/full'      => '.quickedit-changed',
      'node/1/field_tags/en/full' => '.quickedit-editor-is-popup',
    ]);
    // Assert the "Loading…" popup appears.
    $this->assertSession()->elementExists('css', '.quickedit-form-container > .quickedit-form[role="dialog"] > .placeholder');
    hold_test_response(FALSE);
    // Wait for the form to load.
    $this->assertJsCondition('document.querySelector(\'.quickedit-form-container > .quickedit-form[role="dialog"] > .placeholder\') === null');
    $this->assertEntityInstanceFieldStates('node', 1, 0, [
      'node/1/uid/en/full'        => 'candidate',
      'node/1/created/en/full'    => 'candidate',
      'node/1/body/en/full'       => 'candidate',
      'node/1/field_tags/en/full' => 'active',
      'node/1/title/en/full'      => 'candidate',
    ]);

    // Enter an additional tag.
    $this->typeInFormEditorTextInputField('field_tags[target_id]', 'foo, bar');
    $this->awaitEntityInstanceFieldState('node', 1, 0, 'field_tags', 'en', 'changed');
    $this->assertEntityInstanceFieldStates('node', 1, 0, [
      'node/1/uid/en/full'        => 'candidate',
      'node/1/created/en/full'    => 'candidate',
      'node/1/body/en/full'       => 'candidate',
      'node/1/field_tags/en/full' => 'changed',
      'node/1/title/en/full'      => 'candidate',
    ]);

    // Click 'Save'.
    hold_test_response(TRUE);
    $this->saveQuickEdit();
    $this->assertEntityInstanceStates([
      'node/1[0]' => 'committing',
    ]);
    $this->assertEntityInstanceFieldStates('node', 1, 0, [
      'node/1/uid/en/full'        => 'candidate',
      'node/1/created/en/full'    => 'candidate',
      'node/1/body/en/full'       => 'candidate',
      'node/1/field_tags/en/full' => 'saving',
      'node/1/title/en/full'      => 'candidate',
    ]);
    $this->assertEntityInstanceFieldMarkup([
      'node/1/title/en/full'      => '.quickedit-changed',
      'node/1/field_tags/en/full' => '.quickedit-changed',
    ]);
    hold_test_response(FALSE);

    // Wait for the saving of the tags field to complete.
    $this->assertJsCondition("Drupal.quickedit.collections.entities.get('node/1[0]').get('state') === 'closed'");
    $this->assertEntityInstanceStates([
      'node/1[0]' => 'closed',
    ]);

    // Get the load again and ensure the values are the expected values.
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->pageTextContains(' Llamas are awesome!');
    $this->assertSession()->linkExists('foo');
    $this->assertSession()->linkExists('bar');
  }

  /**
   * Tests if a custom can be in-place edited with Quick Edit.
   */
  public function testCustomBlock() {
    $block_content_type = BlockContentType::create([
      'id' => 'basic',
      'label' => 'basic',
      'revision' => FALSE,
    ]);
    $block_content_type->save();
    block_content_add_body_field($block_content_type->id());

    $block_content = BlockContent::create([
      'info' => 'Llama',
      'type' => 'basic',
      'body' => [
        'value' => 'The name "llama" was adopted by European settlers from native Peruvians.',
        'format' => 'some_format',
      ],
    ]);
    $block_content->save();
    $this->drupalPlaceBlock('block_content:' . $block_content->uuid(), [
      'label' => 'My custom block!',
    ]);

    $this->drupalGet('');

    // Initial state.
    $this->awaitQuickEditForEntity('block_content', 1);
    $this->assertEntityInstanceStates([
      'block_content/1[0]' => 'closed',
    ]);

    // Start in-place editing of the article node.
    $this->startQuickEditViaToolbar('block_content', 1, 0);
    $this->assertEntityInstanceStates([
      'block_content/1[0]' => 'opened',
    ]);
    $this->assertQuickEditEntityToolbar((string) $block_content->label(), 'Body');
    $this->assertEntityInstanceFieldStates('block_content', 1, 0, [
      'block_content/1/body/en/full' => 'highlighted',
    ]);

    // Click the body field.
    $this->click('[data-quickedit-entity-id="block_content/1"] .field--name-body');
    $assert_session = $this->assertSession();
    $assert_session->waitForElement('css', '.quickedit-toolbar-field div[id*="body"]');
    $this->assertQuickEditEntityToolbar((string) $block_content->label(), 'Body');
    $this->assertEntityInstanceFieldStates('block_content', 1, 0, [
      'block_content/1/body/en/full' => 'active',
    ]);
    $this->assertEntityInstanceFieldMarkup([
      'block_content/1/body/en/full' => '.cke_editable_inline',
    ]);
    $this->assertSession()->elementExists('css', '#quickedit-entity-toolbar .quickedit-toolgroup.wysiwyg-main > .cke_chrome .cke_top[role="presentation"] .cke_toolbar[role="toolbar"] .cke_toolgroup[role="presentation"] > .cke_button[title~="Bold"][role="button"]');
  }

}

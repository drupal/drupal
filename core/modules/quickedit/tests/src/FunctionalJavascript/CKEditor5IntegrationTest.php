<?php

namespace Drupal\Tests\quickedit\FunctionalJavascript;

use Drupal\ckeditor5\Plugin\Editor\CKEditor5;
use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * Tests that Quick Edit can load CKEditor 5.
 *
 * @group quickedit
 * @internal
 */
class CKEditor5IntegrationTest extends QuickEditJavascriptTestBase {

  use EntityReferenceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'editor',
    'ckeditor5',
    'hold_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

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
    // Create text format, associate CKEditor 5, validate.
    FilterFormat::create([
      'format' => 'some_format',
      'name' => 'Some format',
      'filters' => [
        'filter_html' => [
          'status' => TRUE,
          'settings' => [
            'allowed_html' => '<p> <br> <h2> <h3> <h4> <h5> <h6> <strong> <em>',
          ],
        ],
      ],
    ])->save();
    Editor::create([
      'format' => 'some_format',
      'editor' => 'ckeditor5',
      'settings' => [
        'toolbar' => [
          'items' => ['heading', 'bold', 'italic'],
        ],
        'plugins' => [
          'ckeditor5_heading' => [
            'enabled_headings' => [
              'heading2',
              'heading3',
              'heading4',
              'heading5',
              'heading6',
            ],
          ],
        ],
      ],
      'image_upload' => [
        'status' => FALSE,
      ],
    ])->save();
    $this->assertSame([], array_map(
      function (ConstraintViolation $v) {
        return (string) $v->getMessage();
      },
      iterator_to_array(CKEditor5::validatePair(
        Editor::load('some_format'),
        FilterFormat::load('some_format')
      ))
    ));

    // Create the Article node type.
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);

    // Log in as a content author who can use Quick Edit and edit Articles.
    $this->contentAuthorUser = $this->drupalCreateUser([
      'access contextual links',
      'access toolbar',
      'access in-place editing',
      'access content',
      'create article content',
      'edit any article content',
      'use text format some_format',
    ]);
    $this->drupalLogin($this->contentAuthorUser);
  }

  /**
   * Tests that changes can be discarded.
   */
  public function testDiscard() {
    $page = $this->getSession()->getPage();
    $node = $this->drupalCreateNode([
      'type' => 'article',
      'title' => t('My Test Node'),
      'body' => [
        'value' => '<p>Hello world!</p><p>I do not know what to say…</p><p>I wish I were eloquent.</p>',
        'format' => 'some_format',
      ],
    ]);

    $this->drupalGet('node/' . $node->id());

    // Initial state.
    $this->awaitQuickEditForEntity('node', 1);
    $this->assertEntityInstanceStates([
      'node/1[0]' => 'closed',
    ]);
    $this->assertEntityInstanceFieldStates('node', 1, 0, [
      'node/1/title/en/full' => 'inactive',
      'node/1/body/en/full' => 'inactive',
    ]);

    // Start in-place editing of the article node.
    $this->startQuickEditViaToolbar('node', 1, 0);
    $this->assertEntityInstanceStates([
      'node/1[0]' => 'opened',
    ]);
    $this->assertQuickEditEntityToolbar((string) $node->label(), NULL);
    $this->assertEntityInstanceFieldStates('node', 1, 0, [
      'node/1/title/en/full' => 'candidate',
      'node/1/body/en/full' => 'candidate',
    ]);

    $assert_session = $this->assertSession();

    // Click the body field.
    hold_test_response(TRUE);
    $this->click('[data-quickedit-field-id="node/1/body/en/full"]');
    $assert_session->waitForElement('css', '.quickedit-toolbar-field div[id*="body"]');
    $this->assertQuickEditEntityToolbar((string) $node->label(), 'Body');
    $this->assertEntityInstanceFieldStates('node', 1, 0, [
      'node/1/title/en/full' => 'candidate',
      'node/1/body/en/full' => 'active',
    ]);
    hold_test_response(FALSE);

    $this->assertEntityInstanceFieldMarkup([
      'node/1/body/en/full' => '.ck-editor__editable_inline',
    ]);
    $this->assertSession()
      ->elementExists('css', '#quickedit-entity-toolbar .quickedit-toolgroup.wysiwyg-main .ck-toolbar[role="toolbar"] .ck-toolbar__items > .ck-button[type="button"]');

    // Click the body field.
    $this->click('[data-quickedit-field-id="node/1/body/en/full"]');
    $assert_session->waitForElement('css', '.quickedit-toolbar-field div[id*="body"]');
    $this->typeInPlainTextEditor('[data-quickedit-field-id="node/1/body/en/full"]', ' I am not wanted here');
    $assert_session->waitForElement('css', '.quickedit-toolbar-field div[id*="body"].quickedit-changed');
    $this->assertEntityInstanceFieldStates('node', 1, 0, [
      'node/1/title/en/full' => 'candidate',
      'node/1/body/en/full' => 'changed',
    ]);

    $assert_session->pageTextContains('I am not wanted here');

    // Click the 'Cancel' button.
    $page->find('css', '.action-cancel.quickedit-button')->press();
    hold_test_response(TRUE);

    // Click the 'Discard Changes' button.
    $discard_changes_button = $page->findAll('css', '.ui-dialog-buttonset .button')[1];
    $this->assertEquals('Discard changes', $discard_changes_button->getText());
    $discard_changes_button->press();

    $assert_session->pageTextNotContains('I am not wanted here');
    hold_test_response(FALSE);
  }

  /**
   * Tests if an article node can be in-place edited with Quick Edit.
   */
  public function testArticleNode() {
    $assert_session = $this->assertSession();

    $node = $this->drupalCreateNode([
      'type' => 'article',
      'title' => t('My Test Node'),
      'body' => [
        'value' => '<p>Hello world!</p><p>I do not know what to say…</p><p>I wish I were eloquent.</p>',
        'format' => 'some_format',
      ],
    ]);

    $this->drupalGet('node/' . $node->id());

    // Initial state.
    $this->awaitQuickEditForEntity('node', 1);
    $this->assertEntityInstanceStates([
      'node/1[0]' => 'closed',
    ]);
    $this->assertEntityInstanceFieldStates('node', 1, 0, [
      'node/1/title/en/full' => 'inactive',
      'node/1/body/en/full' => 'inactive',
    ]);

    // Start in-place editing of the article node.
    $this->startQuickEditViaToolbar('node', 1, 0);
    $this->assertEntityInstanceStates([
      'node/1[0]' => 'opened',
    ]);
    $this->assertQuickEditEntityToolbar((string) $node->label(), NULL);
    $this->assertEntityInstanceFieldStates('node', 1, 0, [
      'node/1/title/en/full' => 'candidate',
      'node/1/body/en/full' => 'candidate',
    ]);

    // Confirm that the JavaScript that generates IE11 warnings loads.
    $assert_session->elementExists('css', 'script[src*="ckeditor5/js/ie11.user.warnings.js"]');

    // Click the body field.
    hold_test_response(TRUE);
    $this->click('[data-quickedit-field-id="node/1/body/en/full"]');
    $assert_session->waitForElement('css', '.quickedit-toolbar-field div[id*="body"]');
    $this->assertQuickEditEntityToolbar((string) $node->label(), 'Body');
    $this->assertEntityInstanceFieldStates('node', 1, 0, [
      'node/1/title/en/full' => 'candidate',
      'node/1/body/en/full' => 'active',
    ]);
    hold_test_response(FALSE);

    $this->assertEntityInstanceFieldMarkup([
      'node/1/body/en/full' => '.ck-editor__editable_inline',
    ]);
    $this->assertSession()
      ->elementExists('css', '#quickedit-entity-toolbar .quickedit-toolgroup.wysiwyg-main .ck-toolbar[role="toolbar"] .ck-toolbar__items > .ck-button[type="button"]');

    // Click the body field.
    $this->click('[data-quickedit-field-id="node/1/body/en/full"]');
    $assert_session->waitForElement('css', '.quickedit-toolbar-field div[id*="body"]');
    $this->typeInPlainTextEditor('[data-quickedit-field-id="node/1/body/en/full"]', ' Added text with CKEditor 5');
    $assert_session->waitForElement('css', '.quickedit-toolbar-field div[id*="body"].quickedit-changed');
    $this->assertEntityInstanceFieldStates('node', 1, 0, [
      'node/1/title/en/full' => 'candidate',
      'node/1/body/en/full' => 'changed',
    ]);

    // Click 'Save'.
    hold_test_response(TRUE);
    $this->saveQuickEdit();
    $this->assertEntityInstanceStates([
      'node/1[0]' => 'committing',
    ]);

    $this->assertEntityInstanceFieldStates('node', 1, 0, [
      'node/1/title/en/full' => 'candidate',
      'node/1/body/en/full' => 'saving',
    ]);

    $this->assertEntityInstanceFieldMarkup([
      'node/1/body/en/full' => '.quickedit-changed',
    ]);
    hold_test_response(FALSE);

    $this->assertJsCondition("Drupal.quickedit.collections.entities.get('node/1[0]').get('state') === 'closed'");
    $this->assertEntityInstanceStates([
      'node/1[0]' => 'closed',
    ]);

    // Get the load again and ensure the values are the expected values.
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->pageTextContains('I wish I were eloquent.');
    $this->assertSession()->pageTextContains('Added text with CKEditor 5');
  }

}

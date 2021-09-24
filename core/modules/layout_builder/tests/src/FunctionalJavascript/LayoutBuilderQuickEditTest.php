<?php

namespace Drupal\Tests\layout_builder\FunctionalJavascript;

use Drupal\node\Entity\NodeType;
use Drupal\Tests\contextual\FunctionalJavascript\ContextualLinkClickTrait;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Drupal\Tests\quickedit\FunctionalJavascript\QuickEditJavascriptTestBase;

/**
 * Tests that Layout Builder functions with Quick Edit.
 *
 * @covers layout_builder_entity_view_alter()
 * @covers layout_builder_quickedit_render_field()
 *
 * @group layout_builder
 */
class LayoutBuilderQuickEditTest extends QuickEditJavascriptTestBase {

  use EntityReferenceTestTrait;
  use ContextualLinkClickTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'layout_builder',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * The article node under test.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $article;

  /**
   * A user with permissions to edit Articles and use Quick Edit.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $contentAuthorUser;

  /**
   * Whether the test is currently using Layout Builder on the entity.
   *
   * Allows performing assertions with and without Layout Builder.
   *
   * @var bool
   *
   * @see ::assertEntityInstanceFieldStates()
   * @see ::assertEntityInstanceFieldMarkup()
   */
  protected $usingLayoutBuilder = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalPlaceBlock('page_title_block');

    // Create the Article node type.
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    $this->article = $this->drupalCreateNode([
      'type' => 'article',
      'title' => t('My Test Node'),
      'body' => [
        'value' => 'Hello Layout Builder!',
        'format' => 'plain_text',
      ],
    ]);

    // Log in as a content author who can use Quick Edit and edit Articles.
    $this->contentAuthorUser = $this->drupalCreateUser([
      'access contextual links',
      'access in-place editing',
      'access content',
      'edit any article content',
    ]);
    $this->drupalLogin($this->contentAuthorUser);
  }

  /**
   * Tests that Quick Edit still works even when there are duplicate fields.
   *
   * @see https://www.drupal.org/project/drupal/issues/3041850
   */
  public function testQuickEditIgnoresDuplicateFields() {
    // Place the body field a second time using Layout Builder.
    $this->enableLayouts('admin/structure/types/manage/article/display/default');
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    $this->loginLayoutAdmin();
    $this->drupalGet('admin/structure/types/manage/article/display/default/layout');
    $page->clickLink('Add block');
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '#drupal-off-canvas'));
    $assert_session->assertWaitOnAjaxRequest();
    $page->clickLink('Body');
    $assert_session->assertWaitOnAjaxRequest();
    $page->pressButton('Add block');
    $assert_session->assertWaitOnAjaxRequest();
    $page->pressButton('Save layout');
    $this->assertNotEmpty($assert_session->waitForElement('css', '.messages--status'));
    $assert_session->pageTextContains('The layout has been saved.');

    $this->drupalLogin($this->contentAuthorUser);
    $this->usingLayoutBuilder = TRUE;
    $this->assertQuickEditInit(['title']);
    $this->drupalLogin($this->drupalCreateUser([
      'access contextual links',
      'access in-place editing',
      'access content',
      'edit any article content',
      'administer nodes',
    ]));
    $this->assertQuickEditInit(['title', 'uid', 'created']);
  }

  /**
   * Tests Quick Edit boots correctly with Layout Builder defaults & overrides.
   *
   * @param bool $use_revisions
   *   If revisions are used.
   *
   * @dataProvider providerEnableDisableLayoutBuilder
   */
  public function testEnableDisableLayoutBuilder($use_revisions, $admin_permission = FALSE) {
    if (!$use_revisions) {
      $content_type = NodeType::load('article');
      $content_type->setNewRevision(FALSE);
      $content_type->save();
    }
    $fields = [
      'title',
      'body',
    ];
    if ($admin_permission) {
      $fields = array_merge($fields, ['uid', 'created']);
      $this->drupalLogin($this->drupalCreateUser([
        'access contextual links',
        'access in-place editing',
        'access content',
        'edit any article content',
        'administer nodes',
      ]));
    }

    // Test article with Layout Builder disabled.
    $this->assertQuickEditInit($fields);

    // Test article with Layout Builder enabled.
    $this->enableLayouts('admin/structure/types/manage/article/display/default');
    $this->usingLayoutBuilder = TRUE;
    $this->assertQuickEditInit($fields);

    // Test article with Layout Builder override.
    $this->createLayoutOverride();
    $this->assertQuickEditInit($fields);

    // If we're using revisions, it's not possible to disable Layout Builder
    // without deleting the node (unless the revisions containing the override
    // would be deleted).
    if (!$use_revisions) {
      // Test article with Layout Builder when reverted back to defaults.
      $this->revertLayoutToDefaults();
      $this->assertQuickEditInit($fields);

      // Test with Layout Builder disabled after being enabled.
      $this->usingLayoutBuilder = FALSE;
      $this->disableLayoutBuilder('admin/structure/types/manage/article/display/default');
      $this->assertQuickEditInit($fields);
    }
  }

  /**
   * DataProvider for testEnableDisableLayoutBuilder().
   */
  public function providerEnableDisableLayoutBuilder() {
    return [
      'use revisions, not admin' => [TRUE],
      'do not use revisions, not admin' => [FALSE],
      'use revisions, admin' => [TRUE, TRUE],
      'do not use revisions, admin' => [FALSE, TRUE],
    ];
  }

  /**
   * Enables layouts at an admin path.
   *
   * @param string $path
   *   The manage display path.
   */
  protected function enableLayouts($path) {
    // Save the current user to re-login after Layout Builder changes.
    $user = $this->loggedInUser;
    $this->loginLayoutAdmin();
    $page = $this->getSession()->getPage();
    $this->drupalGet($path);
    $page->checkField('layout[enabled]');
    $page->checkField('layout[allow_custom]');
    $page->pressButton('Save');
    $this->drupalLogin($user);
  }

  /**
   * {@inheritdoc}
   */
  protected function assertEntityInstanceFieldStates($entity_type_id, $entity_id, $entity_instance_id, array $expected_field_states) {
    parent::assertEntityInstanceFieldStates($entity_type_id, $entity_id, $entity_instance_id, $this->replaceLayoutBuilderFieldIdKeys($expected_field_states));
  }

  /**
   * {@inheritdoc}
   */
  protected function assertEntityInstanceFieldMarkup($entity_type_id, $entity_id, $entity_instance_id, array $expected_field_attributes) {
    parent::assertEntityInstanceFieldMarkup($entity_type_id, $entity_id, $entity_instance_id, $this->replaceLayoutBuilderFieldIdKeys($expected_field_attributes));
  }

  /**
   * Replaces the array keys with Layout Builder field IDs when needed.
   *
   * @param array $array
   *   The array with field IDs as keys.
   *
   * @return array
   *   The array with the keys replaced.
   */
  protected function replaceLayoutBuilderFieldIdKeys(array $array) {
    if (!$this->usingLayoutBuilder) {
      return $array;
    }
    $replacement = [];
    foreach ($array as $field_key => $value) {
      $new_field_key = $this->getQuickEditFieldId($field_key);
      $replacement[$new_field_key] = $value;
    }
    return $replacement;
  }

  /**
   * Login the Layout admin user for the test.
   */
  protected function loginLayoutAdmin() {
    // Enable for the Layout Builder.
    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'access content',
      'administer node display',
      'administer node fields',
      'administer blocks',
    ]));
  }

  /**
   * Creates a layout override.
   */
  protected function createLayoutOverride() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    // Save the current user to re-login after Layout Builder changes.
    $user = $this->loggedInUser;
    $this->loginLayoutAdmin();
    $this->drupalGet('node/' . $this->article->id() . '/layout');

    $page->pressButton('Save layout');
    $this->assertNotEmpty($assert_session->waitForElement('css', '.messages--status'));
    $assert_session->pageTextContains('The layout override has been saved.');
    $this->drupalLogin($user);
  }

  /**
   * Reverts a layout override.
   */
  protected function revertLayoutToDefaults() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();
    // Save the current user to re-login after Layout Builder changes.
    $user = $this->loggedInUser;
    $this->loginLayoutAdmin();
    $this->drupalGet('node/' . $this->article->id() . '/layout');
    $assert_session->buttonExists('Revert to defaults');
    $page->pressButton('Revert to defaults');
    $page->pressButton('Revert');
    $assert_session->pageTextContains('The layout has been reverted back to defaults.');
    $this->drupalLogin($user);
  }

  /**
   * Disables Layout Builder.
   *
   * @param string $path
   *   The path to the manage display page.
   */
  protected function disableLayoutBuilder($path) {
    $page = $this->getSession()->getPage();
    // Save the current user to re-login after Layout Builder changes.
    $user = $this->loggedInUser;
    $this->loginLayoutAdmin();
    $this->drupalGet($path);
    $page->uncheckField('layout[allow_custom]');
    $page->uncheckField('layout[enabled]');
    $page->pressButton('Save');
    $page->pressButton('Confirm');
    $this->drupalLogin($user);
  }

  /**
   * Asserts that Quick Edit is initialized on the node view correctly.
   *
   * @todo Replace calls to this method with calls to ::doTestArticle() in
   *    https://www.drupal.org/node/3037436.
   *
   * @param string[] $fields
   *   The fields test.
   */
  private function assertQuickEditInit(array $fields) {
    $this->assertNotEmpty($fields);
    $node = $this->article;
    $this->drupalGet('node/' . $node->id());

    // Initial state.
    $this->awaitQuickEditForEntity('node', 1);
    $this->assertEntityInstanceStates([
      'node/1[0]' => 'closed',
    ]);
    $field_states = [];
    foreach ($fields as $field) {
      $field_states["node/1/$field/en/full"] = 'inactive';
    }
    $this->assertEntityInstanceFieldStates('node', 1, 0, $field_states);
  }

  /**
   * {@inheritdoc}
   */
  protected function getQuickEditFieldId($original_field_id) {
    $page = $this->getSession()->getPage();
    $parts = explode('/', $original_field_id);
    // Removes the last part of the field id which will contain the Quick Edit
    // view mode ID. When using the Layout Builder the view_mode will contain a
    // hash of the layout sections and will be different each time the layout
    // changes.
    array_pop($parts);
    $field_key_without_view_mode = implode('/', $parts);
    $element = $page->find('css', "[data-quickedit-field-id^=\"$field_key_without_view_mode\"]");
    $this->assertNotEmpty($element, "Found Quick Edit-enabled field whose data-quickedit-field attribute starts with: $field_key_without_view_mode");
    try {
      $has_attribute = $element->hasAttribute('data-quickedit-field-id');
    }
    catch (\Exception $e) {
      $has_attribute = FALSE;
    }
    $this->assertTrue($has_attribute, $field_key_without_view_mode);
    $new_field_id = $element->getAttribute('data-quickedit-field-id');
    return $new_field_id;
  }

}

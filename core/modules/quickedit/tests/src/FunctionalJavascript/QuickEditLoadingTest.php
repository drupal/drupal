<?php

namespace Drupal\Tests\quickedit\FunctionalJavascript;

use Behat\Mink\Session;
use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Tests\contextual\FunctionalJavascript\ContextualLinkClickTrait;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests loading of in-place editing functionality and lazy loading of its
 * in-place editors.
 *
 * @group quickedit
 */
class QuickEditLoadingTest extends WebDriverTestBase {

  use ContextualLinkClickTrait;

  use TestFileCreationTrait {
    getTestFiles as drupalGetTestFiles;
  }

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'contextual',
    'quickedit',
    'filter',
    'node',
    'image',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * A user with permissions to create and edit articles.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $authorUser;

  /**
   * A test node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $testNode;

  /**
   * An author user with permissions to access in-place editor.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $editorUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a text format.
    $filtered_html_format = FilterFormat::create([
      'format' => 'filtered_html',
      'name' => 'Filtered HTML',
      'weight' => 0,
      'filters' => [],
    ]);
    $filtered_html_format->save();

    // Create a node type.
    $this->drupalCreateContentType([
      'type' => 'article',
      'name' => 'Article',
    ]);

    // Set the node type to initially not have revisions.
    // Testing with revisions will be done later.
    $node_type = NodeType::load('article');
    $node_type->setNewRevision(FALSE);
    $node_type->save();

    // Create one node of the above node type using the above text format.
    $this->testNode = $this->drupalCreateNode([
      'type' => 'article',
      'body' => [
        0 => [
          'value' => '<p>How are you?</p>',
          'format' => 'filtered_html',
        ],
      ],
      'revision_log' => $this->randomString(),
    ]);

    // Create 2 users, the only difference being the ability to use in-place
    // editing
    $basic_permissions = [
      'access content',
      'create article content',
      'edit any article content',
      'use text format filtered_html',
      'access contextual links',
    ];
    $this->authorUser = $this->drupalCreateUser($basic_permissions);
    $this->editorUser = $this->drupalCreateUser(array_merge($basic_permissions, ['access in-place editing']));
  }

  /**
   * Tests the loading of Quick Edit with different permissions.
   */
  public function testUserPermissions() {
    $assert = $this->assertSession();
    $this->drupalLogin($this->authorUser);
    $this->drupalGet('node/1');

    // Library and in-place editors.
    $this->assertSession()->responseNotContains('core/modules/quickedit/js/quickedit.js');
    $this->assertSession()->responseNotContains('core/modules/quickedit/js/editors/formEditor.js');

    // HTML annotation and title class do not exist for users without
    // permission to in-place edit.
    $this->assertSession()->responseNotContains('data-quickedit-entity-id="node/1"');
    $this->assertSession()->responseNotContains('data-quickedit-field-id="node/1/body/en/full"');
    $this->assertSession()->elementNotExists('xpath', '//h1[contains(@class, "js-quickedit-page-title")]');
    $assert->linkNotExists('Quick edit');

    // Tests the loading of Quick Edit when a user does have access to it.
    // Also ensures lazy loading of in-place editors works.
    $nid = $this->testNode->id();
    // There should be only one revision so far.
    $node = Node::load($nid);
    $vids = \Drupal::entityTypeManager()->getStorage('node')->revisionIds($node);
    $this->assertCount(1, $vids, 'The node has only one revision.');
    $original_log = $node->revision_log->value;

    $this->drupalLogin($this->editorUser);
    $this->drupalGet('node/' . $nid);
    $page = $this->getSession()->getPage();

    // Wait "Quick edit" button for node.
    $assert->waitForElement('css', '[data-quickedit-entity-id="node/' . $nid . '"] .contextual .quickedit');
    // Click by "Quick edit".
    $this->clickContextualLink('[data-quickedit-entity-id="node/' . $nid . '"]', 'Quick edit');
    // Switch to body field.
    $page->find('css', '[data-quickedit-field-id="node/' . $nid . '/body/en/full"]')->click();

    // Wait and update body field.
    $body_field_locator = '[name="body[0][value]"]';
    $body_text = 'Fine thanks.';
    $assert->waitForElementVisible('css', $body_field_locator)->setValue('<p>' . $body_text . '</p>');

    // Wait and click by "Save" button after body field was changed.
    $assert->waitForElementVisible('css', '.quickedit-toolgroup.ops [type="submit"][aria-hidden="false"]')->click();
    $assert->waitForElementRemoved('css', '.quickedit-toolgroup.ops [type="submit"][aria-hidden="false"]');

    // Ensure that the changes take effect.
    $assert->responseMatches("|\s*$body_text\s*|");

    // Reload the page and check for updated body.
    $this->drupalGet('node/' . $nid);
    $assert->pageTextContains($body_text);

    // Ensure that a new revision has not been created.
    $node = Node::load($nid);
    $vids = \Drupal::entityTypeManager()->getStorage('node')->revisionIds($node);
    $this->assertCount(1, $vids, 'The node has only one revision.');
    $this->assertSame($original_log, $node->revision_log->value, 'The revision log message is unchanged.');
  }

  /**
   * Tests Quick Edit does not appear for entities with pending revisions.
   */
  public function testWithPendingRevision() {
    $this->drupalLogin($this->editorUser);

    // Verify that the preview is loaded correctly.
    $this->drupalGet('node/add/article');
    $this->submitForm(['title[0][value]' => 'foo'], 'Preview');
    // Verify that quickedit is not active on preview.
    $this->assertSession()->responseNotContains('data-quickedit-entity-id="node/' . $this->testNode->id() . '"');
    $this->assertSession()->responseNotContains('data-quickedit-field-id="node/' . $this->testNode->id() . '/title/' . $this->testNode->language()->getId() . '/full"');

    $this->drupalGet('node/' . $this->testNode->id());
    $this->assertSession()->responseContains('data-quickedit-entity-id="node/' . $this->testNode->id() . '"');
    $this->assertSession()->responseContains('data-quickedit-field-id="node/' . $this->testNode->id() . '/title/' . $this->testNode->language()->getId() . '/full"');

    // Wait for the page to completely load before making any changes to the
    // node. This allows Quick Edit to fetch the metadata without causing
    // database locks on SQLite.
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->testNode->title = 'Updated node';
    $this->testNode->setNewRevision(TRUE);
    $this->testNode->isDefaultRevision(FALSE);
    $this->testNode->save();

    $this->drupalGet('node/' . $this->testNode->id());
    $this->assertSession()->responseNotContains('data-quickedit-entity-id="node/' . $this->testNode->id() . '"');
    $this->assertSession()->responseNotContains('data-quickedit-field-id="node/' . $this->testNode->id() . '/title/' . $this->testNode->language()->getId() . '/full"');
  }

  /**
   * Tests the loading of Quick Edit for the title base field.
   */
  public function testTitleBaseField() {
    $page = $this->getSession()->getPage();
    $assert = $this->assertSession();
    $nid = $this->testNode->id();

    $this->drupalLogin($this->editorUser);
    $this->drupalGet('node/' . $nid);

    // Wait "Quick edit" button for node.
    $assert->waitForElement('css', '[data-quickedit-entity-id="node/' . $nid . '"] .contextual .quickedit');
    // Click by "Quick edit".
    $this->clickContextualLink('[data-quickedit-entity-id="node/' . $nid . '"]', 'Quick edit');
    // Switch to title field.
    $page->find('css', '[data-quickedit-field-id="node/' . $nid . '/title/en/full"]')->click();

    // Wait and update title field.
    $field_locator = '.field--name-title';
    $text_new = 'Obligatory question';
    $assert->waitForElementVisible('css', $field_locator)->setValue($text_new);

    // Wait and click by "Save" button after title field was changed.
    $this->assertSession()->waitForElementVisible('css', '.quickedit-toolgroup.ops [type="submit"][aria-hidden="false"]')->click();
    $assert->waitForElementRemoved('css', '.quickedit-toolgroup.ops [type="submit"][aria-hidden="false"]');

    // Ensure that the changes take effect.
    $assert->responseMatches("|\s*$text_new\s*|");

    // Reload the page and check for updated title.
    $this->drupalGet('node/' . $nid);
    $assert->pageTextContains($text_new);
  }

  /**
   * Tests that Quick Edit doesn't make fields rendered with display options
   * editable.
   */
  public function testDisplayOptions() {
    $node = Node::load('1');
    $display_settings = [
      'label' => 'inline',
    ];
    $build = $node->body->view($display_settings);
    $output = \Drupal::service('renderer')->renderRoot($build);
    $this->assertStringNotContainsString('data-quickedit-field-id', $output, 'data-quickedit-field-id attribute not added when rendering field using dynamic display options.');
  }

  /**
   * Tests Quick Edit on a node that was concurrently edited on the full node
   * form.
   */
  public function testConcurrentEdit() {
    $nid = $this->testNode->id();
    $this->drupalLogin($this->authorUser);

    // Open the edit page in the default session.
    $this->drupalGet('node/' . $nid . '/edit');

    // Switch to a concurrent session and save a quick edit change.
    // We need to do some bookkeeping to keep track of the logged in user.
    $logged_in_user = $this->loggedInUser;
    $this->loggedInUser = FALSE;
    // Register a session to preform concurrent editing.
    $driver = $this->getDefaultDriverInstance();
    $session = new Session($driver);
    $this->mink->registerSession('concurrent', $session);
    $this->mink->setDefaultSessionName('concurrent');
    $this->initFrontPage();
    $this->drupalLogin($this->editorUser);
    $this->drupalGet('node/' . $nid);

    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Wait "Quick edit" button for node.
    $assert->waitForElement('css', '[data-quickedit-entity-id="node/' . $nid . '"] .contextual .quickedit');
    // Click by "Quick edit".
    $this->clickContextualLink('[data-quickedit-entity-id="node/' . $nid . '"]', 'Quick edit');
    // Switch to body field.
    $page->find('css', '[data-quickedit-field-id="node/' . $nid . '/body/en/full"]')->click();

    // Wait and update body field.
    $body_field_locator = '[name="body[0][value]"]';
    $body_text = 'Fine thanks.';
    $assert->waitForElementVisible('css', $body_field_locator)->setValue('<p>' . $body_text . '</p>');

    // Wait and click by "Save" button after body field was changed.
    $assert->waitForElementVisible('css', '.quickedit-toolgroup.ops [type="submit"][aria-hidden="false"]')->click();
    $assert->waitForElementRemoved('css', $body_field_locator);

    // Ensure that the changes take effect.
    $assert->responseMatches("|\s*$body_text\s*|");

    // Switch back to the default session.
    $this->mink->setDefaultSessionName('default');
    $this->loggedInUser = $logged_in_user;
    // Ensure different save timestamps for field editing.
    sleep(2);
    $this->submitForm(['body[0][value]' => '<p>Concurrent edit!</p>'], 'Save');

    $this->getSession()->getPage()->hasContent('The content has either been modified by another user, or you have already submitted modifications. As a result, your changes cannot be saved.');
  }

  /**
   * Tests that Quick Edit's data- attributes are present for content blocks.
   */
  public function testContentBlock() {
    \Drupal::service('module_installer')->install(['block_content']);

    // Create and place a content_block block.
    $block = BlockContent::create([
      'info' => $this->randomMachineName(),
      'type' => 'basic',
      'langcode' => 'en',
    ]);
    $block->save();
    $this->drupalPlaceBlock('block_content:' . $block->uuid());

    // Check that the data- attribute is present.
    $this->drupalLogin($this->editorUser);
    $this->drupalGet('');
    $this->assertSession()->responseContains('data-quickedit-entity-id="block_content/1"');
  }

  /**
   * Tests that Quick Edit can handle an image field.
   */
  public function testImageField() {
    $page = $this->getSession()->getPage();
    $assert = $this->assertSession();

    // Add an image field to the content type.
    FieldStorageConfig::create([
      'field_name' => 'field_image',
      'type' => 'image',
      'entity_type' => 'node',
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_image',
      'field_type' => 'image',
      'label' => 'Image',
      'entity_type' => 'node',
      'bundle' => 'article',
    ])->save();
    \Drupal::service('entity_display.repository')->getFormDisplay('node', 'article', 'default')
      ->setComponent('field_image', [
        'type' => 'image_image',
      ])
      ->save();
    $display = EntityViewDisplay::load('node.article.default');
    $display->setComponent('field_image', [
      'type' => 'image',
    ])->save();

    // Add an image to the node.
    $this->drupalLogin($this->editorUser);
    $this->drupalGet('node/1/edit');
    $image = $this->drupalGetTestFiles('image')[0];
    $image_path = $this->container->get('file_system')->realpath($image->uri);
    $page->attachFileToField('files[field_image_0]', $image_path);
    $alt_field = $assert->waitForField('field_image[0][alt]');
    $this->assertNotEmpty($alt_field);
    $this->submitForm(['field_image[0][alt]' => 'The quick fox'], 'Save');

    // The image field form should load normally.
    // Wait "Quick edit" button for node.
    $assert->waitForElement('css', '[data-quickedit-entity-id="node/1"] .contextual .quickedit');
    // Click by "Quick edit".
    $this->clickContextualLink('[data-quickedit-entity-id="node/1"]', 'Quick edit');
    // Switch to body field.
    $assert->waitForElement('css', '[data-quickedit-field-id="node/1/field_image/en/full"]')->click();

    $field_locator = '.field--name-field-image';
    $assert->waitForElementVisible('css', $field_locator);
  }

}

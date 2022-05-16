<?php

namespace Drupal\Tests\quickedit\FunctionalJavascript;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\Tests\file\Functional\FileFieldCreationTrait;
use Drupal\Tests\TestFileCreationTrait;

/**
 * @group quickedit
 */
class QuickEditFileTest extends QuickEditJavascriptTestBase {

  use FileFieldCreationTrait;
  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'file',
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

    // Create the Article node type.
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);

    // Add file field to Article node type.
    $this->createFileField('field_file', 'node', 'article', ['file_extensions' => 'txt']);

    // Move file field to the top of all fields, so its QuickEdit Toolbar won't
    // overlap any QuickEdit-able fields, which causes (semi-)random test
    // failures.
    $entity_display = EntityViewDisplay::load('node.article.default');
    $entity_display->setComponent('field_file', ['weight' => 0]);
    $entity_display->save();

    // Log in as a content author who can use Quick Edit and edit Articles.
    $user = $this->drupalCreateUser([
      'access contextual links',
      'access toolbar',
      'access in-place editing',
      'access content',
      'create article content',
      'edit any article content',
    ]);
    $this->drupalLogin($user);
  }

  /**
   * Tests if a file can be in-place removed with Quick Edit.
   */
  public function testRemove() {
    $assert_session = $this->assertSession();

    // Create test file.
    $this->generateFile('test', 64, 10, 'text');
    $file = File::create([
      'uri' => 'public://test.txt',
      'filename' => 'test.txt',
    ]);
    $file->setPermanent();
    $file->save();

    // Create test node.
    $node = $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'My Test Node',
      'field_file' => [
        'target_id' => $file->id(),
      ],
    ]);

    $this->drupalGet($node->toUrl()->toString());

    // Start Quick Edit.
    $this->awaitQuickEditForEntity('node', 1);
    $this->startQuickEditViaToolbar('node', 1, 0);

    // Click the file field.
    $assert_session->waitForElementVisible('css', '[data-quickedit-field-id="node/1/field_file/en/full"]');
    $this->click('[data-quickedit-field-id="node/1/field_file/en/full"]');
    $assert_session->waitForElement('css', '.quickedit-toolbar-field div[id*="file"]');

    // Remove the file.
    $remove = $assert_session->waitForButton('Remove');
    $remove->click();
    // Wait for remove.
    $assert_session->waitForElement('css', 'input[name="files[field_file_0]"]');
    $this->saveQuickEdit();
    // Wait for save.
    $this->assertJsCondition("Drupal.quickedit.collections.entities.get('node/1[0]').get('state') === 'closed'");

    // Assert file is removed from node.
    $assert_session->pageTextNotContains('test.txt');
    $node = Node::load($node->id());
    $this->assertEmpty($node->get('field_file')->getValue());
  }

}

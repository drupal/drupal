<?php

namespace Drupal\Tests\layout_builder\FunctionalJavascript;

use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\file\Functional\FileFieldCreationTrait;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Test access to private files in block fields on the Layout Builder.
 *
 * @group layout_builder
 */
class InlineBlockPrivateFilesTest extends InlineBlockTestBase {

  use FileFieldCreationTrait;
  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'file',
  ];

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Update the test node type to not create new revisions by default. This
    // allows testing for cases when a new revision is made and when it isn't.
    $node_type = NodeType::load('bundle_with_section_field');
    $node_type->setNewRevision(FALSE);
    $node_type->save();
    $field_settings = [
      'file_extensions' => 'txt',
      'uri_scheme' => 'private',
    ];
    $this->createFileField('field_file', 'block_content', 'basic', $field_settings);
    $this->fileSystem = $this->container->get('file_system');
  }

  /**
   * Test access to private files added via inline blocks in the layout builder.
   */
  public function testPrivateFiles() {
    $assert_session = $this->assertSession();
    $this->drupalLogin($this->drupalCreateUser([
      'access contextual links',
      'configure any layout',
      'administer node display',
      'administer node fields',
    ]));

    // Enable layout builder and overrides.
    $this->drupalPostForm(
      static::FIELD_UI_PREFIX . '/display/default',
      ['layout[enabled]' => TRUE, 'layout[allow_custom]' => TRUE],
      'Save'
    );
    $this->drupalLogout();

    // Log in as user you can only configure layouts and access content.
    $this->drupalLogin($this->drupalCreateUser([
      'access contextual links',
      'configure any layout',
      'access content',
    ]));
    $this->drupalGet('node/1/layout');
    $file = $this->createPrivateFile('drupal.txt');

    $file_real_path = $this->fileSystem->realpath($file->getFileUri());
    $this->assertFileExists($file_real_path);
    $this->addInlineFileBlockToLayout('The file', $file);
    $this->assertSaveLayout();

    $this->drupalGet('node/1');
    $private_href1 = $this->assertFileAccessibleOnNode($file);

    // Remove the inline block with the private file.
    $this->drupalGet('node/1/layout');
    $this->removeInlineBlockFromLayout();
    $this->assertSaveLayout();

    $this->drupalGet('node/1');
    $assert_session->pageTextNotContains($file->label());
    // Try to access file directly after it has been removed. Since a new
    // revision was not created for the node the inline block is not in the
    // layout of a previous revision of the node.
    $this->drupalGet($private_href1);
    $assert_session->pageTextContains('You are not authorized to access this page');
    $assert_session->pageTextNotContains($this->getFileSecret($file));
    $this->assertFileExists($file_real_path);

    $file2 = $this->createPrivateFile('2ndFile.txt');

    $this->drupalGet('node/1/layout');
    $this->addInlineFileBlockToLayout('Number2', $file2);
    $this->assertSaveLayout();

    $this->drupalGet('node/1');
    $private_href2 = $this->assertFileAccessibleOnNode($file2);

    $this->createNewNodeRevision(1);

    $file3 = $this->createPrivateFile('3rdFile.txt');
    $this->drupalGet('node/1/layout');
    $this->replaceFileInBlock($file3);
    $this->assertSaveLayout();

    $this->drupalGet('node/1');
    $private_href3 = $this->assertFileAccessibleOnNode($file3);

    // $file2 is on a previous revision of the block which is on a previous
    // revision of the node. The user does not have access to view the previous
    // revision of the node.
    $this->drupalGet($private_href2);
    $assert_session->pageTextContains('You are not authorized to access this page');

    $node = Node::load(1);
    $node->setUnpublished();
    $node->save();
    $this->drupalGet('node/1');
    $assert_session->pageTextContains('You are not authorized to access this page');
    $this->drupalGet($private_href3);
    $assert_session->pageTextNotContains($this->getFileSecret($file3));
    $assert_session->pageTextContains('You are not authorized to access this page');

    $this->drupalGet('node/2/layout');
    $file4 = $this->createPrivateFile('drupal.txt');
    $this->addInlineFileBlockToLayout('The file', $file4);
    $this->assertSaveLayout();

    $this->drupalGet('node/2');
    $private_href4 = $this->assertFileAccessibleOnNode($file4);

    $this->createNewNodeRevision(2);

    // Remove the inline block with the private file.
    // The inline block will still be attached to the previous revision of the
    // node.
    $this->drupalGet('node/2/layout');
    $this->removeInlineBlockFromLayout();
    $this->assertSaveLayout();

    // Ensure that since the user cannot view the previous revision of the node
    // they can not view the file which is only used on that revision.
    $this->drupalGet($private_href4);
    $assert_session->pageTextContains('You are not authorized to access this page');
  }

  /**
   * Replaces the file in the block with another one.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file entity.
   */
  protected function replaceFileInBlock(FileInterface $file) {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->clickContextualLink(static::INLINE_BLOCK_LOCATOR, 'Configure');
    $assert_session->assertWaitOnAjaxRequest();
    $page->pressButton('Remove');
    $assert_session->assertWaitOnAjaxRequest();
    $this->attachFileToBlockForm($file);
    $page->pressButton('Update');
    $this->assertDialogClosedAndTextVisible($file->label(), static::INLINE_BLOCK_LOCATOR);
  }

  /**
   * Adds an entity block with a file.
   *
   * @param string $title
   *   The title field value.
   * @param \Drupal\file\Entity\File $file
   *   The file entity.
   */
  protected function addInlineFileBlockToLayout($title, File $file) {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $page->clickLink('Add Block');
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.block-categories details:contains(Create new block)'));
    $this->clickLink('Basic block');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->fieldValueEquals('Title', '');
    $page->findField('Title')->setValue($title);
    $this->attachFileToBlockForm($file);
    $page->pressButton('Add Block');
    $this->assertDialogClosedAndTextVisible($file->label(), static::INLINE_BLOCK_LOCATOR);
  }

  /**
   * Creates a private file.
   *
   * @param string $file_name
   *   The file name.
   *
   * @return \Drupal\Core\Entity\EntityInterface|\Drupal\file\Entity\File
   *   The file entity.
   */
  protected function createPrivateFile($file_name) {
    // Create a new file entity.
    $file = File::create([
      'uid' => 1,
      'filename' => $file_name,
      'uri' => "private://$file_name",
      'filemime' => 'text/plain',
      'status' => FILE_STATUS_PERMANENT,
    ]);
    file_put_contents($file->getFileUri(), $this->getFileSecret($file));
    $file->save();
    return $file;
  }

  /**
   * Asserts a file is accessible on the page.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file entity.
   *
   * @return string
   *   The file href.
   */
  protected function assertFileAccessibleOnNode(FileInterface $file) {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $assert_session->linkExists($file->label());
    $private_href = $page->findLink($file->label())->getAttribute('href');
    $page->clickLink($file->label());
    $assert_session->pageTextContains($this->getFileSecret($file));

    // Access file directly.
    $this->drupalGet($private_href);
    $assert_session->pageTextContains($this->getFileSecret($file));
    return $private_href;
  }

  /**
   * Gets the text secret for a file.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file entity.
   *
   * @return string
   *   The text secret.
   */
  protected function getFileSecret(FileInterface $file) {
    return "The secret in {$file->label()}";
  }

  /**
   * Attaches a file to the block edit form.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file to be attached.
   */
  protected function attachFileToBlockForm(FileInterface $file) {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $page->attachFileToField("files[settings_block_form_field_file_0]", $this->fileSystem->realpath($file->getFileUri()));
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertNotEmpty($assert_session->waitForLink($file->label()));
  }

  /**
   * Create a new revision of the node.
   *
   * @param int $node_id
   *   The node id.
   */
  protected function createNewNodeRevision($node_id) {
    $node = Node::load($node_id);
    $node->setTitle('Update node');
    $node->setNewRevision();
    $node->save();
  }

}

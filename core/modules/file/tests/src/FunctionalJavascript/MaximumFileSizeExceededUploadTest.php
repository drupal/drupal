<?php

namespace Drupal\Tests\file\FunctionalJavascript;

use Drupal\Component\Utility\Bytes;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\Tests\file\Functional\FileFieldCreationTrait;

/**
 * Tests uploading a file that exceeds the maximum file size.
 *
 * @group file
 */
class MaximumFileSizeExceededUploadTest extends WebDriverTestBase {

  use FileFieldCreationTrait;
  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'file'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * A test user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * The original value of the 'display_errors' PHP configuration option.
   *
   * @todo Remove this when issue #2905597 is fixed.
   * @see https://www.drupal.org/node/2905597
   *
   * @var string
   */
  protected $originalDisplayErrorsValue;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->fileSystem = $this->container->get('file_system');

    // Create the Article node type.
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);

    // Attach a file field to the node type.
    $field_settings = ['file_extensions' => 'bin txt'];
    $this->createFileField('field_file', 'node', 'article', [], $field_settings);

    // Log in as a content author who can create Articles.
    $this->user = $this->drupalCreateUser([
      'access content',
      'create article content',
    ]);
    $this->drupalLogin($this->user);

    // Disable the displaying of errors, so that the AJAX responses are not
    // contaminated with error messages about exceeding the maximum POST size.
    // @todo Remove this when issue #2905597 is fixed.
    // @see https://www.drupal.org/node/2905597
    $this->originalDisplayErrorsValue = ini_set('display_errors', '0');
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    // Restore the displaying of errors to the original value.
    // @todo Remove this when issue #2905597 is fixed.
    // @see https://www.drupal.org/node/2905597
    ini_set('display_errors', $this->originalDisplayErrorsValue);

    parent::tearDown();
  }

  /**
   * Tests that uploading files exceeding maximum size are handled correctly.
   */
  public function testUploadFileExceedingMaximumFileSize() {
    $session = $this->getSession();

    // Create a test file that exceeds the maximum POST size with 1 kilobyte.
    $post_max_size = Bytes::toNumber(ini_get('post_max_size'));
    $invalid_file = 'public://exceeding_post_max_size.bin';
    $file = fopen($invalid_file, 'wb');
    fseek($file, $post_max_size + 1024);
    fwrite($file, 0x0);
    fclose($file);

    // Go to the node creation form and try to upload the test file.
    $this->drupalGet('node/add/article');
    $page = $session->getPage();
    $page->attachFileToField("files[field_file_0]", $this->fileSystem->realpath($invalid_file));

    // An error message should appear informing the user that the file exceeded
    // the maximum file size. The error message includes the actual file size
    // limit which depends on the current environment, so we check for a part
    // of the message.
    $this->assertSession()->statusMessageContainsAfterWait('An unrecoverable error occurred. The uploaded file likely exceeded the maximum file size', 'error');

    // Now upload a valid file and check that the error message disappears.
    $valid_file = $this->generateFile('not_exceeding_post_max_size', 8, 8);
    $page->attachFileToField("files[field_file_0]", $this->fileSystem->realpath($valid_file));
    $this->assertSession()->waitForElement('named', ['id_or_name', 'field_file_0_remove_button']);
    $this->assertSession()->statusMessageNotExistsAfterWait('error');
  }

}

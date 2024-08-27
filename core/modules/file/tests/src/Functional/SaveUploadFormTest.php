<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Functional;

use Drupal\Core\File\FileExists;
use Drupal\file\Entity\File;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests the _file_save_upload_from_form() function.
 *
 * @group file
 * @group #slow
 *
 * @see _file_save_upload_from_form()
 */
class SaveUploadFormTest extends FileManagedTestBase {

  use TestFileCreationTrait {
    getTestFiles as drupalGetTestFiles;
  }

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['dblog', 'file_validator_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * An image file path for uploading.
   *
   * @var \Drupal\file\FileInterface
   */
  protected $image;

  /**
   * A PHP file path for upload security testing.
   *
   * @var string
   */
  protected $phpFile;

  /**
   * The largest file id when the test starts.
   *
   * @var int
   */
  protected $maxFidBefore;

  /**
   * Extension of the image filename.
   *
   * @var string
   */
  protected $imageExtension;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $account = $this->drupalCreateUser(['access site reports']);
    $this->drupalLogin($account);

    $image_files = $this->drupalGetTestFiles('image');
    $this->image = File::create((array) current($image_files));

    [, $this->imageExtension] = explode('.', $this->image->getFilename());
    $this->assertFileExists($this->image->getFileUri());

    $this->phpFile = current($this->drupalGetTestFiles('php'));
    $this->assertFileExists($this->phpFile->uri);

    $this->maxFidBefore = (int) \Drupal::entityQueryAggregate('file')
      ->accessCheck(FALSE)
      ->aggregate('fid', 'max')
      ->execute()[0]['fid_max'];

    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');
    // Upload with replace to guarantee there's something there.
    $edit = [
      'file_test_replace' => FileExists::Replace->name,
      'files[file_test_upload][]' => $file_system->realpath($this->image->getFileUri()),
    ];
    $this->drupalGet('file-test/save_upload_from_form_test');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains("You WIN!");

    // Check that the correct hooks were called then clean out the hook
    // counters.
    $this->assertFileHooksCalled(['validate', 'insert']);
    file_test_reset();
  }

  /**
   * Tests the _file_save_upload_from_form() function.
   */
  public function testNormal(): void {
    $max_fid_after = (int) \Drupal::entityQueryAggregate('file')
      ->accessCheck(FALSE)
      ->aggregate('fid', 'max')
      ->execute()[0]['fid_max'];
    // Verify that a new file was created.
    $this->assertGreaterThan($this->maxFidBefore, $max_fid_after);
    $file1 = File::load($max_fid_after);
    $this->assertInstanceOf(File::class, $file1);
    // MIME type of the uploaded image may be either image/jpeg or image/png.
    $this->assertEquals('image', substr($file1->getMimeType(), 0, 5), 'A MIME type was set.');

    // Reset the hook counters to get rid of the 'load' we just called.
    file_test_reset();

    // Upload a second file.
    $image2 = current($this->drupalGetTestFiles('image'));
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');
    $edit = ['files[file_test_upload][]' => $file_system->realpath($image2->uri)];
    $this->drupalGet('file-test/save_upload_from_form_test');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains("You WIN!");
    $max_fid_after = (int) \Drupal::entityQueryAggregate('file')
      ->accessCheck(FALSE)
      ->aggregate('fid', 'max')
      ->execute()[0]['fid_max'];

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['validate', 'insert']);

    $file2 = File::load($max_fid_after);
    $this->assertInstanceOf(File::class, $file2);
    // MIME type of the uploaded image may be either image/jpeg or image/png.
    $this->assertEquals('image', substr($file2->getMimeType(), 0, 5), 'A MIME type was set.');

    // Load both files using File::loadMultiple().
    $files = File::loadMultiple([$file1->id(), $file2->id()]);
    $this->assertTrue(isset($files[$file1->id()]), 'File was loaded successfully');
    $this->assertTrue(isset($files[$file2->id()]), 'File was loaded successfully');

    // Upload a third file to a subdirectory.
    $image3 = current($this->drupalGetTestFiles('image'));
    $image3_realpath = $file_system->realpath($image3->uri);
    $dir = $this->randomMachineName();
    $edit = [
      'files[file_test_upload][]' => $image3_realpath,
      'file_subdir' => $dir,
    ];
    $this->drupalGet('file-test/save_upload_from_form_test');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains("You WIN!");
    $this->assertFileExists('temporary://' . $dir . '/' . trim(\Drupal::service('file_system')->basename($image3_realpath)));
  }

  /**
   * Tests extension handling.
   */
  public function testHandleExtension(): void {
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');
    // The file being tested is a .gif which is in the default safe list
    // of extensions to allow when the extension validator isn't used. This is
    // implicitly tested at the testNormal() test. Here we tell
    // _file_save_upload_from_form() to only allow ".foo".
    $extensions = 'foo';
    $edit = [
      'file_test_replace' => FileExists::Replace->name,
      'files[file_test_upload][]' => $file_system->realpath($this->image->getFileUri()),
      'extensions' => $extensions,
    ];

    $this->drupalGet('file-test/save_upload_from_form_test');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('Only files with the following extensions are allowed: <em class="placeholder">' . $extensions . '</em>');
    $this->assertSession()->pageTextContains("Epic upload FAIL!");

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['validate']);

    // Reset the hook counters.
    file_test_reset();

    $extensions = 'foo ' . $this->imageExtension;
    // Now tell _file_save_upload_from_form() to allow the extension of our test image.
    $edit = [
      'file_test_replace' => FileExists::Replace->name,
      'files[file_test_upload][]' => $file_system->realpath($this->image->getFileUri()),
      'extensions' => $extensions,
    ];

    $this->drupalGet('file-test/save_upload_from_form_test');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('Only files with the following extensions are allowed:');
    $this->assertSession()->pageTextContains("You WIN!");

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['validate', 'load', 'update']);

    // Reset the hook counters.
    file_test_reset();

    // Now tell _file_save_upload_from_form() to allow any extension.
    $edit = [
      'file_test_replace' => FileExists::Replace->name,
      'files[file_test_upload][]' => $file_system->realpath($this->image->getFileUri()),
      'allow_all_extensions' => 'empty_array',
    ];
    $this->drupalGet('file-test/save_upload_from_form_test');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('Only files with the following extensions are allowed:');
    $this->assertSession()->pageTextContains("You WIN!");

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['validate', 'load', 'update']);
  }

  /**
   * Tests dangerous file handling.
   */
  public function testHandleDangerousFile(): void {
    $config = $this->config('system.file');
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');
    // Allow the .php extension and make sure it gets renamed to .txt for
    // safety. Also check to make sure its MIME type was changed.
    $edit = [
      'file_test_replace' => FileExists::Replace->name,
      'files[file_test_upload][]' => $file_system->realpath($this->phpFile->uri),
      'is_image_file' => FALSE,
      'extensions' => 'php txt',
    ];

    $this->drupalGet('file-test/save_upload_from_form_test');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('For security reasons, your upload has been renamed to <em class="placeholder">' . $this->phpFile->filename . '_.txt' . '</em>');
    $this->assertSession()->pageTextContains('File MIME type is text/plain.');
    $this->assertSession()->pageTextContains("You WIN!");

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['validate', 'insert']);

    // Ensure dangerous files are not renamed when insecure uploads is TRUE.
    // Turn on insecure uploads.
    $config->set('allow_insecure_uploads', 1)->save();
    // Reset the hook counters.
    file_test_reset();

    $this->drupalGet('file-test/save_upload_from_form_test');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('For security reasons, your upload has been renamed');
    $this->assertSession()->pageTextContains("File name is {$this->phpFile->filename}");
    $this->assertSession()->pageTextContains("You WIN!");

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['validate', 'insert']);

    // Turn off insecure uploads.
    $config->set('allow_insecure_uploads', 0)->save();

    // Reset the hook counters.
    file_test_reset();

    $edit = [
      'file_test_replace' => FileExists::Replace->name,
      'files[file_test_upload]' => \Drupal::service('file_system')->realpath($this->phpFile->uri),
      'is_image_file' => FALSE,
      'extensions' => 'php',
    ];

    $this->drupalGet('file-test/upload');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('For security reasons, your upload has been rejected.');
    $this->assertSession()->pageTextContains('Epic upload FAIL!');

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['validate']);
  }

  /**
   * Tests file munge handling.
   */
  public function testHandleFileMunge(): void {
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');
    // Ensure insecure uploads are disabled for this test.
    $this->config('system.file')->set('allow_insecure_uploads', 0)->save();
    $original_uri = $this->image->getFileUri();
    /** @var \Drupal\file\FileRepositoryInterface $file_repository */
    $file_repository = \Drupal::service('file.repository');
    $this->image = $file_repository->move($this->image, $original_uri . '.foo.' . $this->imageExtension);

    // Reset the hook counters to get rid of the 'move' we just called.
    file_test_reset();

    $extensions = $this->imageExtension;
    $edit = [
      'files[file_test_upload][]' => $file_system->realpath($this->image->getFileUri()),
      'extensions' => $extensions,
    ];

    $munged_filename = $this->image->getFilename();
    $munged_filename = substr($munged_filename, 0, strrpos($munged_filename, '.'));
    $munged_filename .= '_.' . $this->imageExtension;

    $this->drupalGet('file-test/save_upload_from_form_test');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('For security reasons, your upload has been renamed');
    $this->assertSession()->pageTextContains("File name is {$munged_filename}");
    $this->assertSession()->pageTextContains("You WIN!");

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['validate', 'insert']);

    // Test with uppercase extensions.
    $this->image = $file_repository->move($this->image, $original_uri . '.foo2.' . $this->imageExtension);
    // Reset the hook counters.
    file_test_reset();
    $extensions = $this->imageExtension;
    $edit = [
      'files[file_test_upload][]' => $file_system->realpath($this->image->getFileUri()),
      'extensions' => mb_strtoupper($extensions),
    ];

    $munged_filename = $this->image->getFilename();
    $munged_filename = substr($munged_filename, 0, strrpos($munged_filename, '.'));
    $munged_filename .= '_.' . $this->imageExtension;

    $this->drupalGet('file-test/save_upload_from_form_test');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('For security reasons, your upload has been renamed');
    $this->assertSession()->pageTextContains("File name is {$munged_filename}");
    $this->assertSession()->pageTextContains("You WIN!");

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['validate', 'insert']);

    // Ensure we don't munge files if we're allowing any extension.
    // Reset the hook counters.
    file_test_reset();

    // Ensure we don't munge files if we're allowing any extension.
    $edit = [
      'files[file_test_upload][]' => $file_system->realpath($this->image->getFileUri()),
      'allow_all_extensions' => 'empty_array',
    ];

    $this->drupalGet('file-test/save_upload_from_form_test');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('For security reasons, your upload has been renamed');
    $this->assertSession()->pageTextContains("File name is {$this->image->getFilename()}");
    $this->assertSession()->pageTextContains("You WIN!");

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['validate', 'insert']);

    // Ensure that setting $validators['file_validate_extensions'] = ['']
    // rejects all files.
    // Reset the hook counters.
    file_test_reset();

    $edit = [
      'files[file_test_upload][]' => $file_system->realpath($this->image->getFileUri()),
      'allow_all_extensions' => 'empty_string',
    ];

    $this->drupalGet('file-test/save_upload_from_form_test');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('For security reasons, your upload has been renamed');
    $this->assertSession()->pageTextContains("Epic upload FAIL!");

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['validate']);
  }

  /**
   * Tests renaming when uploading over a file that already exists.
   */
  public function testExistingRename(): void {
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');
    $edit = [
      'file_test_replace' => FileExists::Rename->name,
      'files[file_test_upload][]' => $file_system->realpath($this->image->getFileUri()),
    ];
    $this->drupalGet('file-test/save_upload_from_form_test');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains("You WIN!");

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['validate', 'insert']);
  }

  /**
   * Tests replacement when uploading over a file that already exists.
   */
  public function testExistingReplace(): void {
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');
    $edit = [
      'file_test_replace' => FileExists::Replace->name,
      'files[file_test_upload][]' => $file_system->realpath($this->image->getFileUri()),
    ];
    $this->drupalGet('file-test/save_upload_from_form_test');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains("You WIN!");

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['validate', 'load', 'update']);
  }

  /**
   * Tests for failure when uploading over a file that already exists.
   */
  public function testExistingError(): void {
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');
    $edit = [
      'file_test_replace' => FileExists::Error->name,
      'files[file_test_upload][]' => $file_system->realpath($this->image->getFileUri()),
    ];
    $this->drupalGet('file-test/save_upload_from_form_test');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains("Epic upload FAIL!");

    // Check that the no hooks were called while failing.
    $this->assertFileHooksCalled([]);
  }

  /**
   * Tests for no failures when not uploading a file.
   */
  public function testNoUpload(): void {
    $this->drupalGet('file-test/save_upload_from_form_test');
    $this->submitForm([], 'Submit');
    $this->assertSession()->pageTextNotContains("Epic upload FAIL!");
  }

  /**
   * Tests for log entry on failing destination.
   */
  public function testDrupalMovingUploadedFileError(): void {
    // Create a directory and make it not writable.
    $test_directory = 'test_drupal_move_uploaded_file_fail';
    \Drupal::service('file_system')->mkdir('temporary://' . $test_directory, 0000);
    $this->assertDirectoryExists('temporary://' . $test_directory);

    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');
    $edit = [
      'file_subdir' => $test_directory,
      'files[file_test_upload][]' => $file_system->realpath($this->image->getFileUri()),
    ];

    \Drupal::state()->set('file_test.disable_error_collection', TRUE);
    $this->drupalGet('file-test/save_upload_from_form_test');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('File upload error. Could not move uploaded file.');
    $this->assertSession()->pageTextContains("Epic upload FAIL!");

    // Uploading failed. Now check the log.
    $this->drupalGet('admin/reports/dblog');
    $this->assertSession()->statusCodeEquals(200);
    // The full log message is in the title attribute of the link, so we cannot
    // use ::pageTextContains() here.
    $destination = 'temporary://' . $test_directory . '/' . $this->image->getFilename();
    $this->assertSession()->responseContains("Upload error. Could not move uploaded file {$this->image->getFilename()} to destination {$destination}.");
  }

  /**
   * Tests that form validation does not change error messages.
   */
  public function testErrorMessagesAreNotChanged(): void {
    $error = 'An error message set before _file_save_upload_from_form()';

    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');
    $edit = [
      'files[file_test_upload][]' => $file_system->realpath($this->image->getFileUri()),
      'error_message' => $error,
    ];
    $this->drupalGet('file-test/save_upload_from_form_test');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains("You WIN!");

    // Ensure the expected error message is present and the counts before and
    // after calling _file_save_upload_from_form() are correct.
    $this->assertSession()->pageTextContains($error);
    $this->assertSession()->pageTextContains('Number of error messages before _file_save_upload_from_form(): 1');
    $this->assertSession()->pageTextContains('Number of error messages after _file_save_upload_from_form(): 1');

    // Test that error messages are preserved when an error occurs.
    $edit = [
      'files[file_test_upload][]' => $file_system->realpath($this->image->getFileUri()),
      'error_message' => $error,
      'extensions' => 'foo',
    ];
    $this->drupalGet('file-test/save_upload_from_form_test');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains("Epic upload FAIL!");

    // Ensure the expected error message is present and the counts before and
    // after calling _file_save_upload_from_form() are correct.
    $this->assertSession()->pageTextContains($error);
    $this->assertSession()->pageTextContains('Number of error messages before _file_save_upload_from_form(): 1');
    $this->assertSession()->pageTextContains('Number of error messages after _file_save_upload_from_form(): 1');

    // Test a successful upload with no messages.
    $edit = [
      'files[file_test_upload][]' => $file_system->realpath($this->image->getFileUri()),
    ];
    $this->drupalGet('file-test/save_upload_from_form_test');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains("You WIN!");

    // Ensure the error message is not present and the counts before and after
    // calling _file_save_upload_from_form() are correct.
    $this->assertSession()->pageTextNotContains($error);
    $this->assertSession()->pageTextContains('Number of error messages before _file_save_upload_from_form(): 0');
    $this->assertSession()->pageTextContains('Number of error messages after _file_save_upload_from_form(): 0');
  }

  /**
   * Tests that multiple validation errors are combined in one message.
   */
  public function testCombinedErrorMessages(): void {
    $text_file = current($this->drupalGetTestFiles('text'));
    $this->assertFileExists($text_file->uri);

    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');

    // Can't use submitForm() for set nonexistent fields.
    $this->drupalGet('file-test/save_upload_from_form_test');
    $client = $this->getSession()->getDriver()->getClient();
    $submit_xpath = $this->assertSession()->buttonExists('Submit')->getXpath();
    $form = $client->getCrawler()->filterXPath($submit_xpath)->form();
    $edit = [
      'is_image_file' => TRUE,
      'extensions' => 'jpeg',
    ];
    $edit += $form->getPhpValues();
    $files['files']['file_test_upload'][0] = $file_system->realpath($this->phpFile->uri);
    $files['files']['file_test_upload'][1] = $file_system->realpath($text_file->uri);
    $client->request($form->getMethod(), $form->getUri(), $edit, $files);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains("Epic upload FAIL!");

    // Search for combined error message followed by a formatted list of messages.
    $this->assertSession()->responseContains('One or more files could not be uploaded.<ul>');
  }

  /**
   * Tests highlighting of file upload field when it has an error.
   */
  public function testUploadFieldIsHighlighted(): void {
    $this->assertCount(0, $this->cssSelect('input[name="files[file_test_upload][]"].error'), 'Successful file upload has no error.');

    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');
    $edit = [
      'files[file_test_upload][]' => $file_system->realpath($this->image->getFileUri()),
      'extensions' => 'foo',
    ];
    $this->drupalGet('file-test/save_upload_from_form_test');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains("Epic upload FAIL!");
    $this->assertCount(1, $this->cssSelect('input[name="files[file_test_upload][]"].error'), 'File upload field has error.');
  }

}

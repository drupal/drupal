<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\File\FileExists;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\Tests\TestFileCreationTrait;

// cSpell:ignore TÉXT Pácê

/**
 * Tests the file_save_upload() function.
 *
 * @group file
 * @group #slow
 */
class SaveUploadTest extends FileManagedTestBase {

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
   * The user used by the test.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->account = $this->drupalCreateUser(['access site reports']);
    $this->drupalLogin($this->account);

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

    // Upload with replace to guarantee there's something there.
    $edit = [
      'file_test_replace' => FileExists::Replace->name,
      'files[file_test_upload]' => \Drupal::service('file_system')->realpath($this->image->getFileUri()),
    ];
    $this->drupalGet('file-test/upload');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    // Check that the success message is present.
    $this->assertSession()->pageTextContains("You WIN!");

    // Check that the correct hooks were called then clean out the hook
    // counters.
    $this->assertFileHooksCalled(['validate', 'insert']);
    file_test_reset();
  }

  /**
   * Tests the file_save_upload() function.
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
    $edit = ['files[file_test_upload]' => \Drupal::service('file_system')->realpath($image2->uri)];
    $this->drupalGet('file-test/upload');
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
    $image3_realpath = \Drupal::service('file_system')->realpath($image3->uri);
    $dir = $this->randomMachineName();
    $edit = [
      'files[file_test_upload]' => $image3_realpath,
      'file_subdir' => $dir,
    ];
    $this->drupalGet('file-test/upload');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains("You WIN!");
    $this->assertFileExists('temporary://' . $dir . '/' . trim(\Drupal::service('file_system')->basename($image3_realpath)));
  }

  /**
   * Tests uploading a duplicate file.
   */
  public function testDuplicate(): void {
    // It should not be possible to create two managed files with the same URI.
    $image1 = current($this->drupalGetTestFiles('image'));
    $edit = ['files[file_test_upload]' => \Drupal::service('file_system')->realpath($image1->uri)];
    $this->drupalGet('file-test/upload');
    $this->submitForm($edit, 'Submit');
    $max_fid_after = (int) \Drupal::entityQueryAggregate('file')
      ->accessCheck(FALSE)
      ->aggregate('fid', 'max')
      ->execute()[0]['fid_max'];
    $file1 = File::load($max_fid_after);

    // Simulate a race condition where two files are uploaded at almost the same
    // time, by removing the first uploaded file from disk (leaving the entry in
    // the file_managed table) before trying to upload another file with the
    // same name.
    unlink(\Drupal::service('file_system')->realpath($file1->getFileUri()));

    $image2 = $image1;
    $edit = ['files[file_test_upload]' => \Drupal::service('file_system')->realpath($image2->uri)];
    $this->drupalGet('file-test/upload');
    $this->submitForm($edit, 'Submit');
    // Received a 200 response for posted test file.
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains("The file {$file1->getFileUri()} already exists. Enter a unique file URI.");
    $max_fid_before_duplicate = $max_fid_after;
    $max_fid_after = (int) \Drupal::entityQueryAggregate('file')
      ->accessCheck(FALSE)
      ->aggregate('fid', 'max')
      ->execute()[0]['fid_max'];
    $this->assertEquals($max_fid_before_duplicate, $max_fid_after, 'A new managed file was not created.');
  }

  /**
   * Tests extension handling.
   */
  public function testHandleExtension(): void {
    // The file being tested is a .gif which is in the default safe list
    // of extensions to allow when the extension validator isn't used. This is
    // implicitly tested at the testNormal() test. Here we tell
    // file_save_upload() to only allow ".foo".
    $extensions = 'foo';
    $edit = [
      'file_test_replace' => FileExists::Replace->name,
      'files[file_test_upload]' => \Drupal::service('file_system')->realpath($this->image->getFileUri()),
      'extensions' => $extensions,
    ];

    $this->drupalGet('file-test/upload');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('Only files with the following extensions are allowed: <em class="placeholder">' . $extensions . '</em>');
    $this->assertSession()->pageTextContains("Epic upload FAIL!");

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['validate']);

    // Reset the hook counters.
    file_test_reset();

    $extensions = 'foo ' . $this->imageExtension;
    // Now tell file_save_upload() to allow the extension of our test image.
    $edit = [
      'file_test_replace' => FileExists::Replace->name,
      'files[file_test_upload]' => \Drupal::service('file_system')->realpath($this->image->getFileUri()),
      'extensions' => $extensions,
    ];

    $this->drupalGet('file-test/upload');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains("Only files with the following extensions are allowed:");
    $this->assertSession()->pageTextContains("You WIN!");

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['validate', 'load', 'update']);

    // Reset the hook counters.
    file_test_reset();

    // Now tell file_save_upload() to allow any extension.
    $edit = [
      'file_test_replace' => FileExists::Replace->name,
      'files[file_test_upload]' => \Drupal::service('file_system')->realpath($this->image->getFileUri()),
      'allow_all_extensions' => 'empty_array',
    ];
    $this->drupalGet('file-test/upload');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains("Only files with the following extensions are allowed:");
    $this->assertSession()->pageTextContains("You WIN!");

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['validate', 'load', 'update']);

    // Reset the hook counters.
    file_test_reset();

    // Now tell file_save_upload() to allow any extension and try and upload a
    // malicious file.
    $edit = [
      'file_test_replace' => FileExists::Replace->name,
      'files[file_test_upload]' => \Drupal::service('file_system')->realpath($this->phpFile->uri),
      'allow_all_extensions' => 'empty_array',
      'is_image_file' => FALSE,
    ];
    $this->drupalGet('file-test/upload');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('For security reasons, your upload has been renamed to <em class="placeholder">' . $this->phpFile->filename . '_.txt' . '</em>');
    $this->assertSession()->pageTextContains('File name is php-2.php_.txt.');
    $this->assertSession()->pageTextContains('File MIME type is text/plain.');
    $this->assertSession()->pageTextContains("You WIN!");
    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['validate', 'insert']);
  }

  /**
   * Tests dangerous file handling.
   */
  public function testHandleDangerousFile(): void {
    $config = $this->config('system.file');
    // Allow the .php extension and make sure it gets munged and given a .txt
    // extension for safety. Also check to make sure its MIME type was changed.
    $edit = [
      'file_test_replace' => FileExists::Replace->name,
      'files[file_test_upload]' => \Drupal::service('file_system')->realpath($this->phpFile->uri),
      'is_image_file' => FALSE,
      'extensions' => 'php txt',
    ];

    $this->drupalGet('file-test/upload');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('For security reasons, your upload has been renamed to <em class="placeholder">' . $this->phpFile->filename . '_.txt' . '</em>');
    $this->assertSession()->pageTextContains('File name is php-2.php_.txt.');
    $this->assertSession()->pageTextContains('File MIME type is text/plain.');
    $this->assertSession()->pageTextContains("You WIN!");

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['validate', 'insert']);

    // Ensure dangerous files are not renamed when insecure uploads is TRUE.
    // Turn on insecure uploads.
    $config->set('allow_insecure_uploads', 1)->save();
    // Reset the hook counters.
    file_test_reset();

    $this->drupalGet('file-test/upload');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('For security reasons, your upload has been renamed');
    $this->assertSession()->pageTextContains('File name is php-2.php.');
    $this->assertSession()->pageTextContains("You WIN!");

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['validate', 'insert']);

    // Reset the hook counters.
    file_test_reset();

    // Even with insecure uploads allowed, the .php file should not be uploaded
    // if it is not explicitly included in the list of allowed extensions.
    $edit['extensions'] = 'foo';
    $this->drupalGet('file-test/upload');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('Only files with the following extensions are allowed: <em class="placeholder">' . $edit['extensions'] . '</em>');
    $this->assertSession()->pageTextContains("Epic upload FAIL!");

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['validate']);

    // Reset the hook counters.
    file_test_reset();

    // Turn off insecure uploads, then try the same thing as above (ensure that
    // the .php file is still rejected since it's not in the list of allowed
    // extensions).
    $config->set('allow_insecure_uploads', 0)->save();
    $this->drupalGet('file-test/upload');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('Only files with the following extensions are allowed: <em class="placeholder">' . $edit['extensions'] . '</em>');
    $this->assertSession()->pageTextContains("Epic upload FAIL!");

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['validate']);

    // Reset the hook counters.
    file_test_reset();

    \Drupal::service('cache.config')->deleteAll();

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
   * Test dangerous file handling.
   */
  public function testHandleDotFile(): void {
    $dot_file = $this->siteDirectory . '/.test';
    file_put_contents($dot_file, 'This is a test');
    $config = $this->config('system.file');
    $edit = [
      'file_test_replace' => FileExists::Replace->name,
      'files[file_test_upload]' => \Drupal::service('file_system')->realpath($dot_file),
      'is_image_file' => FALSE,
    ];
    $this->drupalGet('file-test/upload');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('The specified file .test could not be uploaded');
    $this->assertSession()->pageTextContains('Epic upload FAIL!');

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['validate']);

    $edit = [
      'file_test_replace' => FileExists::Rename->name,
      'files[file_test_upload]' => \Drupal::service('file_system')->realpath($dot_file),
      'is_image_file' => FALSE,
      'allow_all_extensions' => 'empty_array',
    ];
    $this->drupalGet('file-test/upload');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('For security reasons, your upload has been renamed to test.');
    $this->assertSession()->pageTextContains('File name is test.');
    $this->assertSession()->pageTextContains('You WIN!');

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['validate', 'insert']);

    // Reset the hook counters.
    file_test_reset();

    // Turn off insecure uploads, then try the same thing as above to ensure dot
    // files are renamed regardless.
    $config->set('allow_insecure_uploads', 0)->save();
    $this->drupalGet('file-test/upload');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('For security reasons, your upload has been renamed to test_0.');
    $this->assertSession()->pageTextContains('File name is test_0.');
    $this->assertSession()->pageTextContains('You WIN!');

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['validate', 'insert']);

    // Reset the hook counters.
    file_test_reset();
  }

  /**
   * Tests file munge handling.
   */
  public function testHandleFileMunge(): void {
    // Ensure insecure uploads are disabled for this test.
    $this->config('system.file')->set('allow_insecure_uploads', 0)->save();
    $original_image_uri = $this->image->getFileUri();
    /** @var \Drupal\file\FileRepositoryInterface $file_repository */
    $file_repository = \Drupal::service('file.repository');
    $this->image = $file_repository->move($this->image, $original_image_uri . '.foo.' . $this->imageExtension);

    // Reset the hook counters to get rid of the 'move' we just called.
    file_test_reset();

    $extensions = $this->imageExtension;
    $edit = [
      'files[file_test_upload]' => \Drupal::service('file_system')->realpath($this->image->getFileUri()),
      'extensions' => $extensions,
    ];

    $munged_filename = $this->image->getFilename();
    $munged_filename = substr($munged_filename, 0, strrpos($munged_filename, '.'));
    $munged_filename .= '_.' . $this->imageExtension;

    $this->drupalGet('file-test/upload');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('For security reasons, your upload has been renamed');
    $this->assertSession()->pageTextContains("File name is $munged_filename");
    $this->assertSession()->pageTextContains("You WIN!");

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['validate', 'insert']);

    // Reset the hook counters.
    file_test_reset();

    // Ensure we don't munge the .foo extension if it is in the list of allowed
    // extensions.
    $extensions = 'foo ' . $this->imageExtension;
    $edit = [
      'files[file_test_upload]' => \Drupal::service('file_system')->realpath($this->image->getFileUri()),
      'extensions' => $extensions,
    ];

    $this->drupalGet('file-test/upload');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('For security reasons, your upload has been renamed');
    $this->assertSession()->pageTextContains("File name is {$this->image->getFilename()}");
    $this->assertSession()->pageTextContains("You WIN!");

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['validate', 'insert']);

    // Ensure we don't munge files if we're allowing any extension.
    $this->image = $file_repository->move($this->image, $original_image_uri . '.foo.txt.' . $this->imageExtension);
    // Reset the hook counters.
    file_test_reset();

    $edit = [
      'files[file_test_upload]' => \Drupal::service('file_system')->realpath($this->image->getFileUri()),
      'allow_all_extensions' => 'empty_array',
    ];

    $this->drupalGet('file-test/upload');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('For security reasons, your upload has been renamed');
    $this->assertSession()->pageTextContains("File name is {$this->image->getFilename()}");
    $this->assertSession()->pageTextContains("You WIN!");

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['validate', 'insert']);

    // Test that a dangerous extension such as .php is munged even if it is in
    // the list of allowed extensions.
    $this->image = $file_repository->move($this->image, $original_image_uri . '.php.' . $this->imageExtension);
    // Reset the hook counters.
    file_test_reset();

    $extensions = 'php ' . $this->imageExtension;
    $edit = [
      'files[file_test_upload]' => \Drupal::service('file_system')->realpath($this->image->getFileUri()),
      'extensions' => $extensions,
    ];

    $this->drupalGet('file-test/upload');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('For security reasons, your upload has been renamed');
    $this->assertSession()->pageTextContains("File name is image-test.png_.php_.png");
    $this->assertSession()->pageTextContains("You WIN!");

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['validate', 'insert']);

    // Reset the hook counters.
    file_test_reset();

    // Dangerous extensions are munged even when all extensions are allowed.
    $edit = [
      'files[file_test_upload]' => \Drupal::service('file_system')->realpath($this->image->getFileUri()),
      'allow_all_extensions' => 'empty_array',
    ];

    $this->drupalGet('file-test/upload');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('For security reasons, your upload has been renamed');
    $this->assertSession()->pageTextContains("File name is image-test.png_.php__0.png");
    $this->assertSession()->pageTextContains("You WIN!");

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['validate', 'insert']);

    // Dangerous extensions are munged if is renamed to end in .txt.
    $this->image = $file_repository->move($this->image, $original_image_uri . '.cgi.' . $this->imageExtension . '.txt');
    // Reset the hook counters.
    file_test_reset();

    // Dangerous extensions are munged even when all extensions are allowed.
    $edit = [
      'files[file_test_upload]' => \Drupal::service('file_system')->realpath($this->image->getFileUri()),
      'allow_all_extensions' => 'empty_array',
    ];

    $this->drupalGet('file-test/upload');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('For security reasons, your upload has been renamed');
    $this->assertSession()->pageTextContains("File name is image-test.png_.cgi_.png_.txt");
    $this->assertSession()->pageTextContains("You WIN!");

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['validate', 'insert']);

    // Reset the hook counters.
    file_test_reset();

    // Ensure that setting $validators['FileExtension'] = ['extensions' = '']
    // rejects all files without munging or renaming.
    $edit = [
      'files[file_test_upload][]' => \Drupal::service('file_system')->realpath($this->image->getFileUri()),
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
    $edit = [
      'file_test_replace' => FileExists::Rename->name,
      'files[file_test_upload]' => \Drupal::service('file_system')->realpath($this->image->getFileUri()),
    ];
    $this->drupalGet('file-test/upload');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains("You WIN!");
    $this->assertSession()->pageTextContains('File name is image-test_0.png.');

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['validate', 'insert']);
  }

  /**
   * Tests replacement when uploading over a file that already exists.
   */
  public function testExistingReplace(): void {
    $edit = [
      'file_test_replace' => FileExists::Replace->name,
      'files[file_test_upload]' => \Drupal::service('file_system')->realpath($this->image->getFileUri()),
    ];
    $this->drupalGet('file-test/upload');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains("You WIN!");
    $this->assertSession()->pageTextContains('File name is image-test.png.');

    // Check that the correct hooks were called.
    $this->assertFileHooksCalled(['validate', 'load', 'update']);
  }

  /**
   * Tests for failure when uploading over a file that already exists.
   */
  public function testExistingError(): void {
    $edit = [
      'file_test_replace' => FileExists::Error->name,
      'files[file_test_upload]' => \Drupal::service('file_system')->realpath($this->image->getFileUri()),
    ];
    $this->drupalGet('file-test/upload');
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
    $this->drupalGet('file-test/upload');
    $this->submitForm([], 'Submit');
    $this->assertSession()->pageTextNotContains("Epic upload FAIL!");
  }

  /**
   * Tests for log entry on failing destination.
   */
  public function testDrupalMovingUploadedFileError(): void {
    // Create a directory and make it not writable.
    $test_directory = 'test_drupal_move_uploaded_file_fail';
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');
    $file_system->mkdir('temporary://' . $test_directory, 0000);
    $this->assertDirectoryExists('temporary://' . $test_directory);

    $edit = [
      'file_subdir' => $test_directory,
      'files[file_test_upload]' => $file_system->realpath($this->image->getFileUri()),
    ];

    \Drupal::state()->set('file_test.disable_error_collection', TRUE);
    $this->drupalGet('file-test/upload');
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
   * Tests that filenames containing invalid UTF-8 are rejected.
   */
  public function testInvalidUtf8FilenameUpload(): void {
    $this->drupalGet('file-test/upload');

    // Filename containing invalid UTF-8.
    $filename = "x\xc0xx.gif";

    $page = $this->getSession()->getPage();
    $data = [
      'multipart' => [
        [
          'name'     => 'file_test_replace',
          'contents' => FileExists::Rename->name,
        ],
        [
          'name' => 'form_id',
          'contents' => '_file_test_form',
        ],
        [
          'name' => 'form_build_id',
          'contents' => $page->find('hidden_field_selector', ['hidden_field', 'form_build_id'])->getAttribute('value'),
        ],
        [
          'name' => 'form_token',
          'contents' => $page->find('hidden_field_selector', ['hidden_field', 'form_token'])->getAttribute('value'),
        ],
        [
          'name' => 'op',
          'contents' => 'Submit',
        ],
        [
          'name'     => 'files[file_test_upload]',
          'contents' => 'Test content',
          'filename' => $filename,
        ],
      ],
      'cookies' => $this->getSessionCookies(),
      'http_errors' => FALSE,
    ];

    $this->assertFileDoesNotExist('temporary://' . $filename);
    // Use Guzzle's HTTP client directly so we can POST files without having to
    // write them to disk. Not all filesystem support writing files with invalid
    // UTF-8 filenames.
    $response = $this->getHttpClient()->request('POST', Url::fromUri('base:file-test/upload')->setAbsolute()->toString(), $data);

    $content = (string) $response->getBody();
    $this->htmlOutput($content);
    $error_text = new FormattableMarkup('The file %filename could not be uploaded because the name is invalid.', ['%filename' => $filename]);
    $this->assertStringContainsString((string) $error_text, $content);
    $this->assertStringContainsString('Epic upload FAIL!', $content);
    $this->assertFileDoesNotExist('temporary://' . $filename);
  }

  /**
   * Tests the file_save_upload() function when the field is required.
   */
  public function testRequired(): void {
    // Reset the hook counters to get rid of the 'load' we just called.
    file_test_reset();

    // Confirm the field is required.
    $this->drupalGet('file-test/upload_required');
    $this->submitForm([], 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('field is required');

    // Confirm that uploading another file works.
    $image = current($this->drupalGetTestFiles('image'));
    $edit = ['files[file_test_upload]' => \Drupal::service('file_system')->realpath($image->uri)];
    $this->drupalGet('file-test/upload_required');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('You WIN!');
  }

  /**
   * Tests filename sanitization.
   */
  public function testSanitization(): void {
    $file = $this->generateFile('TÉXT-œ', 64, 5, 'text');

    $this->drupalGet('file-test/upload');
    // Upload a file with a name with uppercase and unicode characters.
    $edit = [
      'files[file_test_upload]' => \Drupal::service('file_system')->realpath($file),
      'extensions' => 'txt',
      'is_image_file' => FALSE,
    ];
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    // Test that the file name has not been sanitized.
    $this->assertSession()->responseContains('File name is TÉXT-œ.txt.');

    // Enable sanitization via the UI.
    $admin = $this->createUser(['administer site configuration']);
    $this->drupalLogin($admin);

    // For now, just transliterate, with no other transformations.
    $options = [
      'filename_sanitization[transliterate]' => TRUE,
      'filename_sanitization[replace_whitespace]' => FALSE,
      'filename_sanitization[replace_non_alphanumeric]' => FALSE,
      'filename_sanitization[deduplicate_separators]' => FALSE,
      'filename_sanitization[lowercase]' => FALSE,
      'filename_sanitization[replacement_character]' => '-',
    ];
    $this->drupalGet('admin/config/media/file-system');
    $this->submitForm($options, 'Save configuration');

    $this->drupalLogin($this->account);

    // Upload a file with a name with uppercase and unicode characters.
    $this->drupalGet('file-test/upload');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    // Test that the file name has been transliterated.
    $this->assertSession()->responseContains('File name is TEXT-oe.txt.');
    // Make sure we got a message about the rename.
    $message = 'Your upload has been renamed to <em class="placeholder">TEXT-oe.txt</em>';
    $this->assertSession()->responseContains($message);

    // Generate another file with a name with All The Things(tm) we care about.
    $file = $this->generateFile('S  Pácê--táb#	#--🙈', 64, 5, 'text');
    $edit = [
      'files[file_test_upload]' => \Drupal::service('file_system')->realpath($file),
      'extensions' => 'txt',
      'is_image_file' => FALSE,
    ];
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    // Test that the file name has only been transliterated.
    $this->assertSession()->responseContains('File name is S  Pace--tab#	#---.txt.');

    // Leave transliteration on and enable whitespace replacement.
    $this->drupalLogin($admin);
    $options['filename_sanitization[replace_whitespace]'] = TRUE;
    $this->drupalGet('admin/config/media/file-system');
    $this->submitForm($options, 'Save configuration');
    $this->drupalLogin($this->account);

    // Try again with the monster filename.
    $this->drupalGet('file-test/upload');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    // Test that the file name has been transliterated and whitespace replaced.
    $this->assertSession()->responseContains('File name is S--Pace--tab#-#---.txt.');

    // Leave transliteration and whitespace replacement on, replace non-alpha.
    $this->drupalLogin($admin);
    $options['filename_sanitization[replace_non_alphanumeric]'] = TRUE;
    $options['filename_sanitization[replacement_character]'] = '_';
    $this->drupalGet('admin/config/media/file-system');
    $this->submitForm($options, 'Save configuration');
    $this->drupalLogin($this->account);

    // Try again with the monster filename.
    $this->drupalGet('file-test/upload');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);

    // Test that the file name has been transliterated, whitespace replaced with
    // '_', and non-alphanumeric characters replaced with '_'.
    $this->assertSession()->responseContains('File name is S__Pace--tab___--_.txt.');

    // Now turn on the setting to remove duplicate separators.
    $this->drupalLogin($admin);
    $options['filename_sanitization[deduplicate_separators]'] = TRUE;
    $options['filename_sanitization[replacement_character]'] = '-';
    $this->drupalGet('admin/config/media/file-system');
    $this->submitForm($options, 'Save configuration');
    $this->drupalLogin($this->account);

    // Try again with the monster filename.
    $this->drupalGet('file-test/upload');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);

    // Test that the file name has been transliterated, whitespace replaced,
    // non-alphanumeric characters replaced, and duplicate separators removed.
    $this->assertSession()->responseContains('File name is S-Pace-tab.txt.');

    // Finally, check the lowercase setting.
    $this->drupalLogin($admin);
    $options['filename_sanitization[lowercase]'] = TRUE;
    $this->drupalGet('admin/config/media/file-system');
    $this->submitForm($options, 'Save configuration');
    $this->drupalLogin($this->account);

    // Generate another file since we're going to start getting collisions with
    // previously uploaded and renamed copies.
    $file = $this->generateFile('S  Pácê--táb#	#--🙈-2', 64, 5, 'text');
    $edit = [
      'files[file_test_upload]' => \Drupal::service('file_system')->realpath($file),
      'extensions' => 'txt',
      'is_image_file' => FALSE,
    ];
    $this->drupalGet('file-test/upload');
    $this->submitForm($edit, 'Submit');
    $this->assertSession()->statusCodeEquals(200);
    // Make sure all the sanitization options work as intended.
    $this->assertSession()->responseContains('File name is s-pace-tab-2.txt.');
    // Make sure we got a message about the rename.
    $message = 'Your upload has been renamed to <em class="placeholder">s-pace-tab-2.txt</em>';
    $this->assertSession()->responseContains($message);
  }

}

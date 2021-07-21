<?php

namespace Drupal\Tests\file\Functional;

use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\file_test_upload_event\Form\UploadLocationEventTestForm;

/**
 * Defines a test for FileUploadLocationEvent.
 *
 * This can't be a Kernel test because _file_save_upload_single() uses
 * is_uploaded_file() which can't be mocked because php://input is a read-only
 * stream wrapper.
 *
 * @group file
 */
class FileUploadLocationEventTest extends FileFieldTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['file_test_upload_event'];

  /**
   * Tests file upload location event.
   */
  public function testFileUploadLocationEvent() {
    $folder = $this->randomMachineName();
    $this->drupalGet('/file-test-upload-event');
    $temp_file = sprintf('temporary://%s.txt', $this->randomMachineName());
    file_put_contents($temp_file, '1');
    $this->submitForm([
      'folder' => $folder,
      'files[file]' => \Drupal::service('file_system')->realPath($temp_file),
    ], 'submit');
    $fids = \Drupal::state()->get(UploadLocationEventTestForm::UPLOAD_LOCATION_EVENT_TEST_FIDS);
    $this->assertNotNull($fids);
    $file = File::load(reset($fids));
    assert($file instanceof FileInterface);
    $this->assertEquals(sprintf('public://%s/%s', $folder, basename($temp_file)), $file->getFileUri());
  }

}

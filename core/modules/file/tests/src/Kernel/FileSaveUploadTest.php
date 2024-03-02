<?php

namespace Drupal\Tests\file\Kernel;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests file_save_upload().
 *
 * @group file
 * @group legacy
 */
class FileSaveUploadTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'file',
    'file_test',
    'file_validator_test',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    \file_put_contents('test.bbb', 'test');

    parent::setUp();
    $request = new Request();
    $request->files->set('files', [
      'file' => new UploadedFile(
        path: 'test.bbb',
        originalName: 'test.bbb',
        mimeType: 'text/plain',
        error: \UPLOAD_ERR_OK,
        test: TRUE
      ),
    ]);

    $requestStack = new RequestStack();
    $requestStack->push($request);

    $this->container->set('request_stack', $requestStack);
  }

  /**
   * Tests file_save_upload() with empty extensions.
   */
  public function testFileSaveUploadEmptyExtensions(): void {
    // Allow all extensions.
    $validators = ['file_validate_extensions' => ''];
    $this->expectDeprecation('\'file_validate_extensions\' is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Use the \'FileExtension\' constraint instead. See https://www.drupal.org/node/3363700');
    $files = file_save_upload('file', $validators);
    $this->assertCount(1, $files);
    $file = $files[0];
    // @todo work out why move_uploaded_file() is failing.
    $this->assertFalse($file);
    $messages = \Drupal::messenger()->messagesByType(MessengerInterface::TYPE_ERROR);
    $this->assertNotEmpty($messages);
    $this->assertEquals('File upload error. Could not move uploaded file.', $messages[0]);
  }

}

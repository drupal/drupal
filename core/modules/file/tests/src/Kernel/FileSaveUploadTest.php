<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Kernel;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\KernelTests\KernelTestBase;
use org\bovigo\vfs\vfsStream;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Tests file_save_upload().
 *
 * @group file
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
    parent::setUp();
    $filename = 'test.bbb';
    vfsStream::newFile($filename)
      ->at($this->vfsRoot)
      ->withContent('test');

    $request = new Request();
    $request->files->set('files', [
      'file' => new UploadedFile(
        path: vfsStream::url("root/$filename"),
        originalName: $filename,
        mimeType: 'text/plain',
        error: \UPLOAD_ERR_OK,
        test: TRUE
      ),
    ]);
    $request->setSession(new Session(new MockArraySessionStorage()));
    $requestStack = new RequestStack();
    $requestStack->push($request);

    $this->container->set('request_stack', $requestStack);
  }

  /**
   * Tests file_save_upload() with empty extensions.
   */
  public function testFileSaveUploadEmptyExtensions(): void {
    // Allow all extensions.
    $validators = ['FileExtension' => []];
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

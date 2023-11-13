<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Kernel\Upload;

use Drupal\KernelTests\KernelTestBase;
use org\bovigo\vfs\vfsStream;

/**
 * Tests the stream file uploader.
 *
 * @group file
 * @coversDefaultClass \Drupal\file\Upload\InputStreamFileWriter
 */
class StreamFileUploaderTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['file'];

  /**
   * @covers ::writeStreamToFile
   */
  public function testWriteStreamToFileSuccess(): void {
    vfsStream::newFile('foo.txt')
      ->at($this->vfsRoot)
      ->withContent('bar');

    $fileWriter = $this->container->get('file.input_stream_file_writer');

    $filename = $fileWriter->writeStreamToFile(vfsStream::url('root/foo.txt'));

    $this->assertStringStartsWith('temporary://', $filename);
    $this->assertStringEqualsFile($filename, 'bar');
  }

  /**
   * @covers ::writeStreamToFile
   */
  public function testWriteStreamToFileWithSmallerBytes(): void {
    $content = $this->randomString(2048);
    vfsStream::newFile('foo.txt')
      ->at($this->vfsRoot)
      ->withContent($content);

    $fileWriter = $this->container->get('file.input_stream_file_writer');

    $filename = $fileWriter->writeStreamToFile(
      stream: vfsStream::url('root/foo.txt'),
      bytesToRead: 1024,
    );

    $this->assertStringStartsWith('temporary://', $filename);
    $this->assertStringEqualsFile($filename, $content);
  }

}

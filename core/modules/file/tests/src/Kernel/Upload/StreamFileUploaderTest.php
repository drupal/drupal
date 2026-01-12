<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Kernel\Upload;

use Drupal\file\Upload\InputStreamFileWriter;
use Drupal\KernelTests\KernelTestBase;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the stream file uploader.
 */
#[CoversClass(InputStreamFileWriter::class)]
#[Group('file')]
#[RunTestsInSeparateProcesses]
class StreamFileUploaderTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['file'];

  /**
   * Tests write stream to file success.
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
   * Tests write stream to file with smaller bytes.
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

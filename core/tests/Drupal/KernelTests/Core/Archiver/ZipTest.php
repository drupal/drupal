<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Archiver;

use Drupal\Core\Archiver\Zip;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Drupal\Core\Archiver\Zip.
 */
#[CoversClass(Zip::class)]
#[Group('zip')]
#[RunTestsInSeparateProcesses]
#[IgnoreDeprecations]
class ZipTest extends ArchiverTestBase {
  /**
   * {@inheritdoc}
   */
  protected $archiverPluginId = 'Zip';

  /**
   * Tests that the Zip archive is created if it does not exist.
   */
  public function testCreateArchive(): void {
    $this->expectDeprecation('\Drupal\Core\Archiver\Zip is deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. There is no replacement. See https://www.drupal.org/node/3556927');

    $textFile = current($this->getTestFiles('text'));
    $archiveFilename = $this->fileSystem->realpath('public://' . $this->randomMachineName() . '.zip');
    $zip = new Zip($archiveFilename, [
      'flags' => \ZipArchive::CREATE,
    ]);
    $zip->add($this->fileSystem->realPath($textFile->uri));
    // Close the archive and make sure it is written to disk.
    $this->assertTrue($zip->getArchive()->close(), 'Successfully closed archive.');
    $this->assertFileExists($archiveFilename, 'Archive is automatically created if the file does not exist.');
    $this->assertArchiveContainsFile($archiveFilename, $this->fileSystem->realPath($textFile->uri));
  }

  /**
   * Tests that the Zip archiver is created and overwritten.
   */
  public function testOverwriteArchive(): void {
    $this->expectDeprecation('\Drupal\Core\Archiver\Zip is deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. There is no replacement. See https://www.drupal.org/node/3556927');

    // Create an archive similarly to how it's done in ::testCreateArchive.
    $files = $this->getTestFiles('text');
    $textFile = current($files);
    $archiveFilename = $this->fileSystem->realpath('public://' . $this->randomMachineName() . '.zip');
    $zip = new Zip($archiveFilename, [
      'flags' => \ZipArchive::CREATE,
    ]);
    $zip->add($this->fileSystem->realPath($textFile->uri));
    $zip->getArchive()->close();
    $this->assertArchiveContainsFile($archiveFilename, $this->fileSystem->realPath($textFile->uri));
    // Overwrite the zip with just a new text file.
    $secondTextFile = next($files);
    $zip = new Zip($archiveFilename, [
      'flags' => \ZipArchive::OVERWRITE,
    ]);
    $zip->add($this->fileSystem->realpath($secondTextFile->uri));
    $zip->getArchive()->close();
    $this->assertArchiveNotContainsFile($archiveFilename, $this->fileSystem->realPath($textFile->uri));
    $this->assertArchiveContainsFile($archiveFilename, $this->fileSystem->realPath($secondTextFile->uri));
  }

}

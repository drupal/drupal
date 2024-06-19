<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Archiver;

use Drupal\Core\Archiver\Tar;

/**
 * @coversDefaultClass \Drupal\Core\Archiver\Tar
 * @group tar
 */
class TarTest extends ArchiverTestBase {
  /**
   * {@inheritdoc}
   */
  protected $archiverPluginId = 'Tar';

  /**
   * Tests that the Tar archive is created if it does not exist.
   */
  public function testCreateArchive(): void {
    $textFile = current($this->getTestFiles('text'));
    $archiveFilename = $this->fileSystem->realpath('public://' . $this->randomMachineName() . '.tar');
    $tar = new Tar($archiveFilename);
    $tar->add($this->fileSystem->realPath($textFile->uri));
    $this->assertFileExists($archiveFilename, 'Archive is automatically created if the file does not exist.');
    $this->assertArchiveContainsFile($archiveFilename, $this->fileSystem->realPath($textFile->uri));
  }

}

<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Archiver;

use Drupal\Core\Archiver\Tar;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Drupal\Core\Archiver\Tar.
 */
#[CoversClass(Tar::class)]
#[Group('tar')]
#[RunTestsInSeparateProcesses]
#[IgnoreDeprecations]
class TarTest extends ArchiverTestBase {
  /**
   * {@inheritdoc}
   */
  protected $archiverPluginId = 'Tar';

  /**
   * Tests that the Tar archive is created if it does not exist.
   */
  public function testCreateArchive(): void {
    $this->expectDeprecation('\Drupal\Core\Archiver\Tar is deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. There is no replacement. See https://www.drupal.org/node/3556927');

    $textFile = current($this->getTestFiles('text'));
    $archiveFilename = $this->fileSystem->realpath('public://' . $this->randomMachineName() . '.tar');
    $tar = new Tar($archiveFilename);
    $tar->add($this->fileSystem->realPath($textFile->uri));
    $this->assertFileExists($archiveFilename, 'Archive is automatically created if the file does not exist.');
    $this->assertArchiveContainsFile($archiveFilename, $this->fileSystem->realPath($textFile->uri));
  }

}

<?php

declare(strict_types=1);

namespace Drupal\BuildTests\TestSiteApplication;

use Drupal\BuildTests\Framework\BuildTestBase;
use Drupal\sqlite\Driver\Database\sqlite\Install\Tasks;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\PhpExecutableFinder;

/**
 * @group Build
 * @group TestSiteApplication
 */
class InstallTest extends BuildTestBase {

  public function testInstall(): void {
    $sqlite = (new \PDO('sqlite::memory:'))->query('select sqlite_version()')->fetch()[0];
    if (version_compare($sqlite, Tasks::SQLITE_MINIMUM_VERSION) < 0) {
      $this->markTestSkipped();
    }
    $this->copyCodebase();
    $fs = new Filesystem();
    $fs->chmod($this->getWorkspaceDirectory() . '/sites/default', 0700, 0000);

    // Composer tells you stuff in error output.
    $this->executeCommand('COMPOSER_DISCARD_CHANGES=true composer install --no-interaction');
    $this->assertErrorOutputContains('Generating autoload files');

    // We have to stand up the server first so we can know the port number to
    // pass along to the install command.
    $this->standUpServer();

    $php_finder = new PhpExecutableFinder();
    $install_command = [
      $php_finder->find(),
      './core/scripts/test-site.php',
      'install',
      '--base-url=http://localhost:' . $this->getPortNumber(),
      '--db-url=sqlite://localhost/foo.sqlite',
      '--install-profile=minimal',
      '--json',
    ];
    $this->assertNotEmpty($output_json = $this->executeCommand(implode(' ', $install_command))->getOutput());
    $this->assertCommandSuccessful();
    $connection_details = json_decode($output_json, TRUE);
    foreach (['db_prefix', 'user_agent', 'site_path'] as $key) {
      $this->assertArrayHasKey($key, $connection_details);
    }

    // Visit paths with expectations.
    $this->visit();
    $this->assertDrupalVisit();
  }

}

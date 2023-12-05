<?php

declare(strict_types=1);

namespace Drupal\BuildTests\Framework\Tests;

use Drupal\BuildTests\QuickStart\QuickStartTestBase;
use Drupal\sqlite\Driver\Database\sqlite\Install\Tasks;

/**
 * @coversDefaultClass \Drupal\BuildTests\Framework\BuildTestBase
 * @group Build
 * @requires extension pdo_sqlite
 */
class HtRouterTest extends QuickStartTestBase {

  /**
   * @covers ::instantiateServer
   */
  public function testHtRouter() {
    $sqlite = (new \PDO('sqlite::memory:'))->query('select sqlite_version()')->fetch()[0];
    if (version_compare($sqlite, Tasks::SQLITE_MINIMUM_VERSION) < 0) {
      $this->markTestSkipped();
    }

    $this->copyCodebase();
    $this->executeCommand('COMPOSER_DISCARD_CHANGES=true composer install --no-dev --no-interaction');
    $this->assertErrorOutputContains('Generating autoload files');
    $this->installQuickStart('minimal');
    $this->formLogin($this->adminUsername, $this->adminPassword);
    $this->visit('/.well-known/change-password');
    $this->assertDrupalVisit();
    $url = $this->getMink()->getSession()->getCurrentUrl();
    $this->assertEquals('http://localhost:' . $this->getPortNumber() . '/user/1/edit', $url);
  }

}

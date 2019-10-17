<?php

namespace Drupal\BuildTests\Framework\Tests;

use Drupal\BuildTests\QuickStart\QuickStartTestBase;

/**
 * @coversDefaultClass \Drupal\BuildTests\Framework\BuildTestBase
 * @group Build
 */
class HtRouterTest extends QuickStartTestBase {

  /**
   * @covers ::instantiateServer
   */
  public function testHtRouter() {
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

<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests that system.rss config is deleted.
 *
 * @see system_post_update_delete_rss_config()
 */
#[Group('update')]
#[RunTestsInSeparateProcesses]
class SystemRssDeleteTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../fixtures/update/drupal-10.3.0.bare.standard.php.gz',
    ];
  }

  /**
   * Ensures that system.rss is deleted after updating.
   */
  public function testUpdate(): void {
    $config = $this->config('system.rss');
    $this->assertFalse($config->isNew());

    $this->runUpdates();

    $config = $this->config('system.rss');
    $this->assertTrue($config->isNew());
  }

}

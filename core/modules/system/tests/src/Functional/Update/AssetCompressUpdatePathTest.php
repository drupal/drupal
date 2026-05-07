<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the upgrade path renaming system.performance gzip keys to compress.
 */
#[Group('Update')]
#[RunTestsInSeparateProcesses]
class AssetCompressUpdatePathTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-11.3.0.bare.standard.php.gz',
    ];
  }

  /**
   * Tests the upgrade path for renaming gzip keys to compress.
   */
  public function testRunUpdates(): void {
    $config = \Drupal::config('system.performance');
    $this->assertIsBool($config->get('css.gzip'));
    $this->assertIsBool($config->get('js.gzip'));

    $this->runUpdates();

    $config = \Drupal::config('system.performance');
    $this->assertNull($config->get('css.gzip'));
    $this->assertNull($config->get('js.gzip'));
    $this->assertIsBool($config->get('css.compress'));
    $this->assertIsBool($config->get('js.compress'));
  }

}

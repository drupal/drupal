<?php

namespace Drupal\Tests\jsonapi\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests adding retry-after header settings.
 *
 * @group legacy
 * @group jsonapi
 */
class JsonApiUpdatePathTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-9.0.0.bare.standard.php.gz',
      __DIR__ . '/../../../../tests/fixtures/update/jsonapi.php',
    ];
  }

  /**
   * Tests adding retry-after header settings.
   *
   * @see jsonapi_update_9401()
   */
  public function testUpdate9401() {
    $config = $this->config('jsonapi.settings');
    $this->assertTrue($config->get('read_only'));
    $this->assertNull($config->get('maintenance_header_retry_seconds'));

    // Run updates.
    $this->runUpdates();

    $config = $this->config('jsonapi.settings');
    $this->assertTrue($config->get('read_only'));
    $header_settings = $config->get('maintenance_header_retry_seconds');
    $this->assertSame(5, $header_settings['min']);
    $this->assertSame(10, $header_settings['max']);
  }

}

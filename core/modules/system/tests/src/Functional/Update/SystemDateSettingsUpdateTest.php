<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests system.date:country.default and system.date:timezone.default values.
 *
 * @group system
 * @covers \system_post_update_convert_empty_country_and_timezone_settings_to_null
 */
class SystemDateSettingsUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      DRUPAL_ROOT . '/core/modules/system/tests/fixtures/update/drupal-9.4.0.bare.standard.php.gz',
    ];
  }

  /**
   * Tests update of system.date:country.default & system.date:country.default.
   */
  public function testUpdate(): void {
    // Replace the 'Australia/Sydney' time zone with an empty string, set
    // previously in \Drupal\Core\Test\FunctionalTestSetupTrait::initConfig().
    $this->config('system.date')->set('timezone.default', '')->save();
    $config_before = $this->config('system.date');
    $this->assertSame('', $config_before->get('country.default'));
    $this->assertSame('', $config_before->get('timezone.default'));

    $this->runUpdates();

    $config_after = $this->config('system.date');
    $this->assertNull($config_after->get('country.default'));
    $this->assertNull($config_after->get('timezone.default'));
  }

}

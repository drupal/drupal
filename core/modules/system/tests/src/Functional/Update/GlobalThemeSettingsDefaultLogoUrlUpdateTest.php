<?php

namespace Drupal\Tests\system\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\Tests\UpdatePathTestTrait;

/**
 * Tests update of system.theme.global:logo.url if it's still the default of "".
 *
 * @group system
 * @covers \system_post_update_set_blank_log_url_to_null
 */
class GlobalThemeSettingsDefaultLogoUrlUpdateTest extends UpdatePathTestBase {

  use UpdatePathTestTrait;

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
   * Tests update of system.theme.global:logo.url.
   */
  public function testUpdate() {
    $logo_url_before = $this->config('system.theme.global')->get('logo.url');
    $this->assertSame('', $logo_url_before);

    $this->runUpdates();

    $logo_url_after = $this->config('system.theme.global')->get('logo.url');
    $this->assertNull($logo_url_after);
  }

}

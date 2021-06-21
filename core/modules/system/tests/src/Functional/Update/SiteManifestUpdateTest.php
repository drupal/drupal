<?php

namespace Drupal\Tests\system\Functional\Update;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests that the site manifest configuration is created.
 *
 * @group system
 * @group legacy
 */
class SiteManifestUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  public function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.8.0.bare.standard.php.gz',
    ];
  }

  /**
   * Tests system_update_8801().
   */
  public function testSiteManifestUpdate() {
    // Make sure we have the expected values before the update.
    $this->assertNull($this->config('system.site')->get('manifest'));

    $this->runUpdates();

    $site_config = $this->config('system.site');
    $expected_result = [
      'start_url' => $site_config->get('page.front'),
      'display' => NULL,
      'short_name' => $site_config->get('name'),
      'name' => $site_config->get('name'),
      'manifest_version' => 2,
    ];

    // Assert the new configuration was installed.
    $manifest_configuration = $site_config->get('manifest');
    $this->assertNotNull($manifest_configuration);
    foreach ($expected_result as $key => $value) {
      $this->assertArrayHasKey($key, $manifest_configuration);
      $this->assertEquals($value, $manifest_configuration[$key]);
    }

    // Assert the manifest path is accessible and the returned values are
    // correct.
    $assert_session = $this->assertSession();
    $manifest_result = $this->drupalGet(Url::fromRoute('system.manifest'));
    $assert_session->statusCodeEquals(200);
    $manifest_result = Json::decode($manifest_result);
    foreach ($expected_result as $key => $value) {
      // The display configuration is null and thus omitted.
      // @see \Drupal\Core\Theme\ManifestGenerator::doGenerateManifest()
      if ($key == 'display') {
        $this->assertArrayNotHasKey('display', $manifest_result);
        continue;
      }
      $this->assertArrayHasKey($key, $manifest_result);
      $this->assertEquals($value, $manifest_result[$key]);
    }
  }

}

<?php

declare(strict_types=1);

namespace Drupal\Tests\update\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\update\UpdateFetcherInterface;
use Drupal\update\UpdateManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;

/**
 * Tests the project data when the installed version is a dev version.
 *
 * @group update
 */
class DevReleaseTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'update', 'update_test'];

  /**
   * The http client.
   */
  protected Client $client;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // The Update Status module's default configuration must be installed for
    // our fake release metadata to be fetched.
    $this->installConfig('update');
    $this->installConfig('update_test');
    $this->setCoreVersion('8.1.0-dev');
    $this->setReleaseMetadata(__DIR__ . '/../../fixtures/release-history/drupal.sec.8.1.0-dev.xml');

  }

  /**
   * Sets the current version of core, as known to the Update Status module.
   *
   * @param string $version
   *   The current version of core.
   */
  protected function setCoreVersion(string $version): void {
    $this->config('update_test.settings')
      ->set('system_info.#all.version', $version)
      ->save();
  }

  /**
   * Sets the release metadata file to use when fetching available updates.
   *
   * @param string $file
   *   The path of the XML metadata file to use.
   */
  protected function setReleaseMetadata(string $file): void {
    $metadata = Utils::tryFopen($file, 'r');
    $response = new Response(200, [], Utils::streamFor($metadata));
    $handler = new MockHandler([$response]);
    $this->client = new Client([
      'handler' => HandlerStack::create($handler),
    ]);
    $this->container->set('http_client', $this->client);
  }

  /**
   * Tests security updates when the installed version is a dev version.
   *
   * The xml fixture used here has two security releases 8.1.2 and 8.1.1.
   *
   * Here the timestamp for the installed dev version is set to 1280424641.
   * 8.1.2 will be shown as security update as the date of this security release
   * i.e. 1280424741 is greater than the timestamp of the installed version +
   * 100 seconds. 8.1.1 will not be shown as security update because it's date
   * i.e. 1280424740 is less than timestamp of the installed version + 100
   * seconds.
   */
  public function testSecurityUpdates(): void {
    $system_info = [
      '#all' => [
        'version' => '8.1.0-dev',
        'datestamp' => '1280424641',
      ],
    ];
    $project_data = $this->getProjectData($system_info);
    $this->assertCount(1, $project_data['drupal']['security updates']);
    $this->assertSame('8.1.2', $project_data['drupal']['security updates'][0]['version']);
    $this->assertSame(UpdateManagerInterface::NOT_CURRENT, $project_data['drupal']['status']);
  }

  /**
   * Tests security updates are empty with a dev version and an empty timestamp.
   *
   * Here the timestamp for the installed dev version is set to 0(empty
   *  timestamp) and according to the current logic for dev installed version,
   *  no updates will be shown as security update.
   */
  public function testSecurityUpdateEmptyProjectTimestamp(): void {
    $system_info = [
      '#all' => [
        'version' => '8.1.0-dev',
        'datestamp' => '0',
      ],
    ];
    $project_data = $this->getProjectData($system_info);
    $this->assertArrayNotHasKey('security updates', $project_data['drupal']);
    $this->assertSame(UpdateFetcherInterface::NOT_CHECKED, $project_data['drupal']['status']);
    $this->assertSame('Unknown release date', (string) $project_data['drupal']['reason']);
  }

  /**
   * Gets project data from update_calculate_project_data().
   *
   * @param array $system_info
   *   System test information as used by update_test_system_info_alter().
   *
   * @return array[]
   *   The project data as returned by update_calculate_project_data().
   *
   * @see update_test_system_info_alter()
   */
  private function getProjectData(array $system_info): array {
    $this->config('update_test.settings')
      ->set('system_info', $system_info)
      ->save();
    update_storage_clear();
    $available = update_get_available(TRUE);
    return update_calculate_project_data($available);
  }

}

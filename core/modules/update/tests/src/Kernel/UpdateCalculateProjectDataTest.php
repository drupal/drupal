<?php

declare(strict_types=1);

namespace Drupal\Tests\update\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\update\UpdateManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;

/**
 * Test the values set in update_calculate_project_data().
 *
 * @group update
 */
class UpdateCalculateProjectDataTest extends KernelTestBase {

  /**
   * The Guzzle HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'update', 'update_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // The Update module's default configuration must be installed for our
    // fake release metadata to be fetched.
    $this->installConfig('update');
    $this->installConfig('update_test');
    $this->setCoreVersion('8.0.1');

  }

  /**
   * Sets the installed version of core, as known to the Update module.
   *
   * @param string $version
   *   The core version.
   *
   * @see update_test_system_info_alter()
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
   * Data provider for testProjectStatus().
   *
   * The test cases rely on the following fixtures:
   * - drupal.project_status.revoked.0.2.xml: Project_status is 'revoked'.
   * - drupal.project_status.insecure.0.2.xml:  Project_status is 'insecure'.
   * - drupal.project_status.unsupported.0.2.xml: Project_status is
   *   'unsupported'.
   *
   * @return array[]
   *   Test data.
   */
  public static function providerProjectStatus(): array {
    return [
      'revoked' => [
        'fixture' => '/../../fixtures/release-history/drupal.project_status.revoked.0.2.xml',
        'status' => UpdateManagerInterface::REVOKED,
        'label' => 'Project revoked',
        'expected_error_message' => 'This project has been revoked, and is no longer available for download. Uninstalling everything included by this project is strongly recommended!',
      ],
      'insecure' => [
        'fixture' => '/../../fixtures/release-history/drupal.project_status.insecure.0.2.xml',
        'status' => UpdateManagerInterface::NOT_SECURE,
        'label' => 'Project not secure',
        'expected_error_message' => 'This project has been labeled insecure by the Drupal security team, and is no longer available for download. Immediately uninstalling everything included by this project is strongly recommended!',
      ],
      'unsupported' => [
        'fixture' => '/../../fixtures/release-history/drupal.project_status.unsupported.0.2.xml',
        'status' => UpdateManagerInterface::NOT_SUPPORTED,
        'label' => 'Project not supported',
        'expected_error_message' => 'This project is no longer supported, and is no longer available for download. Uninstalling everything included by this project is strongly recommended!',
      ],
    ];
  }

  /**
   * Tests the project_status of the project.
   *
   * @dataProvider providerProjectStatus
   *
   * @covers update_calculate_project_update_status
   */
  public function testProjectStatus(string $fixture, int $status, string $label, string $expected_error_message): void {
    update_storage_clear();
    $this->setReleaseMetadata(__DIR__ . $fixture);
    $available = update_get_available(TRUE);
    $project_data = update_calculate_project_data($available);
    $this->assertArrayHasKey('status', $project_data['drupal']);
    $this->assertEquals($status, $project_data['drupal']['status']);
    $this->assertArrayHasKey('extra', $project_data['drupal']);
    $this->assertEquals($label, $project_data['drupal']['extra']['0']['label']);
    $this->assertEquals($expected_error_message, $project_data['drupal']['extra']['0']['data']);
  }

}

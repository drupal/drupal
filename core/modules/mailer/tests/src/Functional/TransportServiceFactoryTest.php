<?php

declare(strict_types=1);

namespace Drupal\Tests\mailer\Functional;

use Drupal\Tests\BrowserTestBase;
use Symfony\Component\Mailer\Transport\NullTransport;

/**
 * Tests the transport service factory in the child site of browser tests.
 *
 * @group mailer
 */
class TransportServiceFactoryTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'mailer',
    'mailer_transport_factory_functional_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test that the transport is set to null://null by default in the child site.
   *
   * The mailer configuration is set to a safe default during test setUp by
   * FunctionalTestSetupTrait::initConfig(). This is in order to prevent tests
   * from accidentally sending out emails. This test ensures that the transport
   * service is configured correctly in the test child site.
   */
  public function testDefaultTestMailFactory(): void {
    $response = $this->drupalGet('mailer-transport-factory-functional-test/transport-info');
    $actual = json_decode($response, TRUE);

    $expected = [
      'mailerDsn' => [
        'scheme' => 'null',
        'host' => 'null',
        'user' => NULL,
        'password' => NULL,
        'port' => NULL,
        'options' => [],
      ],
      'mailerTransportClass' => NullTransport::class,
    ];
    $this->assertEquals($expected, $actual);
  }

}

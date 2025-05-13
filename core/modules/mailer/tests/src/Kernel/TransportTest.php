<?php

declare(strict_types=1);

namespace Drupal\Tests\mailer\Kernel;

use Drupal\Core\Site\Settings;
use Drupal\KernelTests\KernelTestBase;
use Drupal\mailer_transport_factory_kernel_test\Transport\CanaryTransport;
use PHPUnit\Framework\Attributes\After;
use Symfony\Component\Mailer\Transport\NullTransport;
use Symfony\Component\Mailer\Transport\SendmailTransport;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Transport\TransportInterface;

/**
 * Tests the transport factory service.
 *
 * @group mailer
 * @coversDefaultClass \Drupal\Core\Mailer\TransportServiceFactory
 */
class TransportTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['mailer', 'system'];

  /**
   * Sets up a mailer DSN config override.
   *
   * @param string $scheme
   *   The mailer DSN scheme.
   * @param string $host
   *   The mailer DSN host.
   * @param string|null $user
   *   The mailer DSN username.
   * @param string|null $password
   *   The mailer DSN password.
   * @param int|null $port
   *   The mailer DSN port.
   * @param array<string, mixed> $options
   *   Options for the mailer transport.
   */
  protected function setUpMailerDsnConfigOverride(
    string $scheme,
    string $host,
    ?string $user = NULL,
    #[\SensitiveParameter] ?string $password = NULL,
    ?int $port = NULL,
    array $options = [],
  ): void {
    $GLOBALS['config']['system.mail']['mailer_dsn'] = [
      'scheme' => $scheme,
      'host' => $host,
      'user' => $user,
      'password' => $password,
      'port' => $port,
      'options' => $options,
    ];
  }

  /**
   * Resets a mailer DSN config override.
   *
   * Clean up the globals modified by setUpMailerDsnConfigOverride() during a
   * test.
   */
  #[After]
  protected function resetMailerDsnConfigOverride(): void {
    $this->setUpMailerDsnConfigOverride('null', 'null');
  }

  /**
   * @covers ::createTransport
   */
  public function testDefaultTestMailFactory(): void {
    $actual = $this->container->get(TransportInterface::class);
    $this->assertInstanceOf(NullTransport::class, $actual);
  }

  /**
   * @dataProvider providerTestBuiltinFactory
   * @covers ::createTransport
   */
  public function testBuiltinFactory(string $schema, string $host, string $expected): void {
    $this->setUpMailerDsnConfigOverride($schema, $host);

    $actual = $this->container->get(TransportInterface::class);
    $this->assertInstanceOf($expected, $actual);
  }

  /**
   * Provides test data for testBuiltinFactory().
   */
  public static function providerTestBuiltinFactory(): iterable {
    yield ['null', 'null', NullTransport::class];
    yield ['sendmail', 'default', SendmailTransport::class];
    yield ['smtp', 'default', EsmtpTransport::class];
  }

  /**
   * @covers ::createTransport
   * @covers \Drupal\Core\Mailer\Transport\SendmailCommandValidationTransportFactory::create
   */
  public function testSendmailFactoryAllowedCommand(): void {
    // Test sendmail command allowlist.
    $settings = Settings::getAll();
    $settings['mailer_sendmail_commands'] = ['/usr/local/bin/sendmail -bs'];
    new Settings($settings);

    // Test allowlisted command.
    $this->setUpMailerDsnConfigOverride('sendmail', 'default', options: [
      'command' => '/usr/local/bin/sendmail -bs',
    ]);
    $actual = $this->container->get(TransportInterface::class);
    $this->assertInstanceOf(SendmailTransport::class, $actual);
  }

  /**
   * @covers ::createTransport
   * @covers \Drupal\Core\Mailer\Transport\SendmailCommandValidationTransportFactory::create
   */
  public function testSendmailFactoryUnlistedCommand(): void {
    // Test sendmail command allowlist.
    $settings = Settings::getAll();
    $settings['mailer_sendmail_commands'] = ['/usr/local/bin/sendmail -bs'];
    new Settings($settings);

    // Test unlisted command.
    $this->setUpMailerDsnConfigOverride('sendmail', 'default', options: [
      'command' => '/usr/bin/bc',
    ]);
    $this->expectExceptionMessage('Unsafe sendmail command /usr/bin/bc');
    $this->container->get(TransportInterface::class);
  }

  /**
   * @covers ::createTransport
   */
  public function testMissingFactory(): void {
    $this->setUpMailerDsnConfigOverride('drupal.no-transport', 'default');

    $this->expectExceptionMessage('The "drupal.no-transport" scheme is not supported');
    $this->container->get(TransportInterface::class);
  }

  /**
   * @covers ::createTransport
   */
  public function testThirdPartyFactory(): void {
    $this->enableModules(['mailer_transport_factory_kernel_test']);

    $this->setUpMailerDsnConfigOverride('drupal.test-canary', 'default');

    $actual = $this->container->get(TransportInterface::class);
    $this->assertInstanceOf(CanaryTransport::class, $actual);
  }

}

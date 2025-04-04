<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Config;

use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests validation of mailer dsn config.
 *
 * @group config
 * @group Validation
 */
class MailerDsnConfigValidationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * Config manager service.
   */
  protected TypedConfigManagerInterface $configManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig('system');
    $this->configManager = $this->container->get(TypedConfigManagerInterface::class);
  }

  /**
   * Tests the validation of the mailer scheme.
   */
  public function testMailerSchemeValidation(): void {
    $config = $this->config('system.mail');
    $this->assertFalse($config->isNew());
    $data = $config->get();

    // If the scheme is NULL, it should be an error.
    $data['mailer_dsn']['scheme'] = NULL;
    $violations = $this->configManager->createFromNameAndData($config->getName(), $data)
      ->validate();
    $this->assertCount(1, $violations);
    $this->assertSame('mailer_dsn.scheme', $violations[0]->getPropertyPath());
    $this->assertSame('This value should not be null.', (string) $violations[0]->getMessage());

    // If the scheme is blank, it should be an error.
    $data['mailer_dsn']['scheme'] = '';
    $violations = $this->configManager->createFromNameAndData($config->getName(), $data)
      ->validate();
    $this->assertCount(1, $violations);
    $this->assertSame('mailer_dsn.scheme', $violations[0]->getPropertyPath());
    $this->assertSame('The mailer DSN must contain a scheme.', (string) $violations[0]->getMessage());

    // If the scheme doesn't start with a letter, it should be an error.
    $data['mailer_dsn']['scheme'] = '-unexpected-first-character';
    $violations = $this->configManager->createFromNameAndData($config->getName(), $data)
      ->validate();
    $this->assertCount(1, $violations);
    $this->assertSame('mailer_dsn.scheme', $violations[0]->getPropertyPath());
    $this->assertSame('The mailer DSN scheme must start with a letter followed by zero or more letters, numbers, plus (+), minus (-) or periods (.)', (string) $violations[0]->getMessage());

    // If the scheme contains unexpected characters, it should be an error.
    $data['mailer_dsn']['scheme'] = 'unexpected_underscore';
    $violations = $this->configManager->createFromNameAndData($config->getName(), $data)
      ->validate();
    $this->assertCount(1, $violations);
    $this->assertSame('mailer_dsn.scheme', $violations[0]->getPropertyPath());
    $this->assertSame('The mailer DSN scheme must start with a letter followed by zero or more letters, numbers, plus (+), minus (-) or periods (.)', (string) $violations[0]->getMessage());

    // If the scheme is valid, it should be accepted.
    $data['mailer_dsn']['scheme'] = 'smtp';
    $violations = $this->configManager->createFromNameAndData($config->getName(), $data)
      ->validate();
    $this->assertCount(0, $violations);

    // If the scheme is valid, it should be accepted.
    $data['mailer_dsn']['scheme'] = 'sendmail+smtp';
    $violations = $this->configManager->createFromNameAndData($config->getName(), $data)
      ->validate();
    $this->assertCount(0, $violations);

    // If the scheme is valid, it should be accepted.
    $data['mailer_dsn']['scheme'] = 'drupal.test-capture';
    $violations = $this->configManager->createFromNameAndData($config->getName(), $data)
      ->validate();
    $this->assertCount(0, $violations);
  }

  /**
   * Tests the validation of the mailer host.
   */
  public function testMailerHostValidation(): void {
    $config = $this->config('system.mail');
    $this->assertFalse($config->isNew());
    $data = $config->get();

    // If the host is NULL, it should be an error.
    $data['mailer_dsn']['host'] = NULL;
    $violations = $this->configManager->createFromNameAndData($config->getName(), $data)
      ->validate();
    $this->assertCount(1, $violations);
    $this->assertSame('mailer_dsn.host', $violations[0]->getPropertyPath());
    $this->assertSame('This value should not be null.', (string) $violations[0]->getMessage());

    // If the host is blank, it should be an error.
    $data['mailer_dsn']['host'] = '';
    $violations = $this->configManager->createFromNameAndData($config->getName(), $data)
      ->validate();
    $this->assertCount(1, $violations);
    $this->assertSame('mailer_dsn.host', $violations[0]->getPropertyPath());
    $this->assertSame('The mailer DSN must contain a host (use "default" by default).', (string) $violations[0]->getMessage());

    // If the host contains a newline, it should be an error.
    $data['mailer_dsn']['host'] = "host\nwith\nnewline";
    $violations = $this->configManager->createFromNameAndData($config->getName(), $data)
      ->validate();
    $this->assertCount(1, $violations);
    $this->assertSame('mailer_dsn.host', $violations[0]->getPropertyPath());
    $this->assertSame('The mailer DSN host should conform to RFC 3986 URI host component.', (string) $violations[0]->getMessage());

    // If the host contains unexpected characters, it should be an error.
    $data['mailer_dsn']['host'] = "host\rwith\tcontrol-chars";
    $violations = $this->configManager->createFromNameAndData($config->getName(), $data)
      ->validate();
    $this->assertCount(1, $violations);
    $this->assertSame('mailer_dsn.host', $violations[0]->getPropertyPath());
    $this->assertSame('The mailer DSN host should conform to RFC 3986 URI host component.', (string) $violations[0]->getMessage());

    // If the host is valid, it should be accepted.
    $data['mailer_dsn']['host'] = 'default';
    $violations = $this->configManager->createFromNameAndData($config->getName(), $data)
      ->validate();
    $this->assertCount(0, $violations);

    // If the host is valid, it should be accepted.
    $data['mailer_dsn']['host'] = 'mail.example.com';
    $violations = $this->configManager->createFromNameAndData($config->getName(), $data)
      ->validate();
    $this->assertCount(0, $violations);

    // If the host is valid, it should be accepted.
    $data['mailer_dsn']['host'] = '127.0.0.1';
    $violations = $this->configManager->createFromNameAndData($config->getName(), $data)
      ->validate();
    $this->assertCount(0, $violations);

    // If the host is valid, it should be accepted.
    $data['mailer_dsn']['host'] = '[::1]';
    $violations = $this->configManager->createFromNameAndData($config->getName(), $data)
      ->validate();
    $this->assertCount(0, $violations);
  }

  /**
   * Tests the validation of the password for the mailer user.
   */
  public function testMailerUserPasswordValidation(): void {
    $config = $this->config('system.mail');
    $this->assertFalse($config->isNew());
    $data = $config->get();

    // If the user is valid, it should be accepted.
    $data['mailer_dsn']['user'] = "any😎thing\ngoes";
    $violations = $this->configManager->createFromNameAndData($config->getName(), $data)
      ->validate();
    $this->assertCount(0, $violations);

    // If the password is valid, it should be accepted.
    $data['mailer_dsn']['password'] = "any😎thing\ngoes";
    $violations = $this->configManager->createFromNameAndData($config->getName(), $data)
      ->validate();
    $this->assertCount(0, $violations);
  }

  /**
   * Tests the validation of the port used by the mailer.
   */
  public function testMailerPortValidation(): void {
    $config = $this->config('system.mail');
    $this->assertFalse($config->isNew());
    $data = $config->get();

    // If the port is negative, it should be an error.
    $data['mailer_dsn']['port'] = -1;
    $violations = $this->configManager->createFromNameAndData($config->getName(), $data)
      ->validate();
    $this->assertCount(1, $violations);
    $this->assertSame('mailer_dsn.port', $violations[0]->getPropertyPath());
    $this->assertSame('The mailer DSN port must be between 0 and 65535.', (string) $violations[0]->getMessage());

    // If the port greater than 65535, it should be an error.
    $data['mailer_dsn']['port'] = 655351 + 1;
    $violations = $this->configManager->createFromNameAndData($config->getName(), $data)
      ->validate();
    $this->assertCount(1, $violations);
    $this->assertSame('mailer_dsn.port', $violations[0]->getPropertyPath());
    $this->assertSame('The mailer DSN port must be between 0 and 65535.', (string) $violations[0]->getMessage());

    // If the port is valid, it should be accepted.
    $data['mailer_dsn']['port'] = 587;
    $violations = $this->configManager->createFromNameAndData($config->getName(), $data)
      ->validate();
    $this->assertCount(0, $violations);
  }

  /**
   * Tests the validation of the default options of the mailer.
   */
  public function testMailerTransportDefaultOptionsValidation(): void {
    $config = $this->config('system.mail');
    $this->assertFalse($config->isNew());
    $data = $config->get();

    // Set scheme to an unknown schema.
    $data['mailer_dsn']['scheme'] = 'drupal.unknown-scheme+https';

    // If there is no more specific type for a scheme, options with any key
    // should be accepted.
    $data['mailer_dsn']['options'] = [
      'any_bool' => TRUE,
      'any_int' => 42,
      'any_string' => "any😎thing\ngoes",
    ];
    $violations = $this->configManager->createFromNameAndData($config->getName(), $data)
      ->validate();
    $this->assertCount(0, $violations);
  }

  /**
   * Tests the validation of the options for the 'native' mailer scheme.
   */
  public function testMailerTransportNativeOptionsValidation(): void {
    $config = $this->config('system.mail');
    $this->assertFalse($config->isNew());
    $data = $config->get();

    // Set scheme to native.
    $data['mailer_dsn']['scheme'] = 'native';

    // If the options contain an invalid key, it should be an error.
    $data['mailer_dsn']['options'] = ['invalid_key' => 'Hello'];
    $violations = $this->configManager->createFromNameAndData($config->getName(), $data)
      ->validate();
    $this->assertCount(1, $violations);
    $this->assertSame('mailer_dsn.options.invalid_key', $violations[0]->getPropertyPath());
    $this->assertSame("'invalid_key' is not a supported key.", (string) $violations[0]->getMessage());

    // If options is an empty map, it should be accepted.
    $data['mailer_dsn']['options'] = [];
    $violations = $this->configManager->createFromNameAndData($config->getName(), $data)
      ->validate();
    $this->assertCount(0, $violations);
  }

  /**
   * Tests the validation of the options for the 'null' mailer scheme.
   */
  public function testMailerTransportNullOptionsValidation(): void {
    $config = $this->config('system.mail');
    $this->assertFalse($config->isNew());
    $data = $config->get();

    // Set scheme to null.
    $data['mailer_dsn']['scheme'] = 'null';

    // If the options contain an invalid key, it should be an error.
    $data['mailer_dsn']['options'] = ['invalid_key' => 'Hello'];
    $violations = $this->configManager->createFromNameAndData($config->getName(), $data)
      ->validate();
    $this->assertCount(1, $violations);
    $this->assertSame('mailer_dsn.options.invalid_key', $violations[0]->getPropertyPath());
    $this->assertSame("'invalid_key' is not a supported key.", (string) $violations[0]->getMessage());

    // If options is an empty map, it should be accepted.
    $data['mailer_dsn']['options'] = [];
    $violations = $this->configManager->createFromNameAndData($config->getName(), $data)
      ->validate();
    $this->assertCount(0, $violations);
  }

  /**
   * Tests the validation of the options for the 'sendmail' mailer scheme.
   */
  public function testMailerTransportSendmailOptionsValidation(): void {
    $config = $this->config('system.mail');
    $this->assertFalse($config->isNew());
    $data = $config->get();

    // Set scheme to sendmail.
    $data['mailer_dsn']['scheme'] = 'sendmail';

    // If the options contain an invalid command, it should be an error.
    $data['mailer_dsn']['options'] = ['command' => "sendmail\t-bs\n"];
    $violations = $this->configManager->createFromNameAndData($config->getName(), $data)
      ->validate();
    $this->assertCount(1, $violations);
    $this->assertSame('mailer_dsn.options.command', $violations[0]->getPropertyPath());
    $this->assertSame('The command option is not allowed to span multiple lines or contain control characters.', (string) $violations[0]->getMessage());

    // If the options contain an invalid key, it should be an error.
    $data['mailer_dsn']['options'] = ['invalid_key' => 'Hello'];
    $violations = $this->configManager->createFromNameAndData($config->getName(), $data)
      ->validate();
    $this->assertCount(1, $violations);
    $this->assertSame('mailer_dsn.options.invalid_key', $violations[0]->getPropertyPath());
    $this->assertSame("'invalid_key' is not a supported key.", (string) $violations[0]->getMessage());

    // If the options contain a command, it should accepted.
    $data['mailer_dsn']['options'] = ['command' => 'sendmail -bs'];
    $violations = $this->configManager->createFromNameAndData($config->getName(), $data)
      ->validate();
    $this->assertCount(0, $violations);

    // If options is an empty map, it should be accepted.
    $data['mailer_dsn']['options'] = [];
    $violations = $this->configManager->createFromNameAndData($config->getName(), $data)
      ->validate();
    $this->assertCount(0, $violations);
  }

  /**
   * Tests the validation of the options for the 'smtps' mailer scheme.
   */
  public function testMailerTransportSMTPOptionsValidation(): void {
    $config = $this->config('system.mail');
    $this->assertFalse($config->isNew());
    $data = $config->get();

    // Set scheme to smtps.
    $data['mailer_dsn']['scheme'] = 'smtps';

    // If the options contain an invalid peer_fingerprint, it should be an
    // error.
    $data['mailer_dsn']['options'] = [
      'verify_peer' => FALSE,
      'peer_fingerprint' => 'BE:F7:B9:CA:0F:6E:0F:29:9B:E9:B4:64:99:35:D6:27',
    ];
    $violations = $this->configManager->createFromNameAndData($config->getName(), $data)
      ->validate();
    $this->assertCount(1, $violations);
    $this->assertSame('mailer_dsn.options.peer_fingerprint', $violations[0]->getPropertyPath());
    $this->assertSame('The peer_fingerprint option requires an md5, sha1 or sha256 certificate fingerprint in hex with all separators (colons) removed.', (string) $violations[0]->getMessage());

    // If the options contain a valid peer_fingerprint, it should be accepted.
    $data['mailer_dsn']['options'] = [
      'verify_peer' => FALSE,
      'peer_fingerprint' => 'BEF7B9CA0F6E0F299BE9B4649935D627',
    ];
    $violations = $this->configManager->createFromNameAndData($config->getName(), $data)
      ->validate();
    $this->assertCount(0, $violations);

    // If the options contain a valid peer_fingerprint, it should be accepted.
    $data['mailer_dsn']['options'] = [
      'verify_peer' => TRUE,
      'peer_fingerprint' => '87abbc4d1c3f23146362c6a1168fb7e90a56569c4c97275c69c0630dc06e526d',
    ];
    $violations = $this->configManager->createFromNameAndData($config->getName(), $data)
      ->validate();
    $this->assertCount(0, $violations);

    // If the options contain a local_domain with a newline, it should be an
    // error.
    $data['mailer_dsn']['options'] = ['local_domain' => "host\nwith\nnewline"];
    $violations = $this->configManager->createFromNameAndData($config->getName(), $data)
      ->validate();
    $this->assertCount(1, $violations);
    $this->assertSame('mailer_dsn.options.local_domain', $violations[0]->getPropertyPath());
    $this->assertSame('The local_domain is not allowed to span multiple lines or contain control characters.', (string) $violations[0]->getMessage());

    // If the options contain a local_domain with unexpected characters, it
    // should be an error.
    $data['mailer_dsn']['options'] = ['local_domain' => "host\rwith\tcontrol-chars"];
    $violations = $this->configManager->createFromNameAndData($config->getName(), $data)
      ->validate();
    $this->assertCount(1, $violations);
    $this->assertSame('mailer_dsn.options.local_domain', $violations[0]->getPropertyPath());
    $this->assertSame('The local_domain is not allowed to span multiple lines or contain control characters.', (string) $violations[0]->getMessage());

    // If the options contain a valid local_domain, it should be accepted.
    $data['mailer_dsn']['options'] = ['local_domain' => 'www.example.com'];
    $violations = $this->configManager->createFromNameAndData($config->getName(), $data)
      ->validate();
    $this->assertCount(0, $violations);
  }

}

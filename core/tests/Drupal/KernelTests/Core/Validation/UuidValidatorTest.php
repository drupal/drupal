<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Validation;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the UUID validator.
 *
 * @group Validation
 */
class UuidValidatorTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['config_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig('config_test');
  }

  /**
   * @see \Drupal\Core\Validation\Plugin\Validation\Constraint\UuidConstraint
   */
  public function testUuid(): void {
    $typed_config_manager = \Drupal::service('config.typed');
    /** @var \Drupal\Core\Config\Schema\TypedConfigInterface $typed_config */
    $typed_config = $typed_config_manager->get('config_test.validation');
    $typed_config->get('uuid')
      ->setValue(\Drupal::service('uuid')->generate());

    $this->assertCount(0, $typed_config->validate());

    $typed_config->get('uuid')
      ->setValue(\Drupal::service('uuid')->generate() . '-invalid');
    $this->assertCount(1, $typed_config->validate());
  }

  /**
   * @see \Drupal\Core\Validation\Plugin\Validation\Constraint\UriHostConstraint
   */
  public function testUriHost(): void {
    $typed_config_manager = \Drupal::service('config.typed');
    /** @var \Drupal\Core\Config\Schema\TypedConfigInterface $typed_config */
    $typed_config = $typed_config_manager->get('config_test.validation');

    // Test valid names.
    $typed_config->get('host')->setValue('example.com');
    $this->assertCount(0, $typed_config->validate());

    $typed_config->get('host')->setValue('example.com.');
    $this->assertCount(0, $typed_config->validate());

    $typed_config->get('host')->setValue('default');
    $this->assertCount(0, $typed_config->validate());

    // Test invalid names.
    $typed_config->get('host')->setValue('.example.com');
    $this->assertCount(1, $typed_config->validate());

    // Test valid IPv6 literals.
    $typed_config->get('host')->setValue('[::1]');
    $this->assertCount(0, $typed_config->validate());

    $typed_config->get('host')->setValue('[2001:DB8::]');
    $this->assertCount(0, $typed_config->validate());

    $typed_config->get('host')->setValue('[2001:db8:dd54:4473:bd6e:52db:10b3:4abe]');
    $this->assertCount(0, $typed_config->validate());

    // Test invalid IPv6 literals.
    $typed_config->get('host')->setValue('::1');
    $this->assertCount(1, $typed_config->validate());

    // Test valid IPv4 addresses.
    $typed_config->get('host')->setValue('127.0.0.1');
    $this->assertCount(0, $typed_config->validate());

    $typed_config->get('host')->setValue('192.0.2.254');
    $this->assertCount(0, $typed_config->validate());
  }

}

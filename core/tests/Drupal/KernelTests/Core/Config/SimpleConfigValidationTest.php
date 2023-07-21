<?php

declare(strict_types = 1);

namespace Drupal\KernelTests\Core\Config;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests validation of certain elements common to all config.
 *
 * @group config
 * @group Validation
 */
class SimpleConfigValidationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig('system');
  }

  public function testDefaultConfigHashValidation(): void {
    $config = $this->config('system.site');
    $this->assertFalse($config->isNew());
    $data = $config->get();
    $original_hash = $data['_core']['default_config_hash'];
    $this->assertNotEmpty($original_hash);

    /** @var \Drupal\Core\Config\TypedConfigManagerInterface $typed_config_manager */
    $typed_config_manager = $this->container->get('config.typed');

    // If the default_config_hash is NULL, it should be an error.
    $data['_core']['default_config_hash'] = NULL;
    $violations = $typed_config_manager->createFromNameAndData($config->getName(), $data)
      ->validate();
    $this->assertCount(1, $violations);
    $this->assertSame('_core.default_config_hash', $violations[0]->getPropertyPath());
    $this->assertSame('This value should not be null.', (string) $violations[0]->getMessage());

    // Config hashes must be 43 characters long.
    $data['_core']['default_config_hash'] = $original_hash . '-long';
    $violations = $typed_config_manager->createFromNameAndData($config->getName(), $data)
      ->validate();
    $this->assertCount(1, $violations);
    $this->assertSame('_core.default_config_hash', $violations[0]->getPropertyPath());
    $this->assertSame('This value should have exactly <em class="placeholder">43</em> characters.', (string) $violations[0]->getMessage());

    // Config hashes can only contain certain characters, and spaces aren't one
    // of them. If we replace the final character of the original hash with a
    // space, we should get an error.
    $data['_core']['default_config_hash'] = substr($original_hash, 0, -1) . ' ';
    $violations = $typed_config_manager->createFromNameAndData($config->getName(), $data)
      ->validate();
    $this->assertCount(1, $violations);
    $this->assertSame('_core.default_config_hash', $violations[0]->getPropertyPath());
    $this->assertSame('This value is not valid.', (string) $violations[0]->getMessage());

    $data['_core']['default_config_hash'] = $original_hash;
    $data['_core']['invalid_key'] = 'Hello';
    $violations = $typed_config_manager->createFromNameAndData($config->getName(), $data)
      ->validate();
    $this->assertCount(1, $violations);
    $this->assertSame('_core', $violations[0]->getPropertyPath());
    $this->assertSame("'invalid_key' is not a supported key.", (string) $violations[0]->getMessage());
  }

}

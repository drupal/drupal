<?php

declare(strict_types=1);

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
    $this->assertSame('_core.invalid_key', $violations[0]->getPropertyPath());
    $this->assertSame("'invalid_key' is not a supported key.", (string) $violations[0]->getMessage());
  }

  /**
   * Data provider for ::testSpecialCharacters().
   *
   * @return array[]
   *   The test cases.
   */
  public static function providerSpecialCharacters(): array {
    $data = [];

    for ($code_point = 0; $code_point < 32; $code_point++) {
      $data["label $code_point"] = [
        'system.site',
        'name',
        mb_chr($code_point),
        'Labels are not allowed to span multiple lines or contain control characters.',
      ];
      $data["text $code_point"] = [
        'system.maintenance',
        'message',
        mb_chr($code_point),
        'Text is not allowed to contain control characters, only visible characters.',
      ];
    }
    // Line feeds (ASCII 10) and carriage returns (ASCII 13) are used to create
    // new lines, so they are allowed in text data, along with tabs (ASCII 9).
    $data['text 9'][3] = $data['text 10'][3] = $data['text 13'][3] = NULL;

    // Ensure emoji are allowed.
    $data['emoji in label'] = [
      'system.site',
      'name',
      '😎',
      NULL,
    ];
    $data['emoji in text'] = [
      'system.maintenance',
      'message',
      '🤓',
      NULL,
    ];

    return $data;
  }

  /**
   * Tests that special characters are not allowed in labels or text data.
   *
   * @param string $config_name
   *   The name of the simple config to test with.
   * @param string $property
   *   The config property in which to embed a control character.
   * @param string $character
   *   A special character to embed.
   * @param string|null $expected_error_message
   *   The expected validation error message, if any.
   *
   * @dataProvider providerSpecialCharacters
   */
  public function testSpecialCharacters(string $config_name, string $property, string $character, ?string $expected_error_message): void {
    $config = $this->config($config_name)
      ->set($property, "This has a special character: $character");

    $violations = $this->container->get('config.typed')
      ->createFromNameAndData($config->getName(), $config->get())
      ->validate();

    if ($expected_error_message === NULL) {
      $this->assertCount(0, $violations);
    }
    else {
      $code_point = mb_ord($character);
      $this->assertCount(1, $violations, "Character $code_point did not raise a constraint violation.");
      $this->assertSame($property, $violations[0]->getPropertyPath());
      $this->assertSame($expected_error_message, (string) $violations[0]->getMessage());
    }
  }

  /**
   * Tests that plugin IDs in simple config are validated.
   *
   * @param string $config_name
   *   The name of the config object to validate.
   * @param string $property
   *   The property path to set. This will receive the value 'non_existent' and
   *   is expected to raise a "plugin does not exist" error.
   *
   * @testWith ["system.mail", "interface.0"]
   *   ["system.image", "toolkit"]
   */
  public function testInvalidPluginId(string $config_name, string $property): void {
    $config = $this->config($config_name);

    $violations = $this->container->get('config.typed')
      ->createFromNameAndData($config_name, $config->set($property, 'non_existent')->get())
      ->validate();

    $this->assertCount(1, $violations);
    $this->assertSame($property, $violations[0]->getPropertyPath());
    $this->assertSame("The 'non_existent' plugin does not exist.", (string) $violations[0]->getMessage());
  }

}

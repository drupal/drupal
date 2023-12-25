<?php

namespace Drupal\KernelTests\Core\TypedData;

use Drupal\block\Entity\Block;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\Core\TypedData\TraversableTypedDataInterface;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Tests the ValidKeys validation constraint.
 *
 * @group Validation
 *
 * @covers \Drupal\Core\Validation\Plugin\Validation\Constraint\ValidKeysConstraint
 * @covers \Drupal\Core\Validation\Plugin\Validation\Constraint\ValidKeysConstraintValidator
 */
class ValidKeysConstraintValidatorTest extends KernelTestBase {

  /**
   * The typed config under test.
   *
   * @var \Drupal\Core\TypedData\TraversableTypedDataInterface
   *
   * @see \Drupal\Core\Config\TypedConfigManagerInterface::get()
   */
  protected TraversableTypedDataInterface $config;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Install the Block module and create a Block config entity, so that we can
    // test that the validator infers keys from a defined schema.
    $this->enableModules(['system', 'block']);
    /** @var \Drupal\Core\Extension\ThemeInstallerInterface $theme_installer */
    $theme_installer = $this->container->get('theme_installer');
    $theme_installer->install(['stark']);

    $block = Block::create([
      'id' => 'branding',
      'plugin' => 'system_branding_block',
      'theme' => 'stark',
    ]);
    $block->save();

    $this->config = $this->container->get('config.typed')
      ->get('block.block.branding');
  }

  /**
   * Tests detecting unsupported keys.
   *
   * @see \Drupal\Core\Validation\Plugin\Validation\Constraint\ValidKeysConstraint::$invalidKeyMessage
   */
  public function testSupportedKeys(): void {
    // Start from the valid config.
    $this->assertEmpty($this->config->validate());

    // Then modify only one thing: generate a non-existent `foobar` setting.
    $data = $this->config->toArray();
    $data['settings']['foobar'] = TRUE;
    $this->assertValidationErrors('block.block.branding', $data,
      // Now 1 validation error should be triggered: one for the unsupported key.
      // @see \Drupal\system\Plugin\Block\SystemBrandingBlock::defaultConfiguration()
      // @see \Drupal\system\Plugin\Block\SystemPoweredByBlock::defaultConfiguration()
      [
        'settings.foobar' => "'foobar' is not a supported key.",
      ],
    );
  }

  /**
   * Tests detecting unknown keys.
   *
   * @see \Drupal\Core\Validation\Plugin\Validation\Constraint\ValidKeysConstraint::$dynamicInvalidKeyMessage
   */
  public function testUnknownKeys(): void {
    // Start from the valid config.
    $this->assertEmpty($this->config->validate());

    // Then modify only one thing: the block plugin that is being used.
    $data = $this->config->toArray();
    $data['plugin'] = 'system_powered_by_block';
    $this->assertValidationErrors('block.block.branding', $data,
      // Now 3 validation errors should be triggered: one for each of the
      // settings that exist in the "branding" block but not the "powered by"
      // block.
      // @see \Drupal\system\Plugin\Block\SystemBrandingBlock::defaultConfiguration()
      // @see \Drupal\system\Plugin\Block\SystemPoweredByBlock::defaultConfiguration()
      [
        'settings' => [
          "'use_site_logo' is an unknown key because plugin is system_powered_by_block (see config schema type block.settings.*).",
          "'use_site_name' is an unknown key because plugin is system_powered_by_block (see config schema type block.settings.*).",
          "'use_site_slogan' is an unknown key because plugin is system_powered_by_block (see config schema type block.settings.*).",
        ],
      ],
    );
  }

  /**
   * Tests the ValidKeys constraint validator.
   */
  public function testValidation(): void {
    // Create a data definition that specifies certain allowed keys.
    $definition = MapDataDefinition::create('mapping')
      ->addConstraint('ValidKeys', ['north', 'south', 'west']);
    $definition['mapping'] = [
      'north' => ['type' => 'string', 'requiredKey' => FALSE],
      'east' => ['type' => 'string', 'requiredKey' => FALSE],
      'south' => ['type' => 'string', 'requiredKey' => FALSE],
      'west' => ['type' => 'string', 'requiredKey' => FALSE],
    ];
    // @todo Remove this line in https://www.drupal.org/project/drupal/issues/3403782
    $definition->setClass('Drupal\Core\Config\Schema\Mapping');

    /** @var \Drupal\Core\TypedData\TypedDataManagerInterface $typed_config */
    $typed_config = $this->container->get('config.typed');
    // @see \Drupal\Core\Config\TypedConfigManager::buildDataDefinition()
    // @see \Drupal\Core\TypedData\TypedDataManager::createDataDefinition()
    $definition->setTypedDataManager($typed_config);

    // Passing a non-array value should raise an exception.
    try {
      // TRICKY: we must clone the definition because the instance is modified
      // when processing.
      // @see \Drupal\Core\Config\Schema\Mapping::processRequiredKeyFlags()
      $typed_config->create(clone $definition, 2501)->validate();
      $this->fail('Expected an exception but none was raised.');
    }
    catch (UnexpectedTypeException $e) {
      $this->assertSame('Expected argument of type "array", "int" given', $e->getMessage());
    }

    // Empty arrays are valid.
    $this->assertCount(0, $typed_config->create(clone $definition, [])->validate());

    // Indexed arrays are never valid.
    $violations = $typed_config->create(clone $definition, ['north', 'south'])->validate();
    $this->assertCount(1, $violations);
    $this->assertSame('Numerically indexed arrays are not allowed.', (string) $violations->get(0)->getMessage());

    // Arrays with automatically assigned keys, AND a valid key, should be
    // considered invalid overall.
    $violations = $typed_config->create(clone $definition, ['north', 'south' => 'west'])->validate();
    $this->assertCount(1, $violations);
    $this->assertSame("'0' is not a supported key.", (string) $violations->get(0)->getMessage());

    // Associative arrays with an invalid key should be invalid.
    $violations = $typed_config->create(clone $definition, ['north' => 'south', 'east' => 'west'])->validate();
    $this->assertCount(1, $violations);
    $this->assertSame("'east' is not a supported key.", (string) $violations->get(0)->getMessage());

    // If the array only contains the allowed keys, it's fine.
    $value = [
      'north' => 'Boston',
      'south' => 'Atlanta',
      'west' => 'San Francisco',
    ];
    $violations = $typed_config->create(clone $definition, $value)->validate();
    $this->assertCount(0, $violations);

    // If, in the mapping definition, some keys do NOT have
    // `requiredKey: false` set, then they MUST be set. In other
    // words, all keys are required unless they individually
    // specify otherwise.
    // First test without changing the value: no error should occur because all
    // keys passed to the ValidKeys constraint have a value.
    unset($definition['mapping']['south']['requiredKey']);
    unset($definition['mapping']['east']['requiredKey']);
    $violations = $typed_config->create(clone $definition, $value)->validate();
    $this->assertCount(0, $violations);
  }

  /**
   * Tests that valid keys can be inferred from the data definition.
   */
  public function testValidKeyInference(): void {
    // Install the System module and its config so that we can test that the
    // validator infers the allowed keys from a defined schema.
    $this->enableModules(['system']);
    $this->installConfig('system');

    $config = $this->container->get('config.typed')
      ->get('system.site');
    $config->getDataDefinition()
      ->addConstraint('ValidKeys', '<infer>');

    $data = $config->getValue();
    $data['invalid-key'] = "There's a snake in my boots.";
    $config->setValue($data);
    $violations = $config->validate();
    $this->assertCount(1, $violations);
    $this->assertSame("'invalid-key' is not a supported key.", (string) $violations->get(0)->getMessage());

    // Ensure that ValidKeys will freak out if the option is not exactly
    // `<infer>`.
    $config->getDataDefinition()
      ->addConstraint('ValidKeys', 'infer');
    $this->expectExceptionMessage("'infer' is not a valid set of allowed keys.");
    $config->validate();
  }

  /**
   * Asserts a set of validation errors is raised when the config is validated.
   *
   * @param string $config_name
   *   The machine name of the configuration.
   * @param array $config_data
   *   The data associated with the configuration. Note: This configuration
   *   doesn't yet have to be stored.
   * @param array<string, string|string[]> $expected_messages
   *   The expected validation error messages. Keys are property paths, values
   *   are the expected messages: a string if a single message is expected, an
   *   array of strings if multiple are expected.
   */
  protected function assertValidationErrors(string $config_name, array $config_data, array $expected_messages): void {
    $violations = $this->container->get('config.typed')
      ->createFromNameAndData($config_name, $config_data)
      ->validate();

    $actual_messages = [];
    foreach ($violations as $violation) {
      $property_path = $violation->getPropertyPath();

      if (!isset($actual_messages[$property_path])) {
        $actual_messages[$property_path] = (string) $violation->getMessage();
      }
      else {
        // Transform value from string to array.
        if (is_string($actual_messages[$property_path])) {
          $actual_messages[$property_path] = (array) $actual_messages[$violation->getPropertyPath()];
        }
        // And append.
        $actual_messages[$property_path][] = (string) $violation->getMessage();
      }
    }
    ksort($expected_messages);
    ksort($actual_messages);
    $this->assertSame($expected_messages, $actual_messages);
  }

}

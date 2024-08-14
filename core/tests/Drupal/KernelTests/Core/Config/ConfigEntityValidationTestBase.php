<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Config;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Config\Schema\Mapping;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\Core\Entity\Plugin\DataType\ConfigEntityAdapter;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\TypedData\Plugin\DataType\LanguageReference;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\Validation\Plugin\Validation\Constraint\FullyValidatableConstraint;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;

// cspell:ignore kthxbai

/**
 * Base class for testing validation of config entities.
 *
 * @group config
 * @group Validation
 */
abstract class ConfigEntityValidationTestBase extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * The config entity being tested.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityInterface
   */
  protected ConfigEntityInterface $entity;

  /**
   * Whether a config entity of this type has a label.
   *
   * Most config entity types ensure their entities have a label. But a few do
   * not, typically highly abstract/very low level config entities without a
   * strong UI presence. For example: REST resource configuration entities and
   * entity view displays.
   *
   * @var bool
   *
   * @see \Drupal\Core\Entity\EntityInterface::label()
   */
  protected bool $hasLabel = TRUE;

  /**
   * The config entity mapping properties with >=1 required keys.
   *
   * All top-level properties of a config entity are guaranteed to be defined
   * (since they are defined as properties on the corresponding PHP class). That
   * is why they can never trigger "required key" validation errors. Only for
   * non-top-level properties can such validation errors be triggered, and hence
   * that is only possible on top-level properties of `type: mapping`.
   *
   * @var string[]
   * @see \Drupal\Core\Config\Entity\ConfigEntityType::getPropertiesToExport()
   * @see ::testRequiredPropertyKeysMissing()
   * @see \Drupal\Core\Validation\Plugin\Validation\Constraint\ValidKeysConstraintValidator
   */
  protected static array $propertiesWithRequiredKeys = [];

  /**
   * The config entity properties whose values are optional (set to NULL).
   *
   * @var string[]
   * @see \Drupal\Core\Config\Entity\ConfigEntityTypeInterface::getPropertiesToExport()
   * @see ::testRequiredPropertyValuesMissing()
   */
  protected static array $propertiesWithOptionalValues = [
    '_core',
    'third_party_settings',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig('system');

    // Install Stark so we can add a legitimately installed theme to config
    // dependencies.
    $this->container->get('theme_installer')->install(['stark']);
    $this->container = $this->container->get('kernel')->getContainer();
  }

  /**
   * Ensures that the entity created in ::setUp() has no validation errors.
   */
  public function testEntityIsValid(): void {
    $this->assertInstanceOf(ConfigEntityInterface::class, $this->entity);
    $this->assertValidationErrors([]);
  }

  /**
   * Returns the validation constraints applied to the entity's ID.
   *
   * If the entity type does not define an ID key, the test will fail. If an ID
   * key is defined but is not using the `machine_name` data type, the test will
   * be skipped.
   *
   * @return array[]
   *   The validation constraint configuration applied to the entity's ID.
   */
  protected function getMachineNameConstraints(): array {
    $id_key = $this->entity->getEntityType()->getKey('id');
    $this->assertNotEmpty($id_key, "The entity under test does not define an ID key.");

    $data_definition = $this->entity->getTypedData()
      ->get($id_key)
      ->getDataDefinition();
    if ($data_definition->getDataType() === 'machine_name') {
      return $data_definition->getConstraints();
    }
    else {
      $this->markTestSkipped("The entity's ID key does not use the machine_name data type.");
    }
  }

  /**
   * Data provider for ::testInvalidMachineNameCharacters().
   *
   * @return array[]
   *   The test cases.
   */
  public static function providerInvalidMachineNameCharacters(): array {
    return [
      'INVALID: space separated' => ['space separated', FALSE],
      'INVALID: dash separated' => ['dash-separated', FALSE],
      'INVALID: uppercase letters' => ['Uppercase_Letters', FALSE],
      'INVALID: period separated' => ['period.separated', FALSE],
      'VALID: underscore separated' => ['underscore_separated', TRUE],
    ];
  }

  /**
   * Tests that the entity's ID is tested for invalid characters.
   *
   * @param string $machine_name
   *   A machine name to test.
   * @param bool $is_expected_to_be_valid
   *   Whether this machine name is expected to be considered valid.
   *
   * @dataProvider providerInvalidMachineNameCharacters
   */
  public function testInvalidMachineNameCharacters(string $machine_name, bool $is_expected_to_be_valid): void {
    $constraints = $this->getMachineNameConstraints();

    $this->assertNotEmpty($constraints['Regex']);
    $this->assertIsArray($constraints['Regex']);
    $this->assertArrayHasKey('pattern', $constraints['Regex']);
    $this->assertIsString($constraints['Regex']['pattern']);
    $this->assertArrayHasKey('message', $constraints['Regex']);
    $this->assertIsString($constraints['Regex']['message']);

    $id_key = $this->entity->getEntityType()->getKey('id');
    if ($is_expected_to_be_valid) {
      $expected_errors = [];
    }
    else {
      $expected_errors = [$id_key => sprintf('The <em class="placeholder">&quot;%s&quot;</em> machine name is not valid.', $machine_name)];
    }

    // Config entity IDs are immutable by default.
    $expected_errors[''] = "The '$id_key' property cannot be changed.";

    $this->entity->set($id_key, $machine_name);
    $this->assertValidationErrors($expected_errors);
  }

  /**
   * Tests that the entity ID's length is validated if it is a machine name.
   */
  public function testMachineNameLength(): void {
    $constraints = $this->getMachineNameConstraints();

    $max_length = $constraints['Length']['max'];
    $this->assertIsInt($max_length);
    $this->assertGreaterThan(0, $max_length);

    $id_key = $this->entity->getEntityType()->getKey('id');
    $expected_errors = [
      $id_key => 'This value is too long. It should have <em class="placeholder">' . $max_length . '</em> characters or less.',
      // Config entity IDs are immutable by default.
      '' => "The '$id_key' property cannot be changed.",
    ];
    $this->entity->set($id_key, $this->randomMachineName($max_length + 2));
    $this->assertValidationErrors($expected_errors);
  }

  /**
   * Data provider for ::testConfigDependenciesValidation().
   *
   * @return array[]
   *   The test cases.
   */
  public static function providerConfigDependenciesValidation(): array {
    return [
      'valid dependency types' => [
        [
          'config' => ['system.site'],
          'content' => ['node:some-random-uuid'],
          'module' => ['system'],
          'theme' => ['stark'],
        ],
        [],
      ],
      'unknown dependency type' => [
        [
          'fun_stuff' => ['star-trek.deep-space-nine'],
        ],
        [
          'dependencies.fun_stuff' => "'fun_stuff' is not a supported key.",
        ],
      ],
      'empty string in config dependencies' => [
        [
          'config' => [''],
        ],
        [
          'dependencies.config.0' => [
            'This value should not be blank.',
            "The '' config does not exist.",
          ],
        ],
      ],
      'non-existent config dependency' => [
        [
          'config' => ['fake_settings'],
        ],
        [
          'dependencies.config.0' => "The 'fake_settings' config does not exist.",
        ],
      ],
      'empty string in module dependencies' => [
        [
          'module' => [''],
        ],
        [
          'dependencies.module.0' => [
            'This value should not be blank.',
            "Module '' is not installed.",
          ],
        ],
      ],
      'invalid module dependency' => [
        [
          'module' => ['invalid-module-name'],
        ],
        [
          'dependencies.module.0' => [
            'This value is not valid.',
            "Module 'invalid-module-name' is not installed.",
          ],
        ],
      ],
      'non-installed module dependency' => [
        [
          'module' => ['bad_judgment'],
        ],
        [
          'dependencies.module.0' => "Module 'bad_judgment' is not installed.",
        ],
      ],
      'empty string in theme dependencies' => [
        [
          'theme' => [''],
        ],
        [
          'dependencies.theme.0' => [
            'This value should not be blank.',
            "Theme '' is not installed.",
          ],
        ],
      ],
      'invalid theme dependency' => [
        [
          'theme' => ['invalid-theme-name'],
        ],
        [
          'dependencies.theme.0' => [
            'This value is not valid.',
            "Theme 'invalid-theme-name' is not installed.",
          ],
        ],
      ],
      'non-installed theme dependency' => [
        [
          'theme' => ['ugly_theme'],
        ],
        [
          'dependencies.theme.0' => "Theme 'ugly_theme' is not installed.",
        ],
      ],
    ];
  }

  /**
   * Tests validation of config dependencies.
   *
   * @param array[] $dependencies
   *   The dependencies that should be added to the config entity under test.
   * @param array<string, string|string[]> $expected_messages
   *   The expected validation error messages. Keys are property paths, values
   *   are the expected messages: a string if a single message is expected, an
   *   array of strings if multiple are expected.
   *
   * @dataProvider providerConfigDependenciesValidation
   */
  public function testConfigDependenciesValidation(array $dependencies, array $expected_messages): void {
    // Add the dependencies we were given to the dependencies that may already
    // exist in the entity.
    $dependencies = NestedArray::mergeDeep($dependencies, $this->entity->getDependencies());

    $this->entity->set('dependencies', $dependencies);
    $this->assertValidationErrors($expected_messages);

    // Enforce these dependencies, and ensure we get the same results.
    $this->entity->set('dependencies', [
      'enforced' => $dependencies,
    ]);
    // We now expect validation errors not at `dependencies.module.0`, but at
    // `dependencies.enforced.module.0`. So reuse the same messages, but perform
    // string replacement in the keys.
    $expected_enforced_messages = array_combine(
      str_replace('dependencies', 'dependencies.enforced', array_keys($expected_messages)),
      array_values($expected_messages),
    );
    $this->assertValidationErrors($expected_enforced_messages);
  }

  /**
   * Tests validation of config entity's label.
   *
   * @see \Drupal\Core\Entity\EntityInterface::label()
   * @see \Drupal\Core\Entity\EntityBase::label()
   */
  public function testLabelValidation(): void {
    // Some entity types do not have a label.
    if (!$this->hasLabel) {
      $this->markTestSkipped();
    }
    if ($this->entity->getEntityType()->getKey('label') === $this->entity->getEntityType()->getKey('id')) {
      $this->markTestSkipped('This entity type uses the ID as the label; an entity without a label is hence impossible.');
    }

    static::setLabel($this->entity, "Multi\nLine");
    $this->assertValidationErrors([$this->entity->getEntityType()->getKey('label') => "Labels are not allowed to span multiple lines or contain control characters."]);
  }

  /**
   * Sets the label of the given config entity.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $entity
   *   The config entity to modify.
   * @param string $label
   *   The label to set.
   *
   * @see ::testLabelValidation()
   */
  protected static function setLabel(ConfigEntityInterface $entity, string $label): void {
    $label_property = $entity->getEntityType()->getKey('label');
    if ($label_property === FALSE) {
      throw new \LogicException(sprintf('Override %s to allow testing a %s without a label.', __METHOD__, (string) $entity->getEntityType()->getSingularLabel()));
    }

    $entity->set($label_property, $label);
  }

  /**
   * Asserts a set of validation errors is raised when the entity is validated.
   *
   * @param array<string, string|string[]> $expected_messages
   *   The expected validation error messages. Keys are property paths, values
   *   are the expected messages: a string if a single message is expected, an
   *   array of strings if multiple are expected.
   */
  protected function assertValidationErrors(array $expected_messages): void {
    /** @var \Drupal\Core\TypedData\TypedDataManagerInterface $typed_data */
    $typed_data = $this->container->get('typed_data_manager');
    $definition = $typed_data->createDataDefinition('entity:' . $this->entity->getEntityTypeId());
    $violations = $typed_data->create($definition, $this->entity)->validate();

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

  /**
   * Tests that the config entity's langcode is validated.
   */
  public function testLangcode(): void {
    $this->entity->set('langcode', NULL);
    $this->assertValidationErrors([
      'langcode' => 'This value should not be null.',
    ]);

    // A langcode from the standard list should always be acceptable.
    $standard_languages = LanguageManager::getStandardLanguageList();
    $this->assertNotEmpty($standard_languages);
    $this->entity->set('langcode', key($standard_languages));
    $this->assertValidationErrors([]);

    // All special, internal langcodes should be acceptable.
    $system_langcodes = [
      LanguageInterface::LANGCODE_NOT_SPECIFIED,
      LanguageInterface::LANGCODE_NOT_APPLICABLE,
      LanguageInterface::LANGCODE_DEFAULT,
      LanguageInterface::LANGCODE_SITE_DEFAULT,
      LanguageInterface::LANGCODE_SYSTEM,
    ];
    foreach ($system_langcodes as $langcode) {
      $this->entity->set('langcode', $langcode);
      $this->assertValidationErrors([]);
    }

    // An invalid langcode should be unacceptable, even if it "looks" right.
    $fake_langcode = 'definitely-not-a-language';
    $this->assertArrayNotHasKey($fake_langcode, LanguageReference::getAllValidLangcodes());
    $this->entity->set('langcode', $fake_langcode);
    $this->assertValidationErrors([
      'langcode' => 'The value you selected is not a valid choice.',
    ]);

    // If a new configurable language is created with a non-standard langcode,
    // it should be acceptable.
    $this->enableModules(['language']);
    // The language doesn't exist yet, so it shouldn't be a valid choice.
    $this->entity->set('langcode', 'kthxbai');
    $this->assertValidationErrors([
      'langcode' => 'The value you selected is not a valid choice.',
    ]);
    // Once we create the language, it should be a valid choice.
    ConfigurableLanguage::createFromLangcode('kthxbai')->save();
    $this->assertValidationErrors([]);
  }

  /**
   * Tests that immutable properties cannot be changed.
   *
   * @param mixed[] $valid_values
   *   (optional) The values to set for the immutable properties, keyed by name.
   *   This should be used if the immutable properties can only accept certain
   *   values, e.g. valid plugin IDs.
   */
  public function testImmutableProperties(array $valid_values = []): void {
    $constraints = $this->entity->getEntityType()->getConstraints();
    $this->assertNotEmpty($constraints['ImmutableProperties'], 'All config entities should have at least one immutable ID property.');

    foreach ($constraints['ImmutableProperties'] as $property_name) {
      $original_value = $this->entity->get($property_name);
      $this->entity->set($property_name, $valid_values[$property_name] ?? $this->randomMachineName());
      $this->assertValidationErrors([
        '' => "The '$property_name' property cannot be changed.",
      ]);
      $this->entity->set($property_name, $original_value);
    }
  }

  /**
   * A property that is required must have a value (i.e. not NULL).
   *
   * @param string[]|null $additional_expected_validation_errors_when_missing
   *   Some required config entity properties have additional validation
   *   constraints that cause additional messages to appear. Keys must be
   *   config entity properties, values must be arrays as expected by
   *   ::assertValidationErrors().
   *
   * @todo Remove this optional parameter in https://www.drupal.org/project/drupal/issues/2820364#comment-15333069
   *
   * @return void
   */
  public function testRequiredPropertyKeysMissing(?array $additional_expected_validation_errors_when_missing = NULL): void {
    $config_entity_properties = array_keys($this->entity->getEntityType()->getPropertiesToExport());

    if (!empty(array_diff(array_keys($additional_expected_validation_errors_when_missing ?? []), $config_entity_properties))) {
      throw new \LogicException(sprintf('The test %s lists `%s` in $additional_expected_validation_errors_when_missing but it is not a property of the `%s` config entity type.',
        get_called_class(),
        implode(', ', array_diff(array_keys($additional_expected_validation_errors_when_missing), $config_entity_properties)),
        $this->entity->getEntityTypeId(),
      ));
    }

    $mapping_properties = array_keys(array_filter(
      ConfigEntityAdapter::createFromEntity($this->entity)->getProperties(FALSE),
      fn (TypedDataInterface $v) => $v instanceof Mapping
    ));

    $required_property_keys = $this->getRequiredPropertyKeys();
    if (!$this->isFullyValidatable()) {
      $this->assertEmpty($required_property_keys, 'No keys can be required when a config entity type is not fully validatable.');
    }

    $original_entity = clone $this->entity;
    foreach ($mapping_properties as $property) {
      $this->entity = clone $original_entity;
      $this->entity->set($property, []);
      $expected_validation_errors = array_key_exists($property, $required_property_keys)
        ? [$property => $required_property_keys[$property]]
        : [];
      $this->assertValidationErrors(($additional_expected_validation_errors_when_missing[$property] ?? []) + $expected_validation_errors);
    }
  }

  /**
   * A property that is required must have a value (i.e. not NULL).
   *
   * @param string[]|null $additional_expected_validation_errors_when_missing
   *   Some required config entity properties have additional validation
   *   constraints that cause additional messages to appear. Keys must be
   *   config entity properties, values must be arrays as expected by
   *   ::assertValidationErrors().
   *
   * @todo Remove this optional parameter in https://www.drupal.org/project/drupal/issues/2820364#comment-15333069
   *
   * @return void
   */
  public function testRequiredPropertyValuesMissing(?array $additional_expected_validation_errors_when_missing = NULL): void {
    $config_entity_properties = array_keys($this->entity->getEntityType()->getPropertiesToExport());

    // Guide developers when $additional_expected_validation_errors_when_missing
    // does not contain sensible values.
    $non_existing_properties = array_diff(array_keys($additional_expected_validation_errors_when_missing ?? []), $config_entity_properties);
    if ($non_existing_properties) {
      throw new \LogicException(sprintf('The test %s lists `%s` in $additional_expected_validation_errors_when_missing but it is not a property of the `%s` config entity type.',
        __METHOD__,
        implode(', ', $non_existing_properties),
        $this->entity->getEntityTypeId(),
      ));
    }
    $properties_with_optional_values = $this->getPropertiesWithOptionalValues();

    // Get the config entity properties that are immutable.
    // @see ::testImmutableProperties()
    $immutable_properties = $this->entity->getEntityType()->getConstraints()['ImmutableProperties'];

    // Config entity properties containing plugin collections are special cases:
    // setting them to NULL would cause them to get out of sync with the plugin
    // collection.
    // @see \Drupal\Core\Config\Entity\ConfigEntityBase::set()
    // @see \Drupal\Core\Config\Entity\ConfigEntityBase::preSave()
    $plugin_collection_properties = $this->entity instanceof EntityWithPluginCollectionInterface
      ? array_keys($this->entity->getPluginCollections())
      : [];

    // To test properties with missing required values, $this->entity must be
    // modified to be able to use ::assertValidationErrors(). To allow restoring
    // $this->entity to its original value for each tested property, a clone of
    // the original entity is needed.
    $original_entity = clone $this->entity;
    foreach ($config_entity_properties as $property) {
      // Do not try to set immutable properties to NULL: their immutability is
      // already tested.
      // @see ::testImmutableProperties()
      if (in_array($property, $immutable_properties, TRUE)) {
        continue;
      }

      // Do not try to set plugin collection properties to NULL.
      if (in_array($property, $plugin_collection_properties, TRUE)) {
        continue;
      }

      $this->entity = clone $original_entity;
      $this->entity->set($property, NULL);
      $expected_validation_errors = in_array($property, $properties_with_optional_values, TRUE)
        ? []
        : [$property => 'This value should not be null.'];

      // @see `type: required_label`
      // @see \Symfony\Component\Validator\Constraints\NotBlank
      if (!$this->isFullyValidatable() && $this->entity->getEntityType()->getKey('label') == $property) {
        $expected_validation_errors = [$property => 'This value should not be blank.'];
      }

      $this->assertValidationErrors(($additional_expected_validation_errors_when_missing[$property] ?? []) + $expected_validation_errors);
    }
  }

  /**
   * Whether the tested config entity type is fully validatable.
   *
   * @return bool
   *   Whether the tested config entity type is fully validatable.
   */
  protected function isFullyValidatable(): bool {
    $typed_config = $this->container->get('config.typed');
    assert($typed_config instanceof TypedConfigManagerInterface);
    // @see \Drupal\Core\Entity\Plugin\DataType\ConfigEntityAdapter::getConfigTypedData()
    $config_entity_type_schema_constraints = $typed_config
      ->createFromNameAndData(
        $this->entity->getConfigDependencyName(),
        $this->entity->toArray()
      )->getConstraints();

    foreach ($config_entity_type_schema_constraints as $constraint) {
      if ($constraint instanceof FullyValidatableConstraint) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Determines the config entity mapping properties with required keys.
   *
   * This refers only to the top-level properties of the config entity which are expected to be mappings, and of those mappings, only the ones which have required keys.
   *
   * @return string[]
   *   An array of key-value pairs, with:
   *   - keys: names of the config entity properties which are mappings that
   *     contain required keys.
   *   - values: the corresponding expected validation error message.
   */
  protected function getRequiredPropertyKeys(): array {
    // If a config entity type is not fully validatable, no mapping property
    // keys are required.
    if (!$this->isFullyValidatable()) {
      return [];
    }

    $config_entity_properties = array_keys($this->entity->getEntityType()
      ->getPropertiesToExport());

    // Otherwise, all mapping property keys are required except for those marked
    // optional. Rather than inspecting config schema, require authors of tests
    // to explicitly list optional properties in a `propertiesWithRequiredKeys`
    // property on this class.
    // @see \Drupal\KernelTests\Config\Schema\MappingTest::testMappingInterpretation()
    $class = static::class;
    $properties_with_required_keys = [];
    while ($class) {
      if (property_exists($class, 'propertiesWithRequiredKeys')) {
        $properties_with_required_keys += $class::$propertiesWithRequiredKeys;
      }
      $class = get_parent_class($class);
    }

    // Guide developers when $propertiesWithRequiredKeys does not contain
    // sensible values.
    if (!empty(array_diff(array_keys($properties_with_required_keys), $config_entity_properties))) {
      throw new \LogicException(sprintf('The %s test class lists %s in $propertiesWithRequiredKeys but it is not a property of the %s config entity type.',
        get_called_class(),
        implode(', ', array_diff(array_keys($properties_with_required_keys), $config_entity_properties)),
        $this->entity->getEntityTypeId()
      ));
    }

    return $properties_with_required_keys;
  }

  /**
   * Determines the config entity properties with optional values.
   *
   * @return string[]
   *   The config entity properties whose values are optional.
   */
  protected function getPropertiesWithOptionalValues(): array {
    $config_entity_properties = array_keys($this->entity->getEntityType()
      ->getPropertiesToExport());

    // If a config entity type is not fully validatable, all properties are
    // optional, with the exception of `type: langcode` and
    // `type: required_label`.
    if (!$this->isFullyValidatable()) {
      return array_diff($config_entity_properties, [
        // @see `type: langcode`
        // @see \Symfony\Component\Validator\Constraints\NotNull
        'langcode',
        'default_langcode',
        // @see `type: required_label`
        // @see \Symfony\Component\Validator\Constraints\NotBlank
        $this->entity->getEntityType()->getKey('label'),
      ]);
    }

    // Otherwise, all properties are required except for those marked
    // optional. Rather than inspecting config schema, require authors of tests
    // to explicitly list optional properties in a
    // `propertiesWithOptionalValues` property on this class.
    $class = static::class;
    $optional_properties = [];
    while ($class) {
      if (property_exists($class, 'propertiesWithOptionalValues')) {
        $optional_properties = array_merge($optional_properties, $class::$propertiesWithOptionalValues);
      }
      $class = get_parent_class($class);
    }
    $optional_properties = array_unique($optional_properties);

    // Guide developers when $optionalProperties does not contain sensible
    // values.
    $non_existing_properties = array_diff($optional_properties, $config_entity_properties);
    if ($non_existing_properties) {
      throw new \LogicException(sprintf('The %s test class lists %s in $optionalProperties but it is not a property of the %s config entity type.',
        static::class,
        implode(', ', $non_existing_properties),
        $this->entity->getEntityTypeId()
      ));
    }

    return $optional_properties;
  }

}

<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Extension;

use Drupal\Core\Extension\Plugin\Validation\Constraint\ExtensionAvailableConstraint;
use Drupal\Core\Extension\Plugin\Validation\Constraint\ExtensionAvailableConstraintValidator;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the ExtensionAvailable constraint validator.
 */
#[Group('Validation')]
#[CoversClass(ExtensionAvailableConstraint::class)]
#[CoversClass(ExtensionAvailableConstraintValidator::class)]
#[RunTestsInSeparateProcesses]
class ExtensionAvailableConstraintValidatorTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * Tests the ExtensionAvailable constraint validator.
   */
  public function testValidationProfile(): void {
    // Create a data definition that specifies the value must be a string with
    // the name of an available module.
    $definition = DataDefinition::create('string');

    /** @var \Drupal\Core\TypedData\TypedDataManagerInterface $typed_data */
    $typed_data = $this->container->get('typed_data_manager');

    $definition->setConstraints(['ExtensionAvailable' => 'profile']);
    $data = $typed_data->create($definition, 'minimal');

    // Assuming 'minimal' profile is available.
    $violations = $data->validate();
    $this->assertCount(0, $violations);

    // Check an unavailable profile by setting a fake profile name.
    $data->setValue('fake_profile');
    $violations = $data->validate();
    $this->assertCount(1, $violations);
    $this->assertSame("Profile 'fake_profile' is not available.", (string) $violations->get(0)->getMessage());

    // NULL should not trigger a validation error: a value may be nullable.
    $data->setValue(NULL);
    $validate = $data->validate();
    $this->assertCount(0, $validate);
  }

  /**
   * Tests the ExtensionAvailable constraint validator.
   */
  #[DataProvider('dataProvider')]
  public function testValidationModule(array $new_config, ?array $initial_config = [], array $expected_violations = [], bool $testing_environment = TRUE): void {
    if ($testing_environment === FALSE) {
      $reflection = new \ReflectionClass(ExtensionAvailableConstraintValidator::class);
      $reflection->setStaticPropertyValue('inTestEnvironment', FALSE);
    }

    /** @var \Drupal\Core\Config\TypedConfigManager $typed_config */
    $typed_config = $this->container->get('config.typed');
    $config = $this->container->get('config.factory')
      ->getEditable('core.extension');

    foreach ($initial_config as $key => $value) {
      if ($value === '__UNSET__') {
        $config->clear($key);
      }
      else {
        $config->set($key, $value);
      }

      $config->save();

      if ($key === 'profile') {
        // The installation profile is provided by a container parameter. Saving
        // the configuration doesn't automatically trigger invalidation
        $this->container->get('kernel')->rebuildContainer();
      }
    }

    $result = $typed_config->createFromNameAndData('core.extension', $new_config);
    $violations = $result->validate();
    $violationMessages = [];
    foreach ($violations as $violation) {
      $violationMessages[] = (string) $violation->getMessage();
    }

    $this->assertEquals($expected_violations, $violationMessages);

    $expected = count($expected_violations);
    $this->assertCount($expected, $violations, 'Expected violations count matches actual violations count.');
  }

  /**
   * Data provider using yield statements.
   */
  public static function dataProvider(): \Generator {
    yield 'default profile: testing module is not available outside testing' => [
      'new_config' => [
        'module' => [
          'system' => 0,
          'sqlite' => 0,
          'testing' => 0,
        ],
        'theme' => [],
        'profile' => 'minimal',
      ],
      'initial_config' => [],
      'expected_violations' => [
        "Module 'testing' is not available.",
        "The keys of the sequence do not match the given constraints.",
      ],
      'testing_environment' => FALSE,
    ];

    yield 'default profile: can set profile to minimal' => [
      'new_config' => [
        'module' => [
          'system' => 0,
          'sqlite' => 0,
          'user' => 0,
        ],
        'theme' => [],
        'profile' => 'minimal',
      ],
      'initial_config' => [],
      'expected_violations' => [],
      'testing_environment' => FALSE,
    ];

    yield 'null profile to invalid profile' => [
      'new_config' => [
        'module' => [
          'system' => 0,
          'sqlite' => 0,
        ],
        'theme' => [],
        'profile' => 'invalid_profile',
      ],
      'initial_config' => [
        'profile' => NULL,
      ],
      'expected_violations' => [
        "Profile 'invalid_profile' could not be loaded to check if the extension 'system' is available.",
        "Profile 'invalid_profile' could not be loaded to check if the extension 'sqlite' is available.",
        "The keys of the sequence do not match the given constraints.",
        "Profile 'invalid_profile' is not available.",
      ],
      'testing_environment' => FALSE,
    ];

    yield 'null profile to valid profile' => [
      'new_config' => [
        'module' => [
          'system' => 0,
          'sqlite' => 0,
        ],
        'theme' => [],
        'profile' => 'standard',
      ],
      'initial_config' => [
        'profile' => NULL,
      ],
      'expected_violations' => [],
      'testing_environment' => FALSE,
    ];

    yield 'unset profile: to valid profile' => [
      'new_config' => [
        'module' => [
          'system' => 0,
          'sqlite' => 0,
        ],
        'theme' => [],
        'profile' => 'minimal',
      ],
      'initial_config' => [
        'profile' => '__UNSET__',
      ],
      'expected_violations' => [],
      'testing_environment' => FALSE,
    ];

    yield 'unset profile: to invalid profile' => [
      'new_config' => [
        'module' => [
          'system' => 0,
        ],
        'theme' => [],
        'profile' => 'invalid_profile',
      ],
      'initial_config' => [
        'profile' => '__UNSET__',
      ],
      'expected_violations' => [
        "Profile 'invalid_profile' could not be loaded to check if the extension 'system' is available.",
        "The keys of the sequence do not match the given constraints.",
        "Profile 'invalid_profile' is not available.",
      ],
      'testing_environment' => FALSE,
    ];

    yield 'unset profile: module does not exist' => [
      'new_config' => [
        'module' => [
          'system' => 0,
          'sqlite' => 0,
          'testing' => 0,
        ],
        'theme' => [],
      ],
      'initial_config' => [
        'profile' => '__UNSET__',
      ],
      'expected_violations' => [
        "Module 'testing' is not available.",
        "The keys of the sequence do not match the given constraints.",
      ],
      'testing_environment' => FALSE,
    ];

    yield 'unset profile: module does exist' => [
      'new_config' => [
        'module' => [
          'system' => 0,
          'sqlite' => 0,
          'testing' => 0,
        ],
        'theme' => [],
      ],
      'initial_config' => [
        'profile' => '__UNSET__',
      ],
      'expected_violations' => [
        "Module 'testing' is not available.",
        "The keys of the sequence do not match the given constraints.",
      ],
      'testing_environment' => FALSE,
    ];

    yield 'unset profile: testing module does exist if it is same name as a profile' => [
      'new_config' => [
        'module' => [
          'system' => 0,
          'sqlite' => 0,
          'testing' => 0,
        ],
        'theme' => [],
        'profile' => 'testing',
      ],
      'initial_config' => [
        'profile' => '__UNSET__',
      ],
      'expected_violations' => [],
      'testing_environment' => TRUE,
    ];

    yield 'minimal profile: testing module does exist if it is same name as a profile' => [
      'new_config' => [
        'module' => [
          'system' => 0,
          'sqlite' => 0,
          'testing' => 0,
        ],
        'theme' => [],
        'profile' => 'testing',
      ],
      'initial_config' => [
        'profile' => 'minimal',
      ],
      'expected_violations' => [],
      'testing_environment' => TRUE,
    ];
  }

}

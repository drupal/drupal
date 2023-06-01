<?php

namespace Drupal\KernelTests\Core\Config;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\KernelTests\KernelTestBase;

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
   * Data provider for ::testConfigDependenciesValidation().
   *
   * @return array[]
   *   The test cases.
   */
  public function providerConfigDependenciesValidation(): array {
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
          'dependencies' => "'fun_stuff' is not a supported key.",
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
    $this->assertInstanceOf(ConfigEntityInterface::class, $this->entity);

    // The entity should have valid data to begin with.
    $this->assertValidationErrors([]);

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
      if (!isset($actual_messages[$violation->getPropertyPath()])) {
        $actual_messages[$violation->getPropertyPath()] = (string) $violation->getMessage();
      }
      else {
        // Transform value from string to array.
        if (is_string($actual_messages[$violation->getPropertyPath()])) {
          $actual_messages[$violation->getPropertyPath()] = (array) $actual_messages[$violation->getPropertyPath()];
        }
        // And append.
        $actual_messages[$violation->getPropertyPath()][] = (string) $violation->getMessage();
      }
    }
    $this->assertSame($expected_messages, $actual_messages);
  }

}

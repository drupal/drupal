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
          "'fun_stuff' is not a supported key.",
        ],
      ],
      'empty string in config dependencies' => [
        [
          'config' => [''],
        ],
        [
          'This value should not be blank.',
          "The '' config does not exist.",
        ],
      ],
      'non-existent config dependency' => [
        [
          'config' => ['fake_settings'],
        ],
        [
          "The 'fake_settings' config does not exist.",
        ],
      ],
      'empty string in module dependencies' => [
        [
          'module' => [''],
        ],
        [
          'This value should not be blank.',
          "Module '' is not installed.",
        ],
      ],
      'invalid module dependency' => [
        [
          'module' => ['invalid-module-name'],
        ],
        [
          'This value is not valid.',
          "Module 'invalid-module-name' is not installed.",
        ],
      ],
      'non-installed module dependency' => [
        [
          'module' => ['bad_judgment'],
        ],
        [
          "Module 'bad_judgment' is not installed.",
        ],
      ],
      'empty string in theme dependencies' => [
        [
          'theme' => [''],
        ],
        [
          'This value should not be blank.',
          "Theme '' is not installed.",
        ],
      ],
      'invalid theme dependency' => [
        [
          'theme' => ['invalid-theme-name'],
        ],
        [
          'This value is not valid.',
          "Theme 'invalid-theme-name' is not installed.",
        ],
      ],
      'non-installed theme dependency' => [
        [
          'theme' => ['ugly_theme'],
        ],
        [
          "Theme 'ugly_theme' is not installed.",
        ],
      ],
    ];
  }

  /**
   * Tests validation of config dependencies.
   *
   * @param array[] $dependencies
   *   The dependencies that should be added to the config entity under test.
   * @param string[] $expected_messages
   *   The expected constraint violation messages.
   *
   * @dataProvider providerConfigDependenciesValidation
   */
  public function testConfigDependenciesValidation(array $dependencies, array $expected_messages): void {
    $this->assertInstanceOf(ConfigEntityInterface::class, $this->entity);

    // The entity should have valid data to begin with.
    $this->assertValidationErrors([]);

    // Add the dependencies we were given to the dependencies that may already
    // exist in the entity.
    $dependencies = NestedArray::mergeDeep($this->entity->getDependencies(), $dependencies);

    $this->entity->set('dependencies', $dependencies);
    $this->assertValidationErrors($expected_messages);

    // Enforce these dependencies, and ensure we get the same results.
    $this->entity->set('dependencies', [
      'enforced' => $dependencies,
    ]);
    $this->assertValidationErrors($expected_messages);
  }

  /**
   * Asserts a set of validation errors is raised when the entity is validated.
   *
   * @param string[] $expected_messages
   *   The expected validation error messages.
   */
  protected function assertValidationErrors(array $expected_messages): void {
    /** @var \Drupal\Core\TypedData\TypedDataManagerInterface $typed_data */
    $typed_data = $this->container->get('typed_data_manager');
    $definition = $typed_data->createDataDefinition('entity:' . $this->entity->getEntityTypeId());
    $violations = $typed_data->create($definition, $this->entity)->validate();

    $actual_messages = [];
    foreach ($violations as $violation) {
      $actual_messages[] = (string) $violation->getMessage();
    }
    $this->assertSame($expected_messages, $actual_messages);
  }

}

<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Validation;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Config\Plugin\Validation\Constraint\RequiredConfigDependenciesConstraint;
use Drupal\Core\Entity\Plugin\Validation\Constraint\BundleConstraint;
use Drupal\Core\Entity\Plugin\Validation\Constraint\EntityHasFieldConstraint;
use Drupal\Core\Entity\Plugin\Validation\Constraint\EntityTypeConstraint;
use Drupal\Core\Entity\Plugin\Validation\Constraint\ImmutablePropertiesConstraint;
use Drupal\Core\Extension\ExtensionDiscovery;
use Drupal\Core\Extension\Plugin\Validation\Constraint\ExtensionAvailableConstraint;
use Drupal\Core\Extension\Plugin\Validation\Constraint\ExtensionExistsConstraint;
use Drupal\Core\Extension\Plugin\Validation\Constraint\ExtensionNameConstraint;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Plugin\Validation\Constraint\PluginExistsConstraint;
use Drupal\Core\Validation\ConstraintFactory;
use Drupal\Core\Validation\Plugin\Validation\Constraint\AllowedValuesConstraint;
use Drupal\Core\Validation\Plugin\Validation\Constraint\AtLeastOneOfConstraint;
use Drupal\Core\Validation\Plugin\Validation\Constraint\ComplexDataConstraint;
use Drupal\Core\Validation\Plugin\Validation\Constraint\EntityBundleExistsConstraint;
use Drupal\Core\Validation\Plugin\Validation\Constraint\OptionalConstraint;
use Drupal\Core\Validation\Plugin\Validation\Constraint\RequiredConstraint;
use Drupal\Core\Validation\Plugin\Validation\Constraint\SequentiallyConstraint;
use Drupal\Core\Validation\Plugin\Validation\Constraint\ValidKeysConstraint;
use Drupal\file\Plugin\Validation\Constraint\FileExtensionConstraint;
use Drupal\file\Plugin\Validation\Constraint\FileSizeLimitConstraint;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Tests Drupal\Core\Validation\ConstraintFactory.
 */
#[CoversClass(ConstraintFactory::class)]
#[Group('Validation')]
#[RunTestsInSeparateProcesses]
class ConstraintFactoryTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test', 'file'];

  /**
   * Tests create instance.
   */
  public function testCreateInstance(): void {
    $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();

    // If the plugin is a \Symfony\Component\Validator\Constraint, they will be
    // created first.
    $this->assertInstanceOf(Constraint::class, $constraint_manager->create('Uuid', []));

    // If the plugin implements the
    // \Drupal\Core\Plugin\ContainerFactoryPluginInterface, they will be created
    // second.
    $container_factory_plugin = $constraint_manager->create('EntityTestContainerFactoryPlugin', []);
    $this->assertNotInstanceOf(Constraint::class, $container_factory_plugin);
    $this->assertInstanceOf(ContainerFactoryPluginInterface::class, $container_factory_plugin);

    // Plugins that are not a \Symfony\Component\Validator\Constraint or do not
    // implement the \Drupal\Core\Plugin\ContainerFactoryPluginInterface are
    // created last.
    $default_plugin = $constraint_manager->create('EntityTestDefaultPlugin', []);
    $this->assertNotInstanceOf(Constraint::class, $default_plugin);
    $this->assertNotInstanceOf(ContainerFactoryPluginInterface::class, $default_plugin);
    $this->assertInstanceOf(PluginBase::class, $default_plugin);
  }

  /**
   * Tests creating constraint plugins without passing an associative array.
   *
   * There are plugin classes that implement Constraint::getDefaultOption(), and
   * when creating those constraints, it is possible to pass options that are
   * not associative arrays. Doing so is deprecated, so this tests that
   * plugin can still be instantiated this way, for backwards compatibility,
   * while deprecation messages will be triggered.
   */
  #[DataProvider('providerCreateInstanceBackwardsCompatibility')]
  #[IgnoreDeprecations]
  public function testAddConstraintBackwardsCompatibility(string $plugin_id, string $plugin_class, mixed $options, string $expected_property, mixed $expected_value): void {
    $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
    $constraint = $constraint_manager->create($plugin_id, $options);
    $this->assertInstanceOf($plugin_class, $constraint);
    $this->assertSame($expected_value, $constraint->$expected_property);
    $this->expectDeprecation(sprintf('Passing any non-associative-array options to configure constraint plugin "%s" is deprecated in drupal:11.4.0 and will not be supported in drupal:12.0.0. See https://www.drupal.org/node/3554746', $plugin_id));
  }

  /**
   * Data provider for testAddConstraintBackwardsCompatibility().
   */
  public static function providerCreateInstanceBackwardsCompatibility(): iterable {
    // Passing non-associative array options should still instantiate the
    // constraint plugin correctly, but also trigger deprecations.
    yield ['ExtensionName', ExtensionNameConstraint::class, '/.*/', 'pattern', ExtensionDiscovery::PHP_FUNCTION_PATTERN];
    $not_blank = new NotBlank();
    yield ['Sequentially', SequentiallyConstraint::class, [$not_blank], 'constraints', [$not_blank]];
    yield ['Optional', OptionalConstraint::class, [$not_blank], 'constraints', [$not_blank]];
    yield ['Required', RequiredConstraint::class, [$not_blank], 'constraints', [$not_blank]];
    yield ['AtLeastOneOf', AtLeastOneOfConstraint::class, [$not_blank], 'constraints', [$not_blank]];
    yield ['AllowedValues', AllowedValuesConstraint::class, [0, 1], 'choices', [0, 1]];
    yield ['RequiredConfigDependencies', RequiredConfigDependenciesConstraint::class, ['node'], 'entityTypes', ['node']];
    yield ['EntityHasField', EntityHasFieldConstraint::class, 'field_test', 'field_name', 'field_test'];
    yield ['Bundle', BundleConstraint::class, 'article', 'bundle', 'article'];
    yield ['Bundle', BundleConstraint::class, ['article', 'page'], 'bundle', ['article', 'page']];
    yield ['EntityType', EntityTypeConstraint::class, 'node', 'type', 'node'];
    yield [
      'ImmutableProperties',
      ImmutablePropertiesConstraint::class,
      ['test1', 'test2'],
      'properties',
      ['test1', 'test2'],
    ];
    yield ['ExtensionAvailable', ExtensionAvailableConstraint::class, 'module', 'type', 'module'];
    yield ['ExtensionExists', ExtensionExistsConstraint::class, 'module', 'type', 'module'];
    yield ['EntityBundleExists', EntityBundleExistsConstraint::class, 'node', 'entityTypeId', 'node'];
    yield ['ValidKeys', ValidKeysConstraint::class, 'key1', 'allowedKeys', 'key1'];
    yield ['ValidKeys', ValidKeysConstraint::class, ['key1', 'key2'], 'allowedKeys', ['key1', 'key2']];
    yield ['FileExtension', FileExtensionConstraint::class, 'gif|jpg', 'extensions', 'gif|jpg'];
    yield ['FileSizeLimit', FileSizeLimitConstraint::class, 100, 'fileLimit', 100];
  }

  /**
   * Tests for backwards compatibility when creating specific other plugins.
   */
  #[IgnoreDeprecations]
  public function testAdditionalConstraintBackwardsCompatibility(): void {
    $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
    $constraint = $constraint_manager->create('PluginExists', 'entity_type.manager');
    $this->assertInstanceOf(PluginExistsConstraint::class, $constraint);
    $this->assertSame($constraint->pluginManager, \Drupal::entityTypeManager());
    $this->expectDeprecation('Passing any non-associative-array options to configure constraint plugin "PluginExists" is deprecated in drupal:11.4.0 and will not be supported in drupal:12.0.0. See https://www.drupal.org/node/3554746');
    $this->expectDeprecation('Passing the "value" option in configuration to Drupal\Core\Plugin\Plugin\Validation\Constraint\PluginExistsConstraint::create is deprecated in drupal:11.4.0 and will not be supported in drupal:12.0.0. See https://www.drupal.org/node/3554746');

    $constraint = $constraint_manager->create('ComplexData', ['value' => ['NotBlank' => []]]);
    $this->assertInstanceOf(ComplexDataConstraint::class, $constraint);
    $this->assertInstanceOf(NotBlank::class, $constraint->properties['value']['NotBlank']);
  }

}

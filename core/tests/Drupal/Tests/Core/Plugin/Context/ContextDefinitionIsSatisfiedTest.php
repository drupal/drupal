<?php

namespace Drupal\Tests\Core\Plugin\Context;

use Drupal\Core\Cache\NullBackend;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageDefault;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\StringTranslation\TranslationManager;
use Drupal\Core\TypedData\TypedDataManager;
use Drupal\Core\Validation\ConstraintManager;
use Drupal\Tests\Core\Plugin\Fixtures\InheritedContextDefinition;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\Core\Plugin\Context\ContextDefinition
 * @group Plugin
 */
class ContextDefinitionIsSatisfiedTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $namespaces = new \ArrayObject([
      'Drupal\\Core\\TypedData' => $this->root . '/core/lib/Drupal/Core/TypedData',
      'Drupal\\Core\\Validation' => $this->root . '/core/lib/Drupal/Core/Validation',
      'Drupal\\Tests\\Core\\Plugin\\Fixtures' => $this->root . '/core/tests/Drupal/Tests/Core/Plugin/Fixtures',
    ]);
    $cache_backend = new NullBackend('cache');
    $module_handler = $this->prophesize(ModuleHandlerInterface::class);

    $class_resolver = $this->prophesize(ClassResolverInterface::class);
    $class_resolver->getInstanceFromDefinition(Argument::type('string'))->will(function ($arguments) {
      $class_name = $arguments[0];
      return new $class_name();
    });

    $type_data_manager = new TypedDataManager($namespaces, $cache_backend, $module_handler->reveal(), $class_resolver->reveal());
    $type_data_manager->setValidationConstraintManager(new ConstraintManager($namespaces, $cache_backend, $module_handler->reveal()));

    $string_translation = new TranslationManager(new LanguageDefault([]));

    $container = new ContainerBuilder();
    $container->set('typed_data_manager', $type_data_manager);
    $container->set('string_translation', $string_translation);
    \Drupal::setContainer($container);
  }

  /**
   * Tests that context requirements is satisfied as expected.
   *
   * @param bool $expected
   *   The expected outcome.
   * @param \Drupal\Core\Plugin\Context\ContextDefinition $requirement
   *   The requirement to check against.
   * @param \Drupal\Core\Plugin\Context\ContextDefinition $definition
   *   The context definition to check.
   * @param mixed $value
   *   (optional) The value to set on the context, defaults to NULL.
   *
   * @covers ::isSatisfiedBy
   * @covers ::dataTypeMatches
   * @covers ::getSampleValues
   * @covers ::getConstraintObjects
   *
   * @dataProvider providerTestIsSatisfiedBy
   */
  public function testIsSatisfiedBy($expected, ContextDefinition $requirement, ContextDefinition $definition, $value = NULL) {
    $context = new Context($definition, $value);
    $this->assertSame($expected, $requirement->isSatisfiedBy($context));
  }

  /**
   * Provides test data for ::testIsSatisfiedBy().
   */
  public function providerTestIsSatisfiedBy() {
    $data = [];

    // Simple data types.
    $data['both any'] = [
      TRUE,
      new ContextDefinition('any'),
      new ContextDefinition('any'),
    ];
    $data['requirement any'] = [
      TRUE,
      new ContextDefinition('any'),
      new ContextDefinition('integer'),
    ];
    $data['integer, out of range'] = [
      FALSE,
      (new ContextDefinition('integer'))->addConstraint('Range', ['min' => 0, 'max' => 10]),
      new ContextDefinition('integer'),
      20,
    ];
    $data['integer, within range'] = [
      TRUE,
      (new ContextDefinition('integer'))->addConstraint('Range', ['min' => 0, 'max' => 10]),
      new ContextDefinition('integer'),
      5,
    ];
    $data['integer, no value'] = [
      TRUE,
      (new ContextDefinition('integer'))->addConstraint('Range', ['min' => 0, 'max' => 10]),
      new ContextDefinition('integer'),
    ];
    $data['non-integer, within range'] = [
      FALSE,
      (new ContextDefinition('integer'))->addConstraint('Range', ['min' => 0, 'max' => 10]),
      new ContextDefinition('any'),
      5,
    ];
    // Inherited context definition class.
    $data['both any, inherited context requirement definition'] = [
      TRUE,
      new InheritedContextDefinition('any'),
      new ContextDefinition('any'),
    ];
    $data['specific definition, generic requirement'] = [
      TRUE,
      new ContextDefinition('test_data_type'),
      new ContextDefinition('test_data_type:a_variant'),
    ];
    $data['generic definition, specific requirement'] = [
      FALSE,
      new ContextDefinition('test_data_type:a_variant'),
      new ContextDefinition('test_data_type'),
    ];

    return $data;
  }

}

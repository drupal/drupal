<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Plugin;

use Drupal\Component\Plugin\FallbackPluginManagerInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Action\ActionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\KernelTests\KernelTestBase;
use Drupal\system\MenuInterface;

/**
 * @group Plugin
 * @group Validation
 *
 * @covers \Drupal\Core\Plugin\Plugin\Validation\Constraint\PluginExistsConstraint
 * @covers \Drupal\Core\Plugin\Plugin\Validation\Constraint\PluginExistsConstraintValidator
 */
class PluginExistsConstraintValidatorTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['action_test', 'system'];

  /**
   * Tests validation of plugin existence.
   */
  public function testValidation(): void {
    $definition = DataDefinition::create('string')
      ->addConstraint('PluginExists', 'plugin.manager.action');

    // An existing action plugin should pass validation.
    $data = $this->container->get('typed_data_manager')->create($definition);
    $data->setValue('action_test_save_entity');
    $this->assertCount(0, $data->validate());

    // It should also pass validation if we check for an interface it actually
    // implements.
    $definition->setConstraints([
      'PluginExists' => [
        'manager' => 'plugin.manager.action',
        'interface' => ActionInterface::class,
      ],
    ]);
    $this->assertCount(0, $data->validate());

    // A non-existent plugin should be invalid, regardless of interface.
    $data->setValue('non_existent_plugin');
    $violations = $data->validate();
    $this->assertCount(1, $violations);
    $this->assertSame("The 'non_existent_plugin' plugin does not exist.", (string) $violations->get(0)->getMessage());

    // An existing plugin that doesn't implement the specified interface should
    // raise an error.
    $definition->setConstraints([
      'PluginExists' => [
        'manager' => 'plugin.manager.action',
        'interface' => MenuInterface::class,
      ],
    ]);
    $data->setValue('action_test_save_entity');
    $violations = $data->validate();
    $this->assertCount(1, $violations);
    $this->assertSame("The 'action_test_save_entity' plugin must implement or extend " . MenuInterface::class . '.', (string) $violations->get(0)->getMessage());

    // No validation is attempted on a NULL value.
    $data->setValue(NULL);
    $violations = $data->validate();
    $this->assertCount(0, $violations);
  }

  /**
   * Tests that fallback plugin IDs can be considered valid or invalid.
   */
  public function testFallbackPluginIds(): void {
    $plugin_manager = $this->prophesize(PluginManagerInterface::class)
      ->willImplement(FallbackPluginManagerInterface::class);
    $plugin_manager->getFallbackPluginId('non_existent')
      ->shouldBeCalledOnce()
      ->willReturn('broken');
    $plugin_manager->getDefinition('non_existent', FALSE)
      ->shouldBeCalled()
      ->willReturn(NULL);
    $plugin_manager->getDefinition('broken', FALSE)
      ->shouldBeCalled()
      ->willReturn(['id' => 'broken']);
    $this->container->set('plugin.manager.test_fallback', $plugin_manager->reveal());

    // If fallback plugin IDs are allowed, then an invalid plugin ID should not
    // raise an error.
    $definition = DataDefinition::create('string')
      ->addConstraint('PluginExists', [
        'manager' => 'plugin.manager.test_fallback',
        'allowFallback' => TRUE,
      ]);
    $data = $this->container->get('typed_data_manager')->create($definition);
    $data->setValue('non_existent');
    $this->assertCount(0, $data->validate());

    // If fallback plugin IDs are not considered valid (the default behavior),
    // then we should get a validation error.
    $definition->addConstraint('PluginExists', [
      'manager' => 'plugin.manager.test_fallback',
    ]);
    $violations = $data->validate();
    $this->assertCount(1, $violations);
    $this->assertSame("The 'non_existent' plugin does not exist.", (string) $violations->get(0)->getMessage());
  }

}

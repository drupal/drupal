<?php

namespace Drupal\KernelTests\Core\Validation;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\Validator\Constraint;

/**
 * @coversDefaultClass \Drupal\Core\Validation\ConstraintFactory
 *
 * @group Validation
 */
class ConstraintFactoryTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test'];

  /**
   * @covers ::createInstance
   */
  public function testCreateInstance() {
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

}

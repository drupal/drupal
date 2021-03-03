<?php

namespace Drupal\entity_test\Plugin\Validation\Constraint;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A dummy constraint for testing \Drupal\Core\Validation\ConstraintFactory.
 *
 * @Constraint(
 *   id = "EntityTestContainerFactoryPlugin",
 *   label = @Translation("Constraint that implements ContainerFactoryPluginInterface."),
 *   type = "entity"
 * )
 */
class EntityTestContainerFactoryPlugin extends PluginBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
    );
  }

}

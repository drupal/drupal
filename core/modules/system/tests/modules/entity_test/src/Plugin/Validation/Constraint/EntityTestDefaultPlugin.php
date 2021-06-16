<?php

namespace Drupal\entity_test\Plugin\Validation\Constraint;

use Drupal\Component\Plugin\PluginBase;

/**
 * A dummy constraint for testing \Drupal\Core\Validation\ConstraintFactory.
 *
 * @Constraint(
 *   id = "EntityTestDefaultPlugin",
 *   label = @Translation("Constraint that extends PluginBase."),
 *   type = "entity"
 * )
 */
class EntityTestDefaultPlugin extends PluginBase {}

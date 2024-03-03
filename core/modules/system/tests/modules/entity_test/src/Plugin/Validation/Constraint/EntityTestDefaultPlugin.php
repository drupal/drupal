<?php

namespace Drupal\entity_test\Plugin\Validation\Constraint;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;

/**
 * A dummy constraint for testing \Drupal\Core\Validation\ConstraintFactory.
 */
#[Constraint(
  id: 'EntityTestDefaultPlugin',
  label: new TranslatableMarkup('Constraint that extends PluginBase.'),
  type: 'entity'
)]
class EntityTestDefaultPlugin extends PluginBase {}

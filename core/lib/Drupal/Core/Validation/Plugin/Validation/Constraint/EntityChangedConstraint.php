<?php

/**
 * @file
 * Contains \Drupal\Core\Validation\Plugin\Validation\Constraint\EntityChangedConstraint.
 */

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Symfony\Component\Validator\Constraint;

/**
 * Validation constraint for the entity changed timestamp.
 *
 * @Plugin(
 *   id = "EntityChanged",
 *   label = @Translation("Entity changed", context = "Validation"),
 *   type = {"entity"}
 * )
 */
class EntityChangedConstraint extends Constraint {

  public $message = 'The content has either been modified by another user, or you have already submitted modifications. As a result, your changes cannot be saved.';
}

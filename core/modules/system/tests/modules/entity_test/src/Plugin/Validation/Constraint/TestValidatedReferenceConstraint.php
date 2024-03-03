<?php

namespace Drupal\entity_test\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Validates referenced entities.
 */
#[Constraint(
  id: 'TestValidatedReferenceConstraint',
  label: new TranslatableMarkup('Test validated reference constraint.')
)]
class TestValidatedReferenceConstraint extends SymfonyConstraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = 'Invalid referenced entity.';

}

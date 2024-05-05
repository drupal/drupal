<?php

namespace Drupal\Core\Entity\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Checks if a value is an entity that has a specific field.
 */
#[Constraint(
  id: 'EntityHasField',
  label: new TranslatableMarkup('Entity has field', [], ['context' => 'Validation']),
  type: ['entity']
)]
class EntityHasFieldConstraint extends SymfonyConstraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = 'The entity must have the %field_name field.';

  /**
   * The violation message for non-fieldable entities.
   *
   * @var string
   */
  public $notFieldableMessage = 'The entity does not support fields.';

  /**
   * The field name option.
   *
   * @var string
   */
  // phpcs:ignore Drupal.NamingConventions.ValidVariableName.LowerCamelName
  public $field_name;

  /**
   * {@inheritdoc}
   *
   * @return ?string
   *   Name of the default option.
   *
   * @todo Add method return type declaration.
   * @see https://www.drupal.org/project/drupal/issues/3425150
   */
  public function getDefaultOption() {
    return 'field_name';
  }

  /**
   * {@inheritdoc}
   *
   * @return array
   *   The names of the required options.
   *
   * @todo Add method return type declaration.
   * @see https://www.drupal.org/project/drupal/issues/3425150
   */
  public function getRequiredOptions() {
    return (array) $this->getDefaultOption();
  }

}

<?php

declare(strict_types = 1);

namespace Drupal\Core\Entity\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Checks if config entity properties have been changed.
 */
#[Constraint(
  id: 'ImmutableProperties',
  label: new TranslatableMarkup('Properties are unchanged', [], ['context' => 'Validation']),
  type: ['entity']
)]
class ImmutablePropertiesConstraint extends SymfonyConstraint {

  /**
   * The error message if an immutable property has been changed.
   *
   * @var string
   */
  public string $message = "The '@name' property cannot be changed.";

  /**
   * The names of the immutable properties.
   *
   * @var string[]
   */
  public array $properties = [];

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
    return 'properties';
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
    return ['properties'];
  }

}

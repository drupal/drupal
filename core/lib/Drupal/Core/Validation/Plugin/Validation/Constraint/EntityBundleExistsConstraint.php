<?php

declare(strict_types=1);

namespace Drupal\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Checks if a bundle exists on a certain content entity type.
 *
 * This differs from the `Bundle` constraint in that checks that the validated
 * value is the *name of a bundle* of a particular entity type.
 */
#[Constraint(
  id: 'EntityBundleExists',
  label: new TranslatableMarkup('Entity bundle exists', [], ['context' => 'Validation']),
  type: 'entity'
)]
class EntityBundleExistsConstraint extends SymfonyConstraint {

  /**
   * The error message if validation fails.
   *
   * @var string
   */
  public $message = "The '@bundle' bundle does not exist on the '@entity_type_id' entity type.";

  /**
   * The entity type ID which should have the given bundle.
   *
   * This can contain variable values (e.g., `%parent`) that will be replaced.
   *
   * @see \Drupal\Core\Config\Schema\TypeResolver::replaceVariable()
   *
   * @var string
   */
  public string $entityTypeId;

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
    return 'entityTypeId';
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
    return ['entityTypeId'];
  }

}

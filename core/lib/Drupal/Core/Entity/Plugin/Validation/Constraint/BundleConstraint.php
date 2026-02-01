<?php

namespace Drupal\Core\Entity\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Checks if a value is a valid entity type.
 *
 * This differs from the `EntityBundleExists` constraint in that checks that the
 * validated value is an *entity* of a particular bundle.
 */
#[Constraint(
  id: 'Bundle',
  label: new TranslatableMarkup('Bundle', [], ['context' => 'Validation']),
  type: ['entity', 'entity_reference']
)]
class BundleConstraint extends SymfonyConstraint {

  /**
   * The bundle option.
   *
   * @var string|array
   */
  public $bundle;

  #[HasNamedArguments]
  public function __construct(
    mixed $options = NULL,
    string|array|null $bundle = NULL,
    public $message = 'The entity must be of bundle %bundle.',
    ?array $groups = NULL,
    mixed $payload = NULL,
  ) {
    parent::__construct($options, $groups, $payload);
    $this->bundle = $bundle ?? $this->bundle;
  }

  /**
   * Gets the bundle option as array.
   *
   * @return array
   *   An array of bundle options.
   */
  public function getBundleOption() {
    // Support passing the bundle as string, but force it to be an array.
    if (!is_array($this->bundle)) {
      $this->bundle = [$this->bundle];
    }
    return $this->bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOption(): ?string {
    return 'bundle';
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredOptions(): array {
    return ['bundle'];
  }

}

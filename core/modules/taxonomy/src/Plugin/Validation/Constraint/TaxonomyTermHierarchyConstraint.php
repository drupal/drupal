<?php

namespace Drupal\taxonomy\Plugin\Validation\Constraint;

use Drupal\Core\Entity\Plugin\Validation\Constraint\CompositeConstraintBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Attribute\HasNamedArguments;

/**
 * Validation constraint for changing the term hierarchy in pending revisions.
 */
#[Constraint(
  id: 'TaxonomyHierarchy',
  label: new TranslatableMarkup('Taxonomy term hierarchy', [], ['context' => 'Validation'])
)]
class TaxonomyTermHierarchyConstraint extends CompositeConstraintBase {

  #[HasNamedArguments]
  public function __construct(
    mixed $options = NULL,
    public string $message = 'You can only change the hierarchy for the <em>published</em> version of this term.',
    ?array $groups = NULL,
    mixed $payload = NULL,
  ) {
    parent::__construct($options, $groups, $payload);
  }

  /**
   * {@inheritdoc}
   */
  public function coversFields() {
    return ['parent', 'weight'];
  }

}

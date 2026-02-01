<?php

namespace Drupal\menu_link_content\Plugin\Validation\Constraint;

use Drupal\Core\Entity\Plugin\Validation\Constraint\CompositeConstraintBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Attribute\HasNamedArguments;

/**
 * Validation constraint for changing the menu hierarchy in pending revisions.
 */
#[Constraint(
  id: 'MenuTreeHierarchy',
  label: new TranslatableMarkup('Menu tree hierarchy.', [], ['context' => 'Validation'])
)]
class MenuTreeHierarchyConstraint extends CompositeConstraintBase {

  #[HasNamedArguments]
  public function __construct(
    mixed $options = NULL,
    public $message = 'You can only change the hierarchy for the <em>published</em> version of this menu link.',
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

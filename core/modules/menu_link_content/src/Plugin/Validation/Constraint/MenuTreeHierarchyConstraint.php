<?php

namespace Drupal\menu_link_content\Plugin\Validation\Constraint;

use Drupal\Core\Entity\Plugin\Validation\Constraint\CompositeConstraintBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;

/**
 * Validation constraint for changing the menu hierarchy in pending revisions.
 */
#[Constraint(
  id: 'MenuTreeHierarchy',
  label: new TranslatableMarkup('Menu tree hierarchy.', [], ['context' => 'Validation'])
)]
class MenuTreeHierarchyConstraint extends CompositeConstraintBase {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = 'You can only change the hierarchy for the <em>published</em> version of this menu link.';

  /**
   * {@inheritdoc}
   */
  public function coversFields() {
    return ['parent', 'weight'];
  }

}

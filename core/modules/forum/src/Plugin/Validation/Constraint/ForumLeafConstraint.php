<?php

namespace Drupal\forum\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Checks that the node is assigned only a "leaf" term in the forum taxonomy.
 */
#[Constraint(
  id: 'ForumLeaf',
  label: new TranslatableMarkup('Forum leaf', [], ['context' => 'Validation'])
)]
class ForumLeafConstraint extends SymfonyConstraint {

  public $selectForum = 'Select a forum.';
  public $noLeafMessage = 'The item %forum is a forum container, not a forum. Select one of the forums below instead.';

}

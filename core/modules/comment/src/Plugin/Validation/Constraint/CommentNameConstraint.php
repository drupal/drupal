<?php

namespace Drupal\comment\Plugin\Validation\Constraint;

use Drupal\Core\Entity\Plugin\Validation\Constraint\CompositeConstraintBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Attribute\HasNamedArguments;

/**
 * Supports validating comment author names.
 */
#[Constraint(
  id: 'CommentName',
  label: new TranslatableMarkup('Comment author name', [], ['context' => 'Validation']),
  type: 'entity:comment'
)]
class CommentNameConstraint extends CompositeConstraintBase {

  #[HasNamedArguments]
  public function __construct(
    mixed $options = NULL,
    public $messageNameTaken = 'The name you used (%name) belongs to a registered user.',
    public $messageRequired = 'You have to specify a valid author.',
    public $messageMatch = 'The specified author name does not match the comment author.',
    ?array $groups = NULL,
    mixed $payload = NULL,
  ) {
    parent::__construct($options, $groups, $payload);
  }

  /**
   * {@inheritdoc}
   */
  public function coversFields() {
    return ['name', 'uid'];
  }

}

<?php

namespace Drupal\link\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Defines a protocol validation constraint for links to broken internal URLs.
 */
#[Constraint(
  id: 'LinkNotExistingInternal',
  label: new TranslatableMarkup('No broken internal links', [], ['context' => 'Validation'])
)]
class LinkNotExistingInternalConstraint extends SymfonyConstraint {

  #[HasNamedArguments]
  public function __construct(
    mixed $options = NULL,
    public string $message = '',
    public string $notFoundMessage = "The URL '@uri' doesn't exist.",
    public string $invalidParameterMessage = "The URL '@uri' has an invalid parameter.",
    public string $missingParameterMessage = "The URL '@uri' is missing a required parameter.",
    ?array $groups = NULL,
    mixed $payload = NULL,
  ) {
    if ($message !== '') {
      @trigger_error('Passing the $message argument to ' . __METHOD__ . '() is deprecated in drupal:11.5.0 and is removed from drupal:12.0.0. Use the $notFoundMessage argument instead. See https://www.drupal.org/node/3614626', E_USER_DEPRECATED);
      if ($this->notFoundMessage === "The URL '@uri' doesn't exist.") {
        $this->notFoundMessage = $message;
      }
    }
    parent::__construct($options, $groups, $payload);
  }

}

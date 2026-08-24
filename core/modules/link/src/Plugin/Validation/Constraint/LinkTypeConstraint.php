<?php

namespace Drupal\link\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Validation constraint for links receiving data allowed by its settings.
 */
#[Constraint(
  id: 'LinkType',
  label: new TranslatableMarkup('Link data valid for link type.', [], ['context' => 'Validation'])
)]
class LinkTypeConstraint extends SymfonyConstraint {

  #[HasNamedArguments]
  public function __construct(
    mixed $options = NULL,
    public string $message = '',
    public string $invalidMessage = "The URL '@uri' is invalid.",
    public string $onlyInternalMessage = "The URL '@uri' is external, but the @field-label field only supports internal paths.",
    public string $onlyExternalMessage = "The URL '@uri' is internal, but the @field-label field only supports external URLs.",
    ?array $groups = NULL,
    mixed $payload = NULL,
  ) {
    if ($message !== '') {
      @trigger_error('Passing the $message argument to ' . __METHOD__ . '() is deprecated in drupal:11.5.0 and is removed from drupal:12.0.0. Use the $invalidMessage argument instead. See https://www.drupal.org/node/3614626', E_USER_DEPRECATED);
      if ($this->invalidMessage === "The URL '@uri' doesn't exist.") {
        $this->invalidMessage = $message;
      }
    }
    parent::__construct($options, $groups, $payload);
  }

}

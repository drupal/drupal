<?php

namespace Drupal\media\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Checks if a value represents a valid oEmbed resource URL.
 *
 * @internal
 *   This is an internal part of the oEmbed system and should only be used by
 *   oEmbed-related code in Drupal core.
 */
#[Constraint(
  id: 'oembed_resource',
  label: new TranslatableMarkup('oEmbed resource', [], ['context' => 'Validation']),
  type: ['link', 'string', 'string_long']
)]
class OEmbedResourceConstraint extends SymfonyConstraint {

  #[HasNamedArguments]
  public function __construct(
    mixed $options = NULL,
    public $unknownProviderMessage = 'The given URL does not match any known oEmbed providers.',
    public $disallowedProviderMessage = 'Sorry, the @name provider is not allowed.',
    public $invalidResourceMessage = 'The provided URL does not represent a valid oEmbed resource.',
    public $providerErrorMessage = 'An error occurred while trying to retrieve the oEmbed provider database.',
    ?array $groups = NULL,
    mixed $payload = NULL,
  ) {
    parent::__construct($options, $groups, $payload);
  }

}

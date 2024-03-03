<?php

namespace Drupal\media\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
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

  /**
   * The error message if the URL does not match any known provider.
   *
   * @var string
   */
  public $unknownProviderMessage = 'The given URL does not match any known oEmbed providers.';

  /**
   * The error message if the URL matches a disallowed provider.
   *
   * @var string
   */
  public $disallowedProviderMessage = 'Sorry, the @name provider is not allowed.';

  /**
   * The error message if the URL is not a valid oEmbed resource.
   *
   * @var string
   */
  public $invalidResourceMessage = 'The provided URL does not represent a valid oEmbed resource.';

  /**
   * The error message if an unexpected behavior occurs.
   *
   * @var string
   */
  public $providerErrorMessage = 'An error occurred while trying to retrieve the oEmbed provider database.';

}

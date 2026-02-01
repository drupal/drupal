<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * The CKEditor 5 plugin settings.
 *
 * @internal
 */
#[Constraint(
  id: 'CKEditor5EnabledConfigurablePlugins',
  label: new TranslatableMarkup('CKEditor 5 enabled configurable plugins', [], ['context' => 'Validation'])
)]
class EnabledConfigurablePluginsConstraint extends SymfonyConstraint {

  #[HasNamedArguments]
  public function __construct(
    mixed $options = NULL,
    public $message = 'Configuration for the enabled plugin "%plugin_label" (%plugin_id) is missing.',
    ?array $groups = NULL,
    mixed $payload = NULL,
  ) {
    parent::__construct($options, $groups, $payload);
  }

}

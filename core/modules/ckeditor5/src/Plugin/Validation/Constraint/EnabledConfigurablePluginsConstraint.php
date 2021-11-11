<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * The CKEditor 5 plugin settings.
 *
 * @Constraint(
 *   id = "CKEditor5EnabledConfigurablePlugins",
 *   label = @Translation("CKEditor 5 enabled configurable plugins", context = "Validation"),
 * )
 *
 * @internal
 */
class EnabledConfigurablePluginsConstraint extends Constraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = 'Configuration for the enabled plugin "%plugin_label" (%plugin_id) is missing.';

}

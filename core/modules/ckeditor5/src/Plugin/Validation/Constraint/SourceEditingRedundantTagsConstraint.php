<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * For disallowing Source Editing elements already supported by a plugin.
 *
 * @Constraint(
 *   id = "SourceEditingRedundantTags",
 *   label = @Translation("Source editing should only use otherwise unavailable tags and attributes", context = "Validation"),
 * )
 *
 * @internal
 */
class SourceEditingRedundantTagsConstraint extends Constraint {

  /**
   * When a Source Editing element is added that an enabled plugin supports.
   *
   * @var string
   */
  public $enabledPluginsMessage = 'The following @element_type(s) are already supported by enabled plugins and should not be added to the Source Editing "Manually editable HTML tags" field: %overlapping_tags.';

  /**
   * When a Source Editing element is added that a disabled plugin supports.
   *
   * @var string
   */
  public $availablePluginsMessage = 'The following @element_type(s) are already supported by available plugins and should not be added to the Source Editing "Manually editable HTML tags" field. Instead, enable the following plugins to support these @element_types: %overlapping_tags.';

}

<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Ensure CKEditor 5 media plugin's and media filter's settings are in sync.
 *
 * @internal
 */
#[Constraint(
  id: 'CKEditor5MediaAndFilterSettingsInSync',
  label: new TranslatableMarkup('CKEditor 5 Media plugin in sync with filter settings', [], ['context' => 'Validation'])
)]
class CKEditor5MediaAndFilterSettingsInSyncConstraint extends SymfonyConstraint {

  /**
   * The default violation message.
   *
   * @var string
   */
  public $message = 'The CKEditor 5 "%cke5_media_plugin_label" plugin\'s "%cke5_allow_view_mode_override_label" setting should be in sync with the "%filter_media_plugin_label" filter\'s "%filter_media_allowed_view_modes_label" setting: when checked, two or more view modes must be allowed by the filter.';

}

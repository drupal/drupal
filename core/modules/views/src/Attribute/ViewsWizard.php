<?php

namespace Drupal\views\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a Plugin attribute object for views wizard plugins.
 *
 * @see \Drupal\views\Plugin\views\wizard\WizardPluginBase
 * @see \Drupal\views\Plugin\views\wizard\WizardInterface
 *
 * @ingroup views_wizard_plugins
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class ViewsWizard extends Plugin {

  /**
   * Constructs an ViewsWizard attribute.
   *
   * @param string $id
   *   The plugin ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $title
   *   The plugin title used in the views UI.
   * @param string|null $base_table
   *   (optional) The base table on which this wizard is used. The base_table is
   *   required when a deriver class is not defined.
   * @param class-string|null $deriver
   *   (optional) The deriver class.
   */
  public function __construct(
    public readonly string $id,
    public readonly TranslatableMarkup $title,
    public readonly ?string $base_table = NULL,
    public readonly ?string $deriver = NULL,
  ) {}

}

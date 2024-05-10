<?php

namespace Drupal\Core\Display\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a display variant attribute object.
 *
 * Display variants are used to dictate the output of a given Display, which
 * can be used to control the output of many parts of Drupal.
 *
 * Variants are usually chosen by some selection criteria, and are instantiated
 * directly. Each variant must define its own approach to rendering, and can
 * either load its own data or be injected with data from another Display
 * object.
 *
 * Plugin namespace: Plugin\DisplayVariant
 *
 * For working examples, see
 * - \Drupal\Core\Render\Plugin\DisplayVariant\SimplePageVariant
 * - \Drupal\block\Plugin\DisplayVariant\BlockPageVariant
 *
 * @see \Drupal\Core\Display\VariantInterface
 * @see \Drupal\Core\Display\VariantBase
 * @see \Drupal\Core\Display\VariantManager
 * @see \Drupal\Core\Display\PageVariantInterface
 * @see plugin_api
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class DisplayVariant extends Plugin {

  /**
   * Constructs a DisplayVariant plugin attribute object.
   *
   * @param string $id
   *   The plugin ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $admin_label
   *   The administrative label.
   * @param class-string|null $deriver
   *   (optional) The deriver class.
   */
  public function __construct(
    public readonly string $id,
    public readonly TranslatableMarkup $admin_label,
    public readonly ?string $deriver = NULL,
  ) {}

}

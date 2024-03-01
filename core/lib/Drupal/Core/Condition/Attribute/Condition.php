<?php

declare(strict_types=1);

namespace Drupal\Core\Condition\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a condition plugin attribute.
 *
 * Condition plugins provide generalized conditions for use in other
 * operations, such as conditional block placement.
 *
 * Plugin Namespace: Plugin\Condition
 *
 * For a working example, see \Drupal\user\Plugin\Condition\UserRole.
 *
 * @see \Drupal\Core\Condition\ConditionManager
 * @see \Drupal\Core\Condition\ConditionInterface
 * @see \Drupal\Core\Condition\ConditionPluginBase
 * @see block_api
 *
 * @ingroup plugin_api
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class Condition extends Plugin {

  /**
   * Constructs a Condition attribute.
   *
   * @param string $id
   *   The plugin ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $label
   *   (optional) The human-readable name of the condition.
   * @param string|null $module
   *   (optional) The name of the module providing the type.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $category
   *   (optional) The category under which the condition should be listed in the
   *   UI.
   * @param array $context_definitions
   *   (optional) An array of context definitions describing the context used by
   *   the plugin.
   * @param class-string|null $deriver
   *   (optional) The deriver class.
   */
  public function __construct(
    public readonly string $id,
    public readonly ?TranslatableMarkup $label = NULL,
    public readonly ?string $module = NULL,
    public readonly ?TranslatableMarkup $category = NULL,
    public readonly array $context_definitions = [],
    public readonly ?string $deriver = NULL,
  ) {}

}

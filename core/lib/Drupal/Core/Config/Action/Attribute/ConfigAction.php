<?php

declare(strict_types=1);

namespace Drupal\Core\Config\Action\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a ConfigAction attribute object.
 *
 * Plugin Namespace: Plugin\ConfigAction
 *
 * @ingroup config_action_api
 *
 * @internal
 *   This API is experimental.
 *
 * @see \Drupal\Core\Config\Action\ConfigActionPluginInterface
 * @see \Drupal\Core\Config\Action\ConfigActionManager
 * @see plugin_api
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class ConfigAction extends Plugin {

  /**
   * Constructs a ConfigAction attribute.
   *
   * @param string $id
   *   The plugin ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $admin_label
   *   The administrative label of the config action. This is optional when
   *   using a deriver, but in that case the deriver should add an admin label.
   * @param string[] $entity_types
   *   (optional) Allows action shorthand IDs for the listed config entity
   *   types. If '*' is present in the array then it can apply to all entity
   *   types. An empty array means that shorthand action IDs are not available
   *   for this plugin. See ConfigActionManager::convertActionToPluginId().
   * @param class-string|null $deriver
   *   (optional) The deriver class.
   *
   * @see \Drupal\Core\Config\Action\ConfigActionManager::convertActionToPluginId()
   */
  public function __construct(
    public readonly string $id,
    public readonly ?TranslatableMarkup $admin_label = NULL,
    public readonly array $entity_types = [],
    public readonly ?string $deriver = NULL,
  ) {
    if ($this->admin_label === NULL && $this->deriver === NULL) {
      throw new InvalidPluginDefinitionException($id, sprintf("The '%s' config action plugin must have either an admin label or a deriver", $id));
    }
  }

}

<?php

namespace Drupal\ckeditor5\Plugin\ConfigAction;

use Drupal\ckeditor5\Plugin\CKEditor5PluginManagerInterface;
use Drupal\Core\Config\Action\Attribute\ConfigAction;
use Drupal\Core\Config\Action\ConfigActionException;
use Drupal\Core\Config\Action\ConfigActionPluginInterface;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\editor\EditorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Config action plugin to add an item to the toolbar.
 *
 * @internal
 *   This API is experimental.
 */
#[ConfigAction(
  id: 'editor:addItemToToolbar',
  admin_label: new TranslatableMarkup('Add an item to a CKEditor 5 toolbar'),
  entity_types: ['editor'],
)]
final class AddItemToToolbar implements ConfigActionPluginInterface, ContainerFactoryPluginInterface {

  public function __construct(
    private readonly ConfigManagerInterface $configManager,
    private readonly CKEditor5PluginManagerInterface $pluginManager,
    private readonly string $pluginId,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get(ConfigManagerInterface::class),
      $container->get(CKEditor5PluginManagerInterface::class),
      $plugin_id,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function apply(string $configName, mixed $value): void {
    $editor = $this->configManager->loadConfigEntityByName($configName);
    assert($editor instanceof EditorInterface);

    if ($editor->getEditor() !== 'ckeditor5') {
      throw new ConfigActionException(sprintf('The %s config action only works with editors that use CKEditor 5.', $this->pluginId));
    }

    if (is_string($value)) {
      $value = ['item_name' => $value];
    }
    assert(is_array($value));

    $item_name = $value['item_name'];
    assert(is_string($item_name));

    $replace = $value['replace'] ?? FALSE;
    assert(is_bool($replace));

    $position = $value['position'] ?? NULL;

    $allow_duplicate = $value['allow_duplicate'] ?? FALSE;
    assert(is_bool($allow_duplicate));

    $editor_settings = $editor->getSettings();

    // If the item is already in the toolbar and we're not allowing duplicate
    // items, we're done.
    if (in_array($item_name, $editor_settings['toolbar']['items'], TRUE) && $allow_duplicate === FALSE && $item_name !== '|') {
      return;
    }

    if (is_int($position)) {
      // If we want to replace the item at this position, then `replace`
      // should be true. This would be useful if, for example, we wanted to
      // replace the Image button with the Media Library.
      array_splice($editor_settings['toolbar']['items'], $position, $replace ? 1 : 0, $item_name);
    }
    else {
      $editor_settings['toolbar']['items'][] = $item_name;
    }

    // If we're just adding a vertical separator, there's nothing else we need
    // to do at this point.
    if ($item_name === '|') {
      return;
    }

    // If this item is associated with a plugin, ensure that it's configured
    // at the editor level, if necessary.
    /** @var \Drupal\ckeditor5\Plugin\CKEditor5PluginDefinition $definition */
    foreach ($this->pluginManager->getDefinitions() as $id => $definition) {
      if (array_key_exists($item_name, $definition->getToolbarItems())) {
        // If plugin settings already exist, don't change them.
        if (array_key_exists($id, $editor_settings['plugins'])) {
          break;
        }
        elseif ($definition->isConfigurable()) {
          /** @var \Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableInterface $plugin */
          $plugin = $this->pluginManager->getPlugin($id, NULL);
          $editor_settings['plugins'][$id] = $plugin->defaultConfiguration();
        }
        // No need to examine any other plugins.
        break;
      }
    }

    $editor->setSettings($editor_settings)->save();
  }

}

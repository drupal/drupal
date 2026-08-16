<?php

declare(strict_types=1);

namespace Drupal\shortcut\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a shortcuts navigation block class.
 *
 * @internal
 */
#[Block(
  id: 'navigation_shortcuts',
  admin_label: new TranslatableMarkup('Navigation Shortcuts'),
)]
class ShortcutNavigationBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a new ShortcutsNavigationBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, protected ModuleHandlerInterface $moduleHandler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account): AccessResultInterface {
    return AccessResult::allowedIfHasPermission($account, 'access shortcuts');
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    if (!$this->moduleHandler->moduleExists('navigation')) {
      return [];
    }
    return [
      'shortcuts' => [
        // @phpstan-ignore-next-line
        '#lazy_builder' => ['navigation.shortcut_lazy_builder:lazyLinks', [$this->configuration['label']]],
        '#create_placeholder' => TRUE,
        '#cache' => [
          'keys' => ['shortcut_set_navigation_links'],
          'contexts' => ['user'],
        ],
        '#lazy_builder_preview' => [
          [
            '#theme' => 'navigation_menu',
            '#menu_name' => 'shortcuts',
            '#title' => $this->configuration['label'],
            '#items' => [
              [
                'title' => $this->configuration['label'],
                'class' => 'shortcuts',
                'icon' => [
                  'icon_id' => 'shortcuts',
                ],
              ],
            ],
          ],
        ],
      ],
    ];
  }

}

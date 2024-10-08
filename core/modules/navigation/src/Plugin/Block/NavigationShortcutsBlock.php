<?php

declare(strict_types=1);

namespace Drupal\navigation\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a shortcuts navigation block class.
 *
 * @internal
 *
 * @todo Move to Shortcut module as part of the core MR process.
 */
#[Block(
  id: 'navigation_shortcuts',
  admin_label: new TranslatableMarkup('Navigation Shortcuts'),
)]
final class NavigationShortcutsBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler')
    );
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
    // This navigation block requires shortcut module. Once the plugin is moved
    // to the module, this should not be necessary.
    if (!$this->moduleHandler->moduleExists('shortcut')) {
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
          '#markup' => '<a href="#" class="toolbar-tray-lazy-placeholder-link">&nbsp;</a>',
        ],
      ],
    ];
  }

}

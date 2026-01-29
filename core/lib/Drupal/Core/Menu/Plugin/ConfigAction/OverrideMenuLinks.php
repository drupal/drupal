<?php

declare(strict_types=1);

namespace Drupal\Core\Menu\Plugin\ConfigAction;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Config\Action\Attribute\ConfigAction;
use Drupal\Core\Config\Action\ConfigActionException;
use Drupal\Core\Config\Action\ConfigActionPluginInterface;
use Drupal\Core\DependencyInjection\AutowiredInstanceTrait;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Menu\StaticMenuLinkOverridesInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Overrides static menu links defined in code.
 *
 * This is essentially a thin wrapper around
 * \Drupal\Core\Menu\StaticMenuLinkOverridesInterface. The value passed to the
 * config action should be an associative array whose keys are the plugin IDs of
 * menu links defined in code, and whose values are either:
 *
 * - An array of properties to override for that menu link
 * - NULL to delete any existing override for that menu link
 *
 * Usage example:
 *
 * @code
 * core.menu.static_menu_link_overrides:
 *   overrideMenuLinks:
 *     navigation.content.node_type.blog:
 *       weight: -10
 *     user.create:
 *       enabled: false
 *     admin.reports: null
 * @endcode
 */
#[ConfigAction(
  id: 'overrideMenuLinks',
  admin_label: new TranslatableMarkup('Override static menu links'),
)]
final readonly class OverrideMenuLinks implements ConfigActionPluginInterface, ContainerFactoryPluginInterface {

  use AutowiredInstanceTrait;

  public function __construct(
    private MenuLinkManagerInterface $menuLinkManager,
    private StaticMenuLinkOverridesInterface $linkOverrides,
    #[Autowire(service: 'logger.channel.menu')] private LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function apply(string $configName, mixed $value): void {
    if ($configName !== 'core.menu.static_menu_link_overrides') {
      throw new ConfigActionException('This config action can only be used on the core.menu.static_menu_link_overrides config object.');
    }
    // We want to be sure we have the latest menu link data.
    $this->menuLinkManager->rebuild();

    assert(is_array($value));
    foreach ($value as $link_id => $definition) {
      if ($definition === NULL) {
        $this->linkOverrides->deleteOverride($link_id);
        continue;
      }

      try {
        $this->linkOverrides->saveOverride($link_id, $definition + $this->menuLinkManager->getDefinition($link_id));
      }
      catch (PluginNotFoundException) {
        $this->logger->warning('The @link_id menu link was not overridden because it does not exist.', [
          '@link_id' => $link_id,
        ]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return self::createInstanceAutowired($container);
  }

}

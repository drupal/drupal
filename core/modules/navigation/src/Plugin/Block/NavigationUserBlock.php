<?php

declare(strict_types=1);

namespace Drupal\navigation\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Menu\MenuLinkDefault;
use Drupal\Core\Menu\MenuLinkTreeElement;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a user navigation block.
 *
 * @internal
 */
#[Block(
  id: 'navigation_user',
  admin_label: new TranslatableMarkup('User'),
)]
final class NavigationUserBlock extends BlockBase implements ContainerFactoryPluginInterface {

  const string NAVIGATION_LINKS_MENU = 'navigation-user-links';

  /**
   * Constructs a new SystemMenuBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container.
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menuTree
   *   The menu link tree.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected ContainerInterface $container,
    protected MenuLinkTreeInterface $menuTree,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container,
      $container->get('navigation.menu_tree'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $menu_name = static::NAVIGATION_LINKS_MENU;
    $parameters = new MenuTreeParameters();
    $parameters
      ->setMinDepth(0)
      ->setMaxDepth(2)
      ->onlyEnabledLinks();
    $subtree = $this->menuTree->load($menu_name, $parameters);

    // Create a parent link that serves as a wrapper.
    // If the menu is removed for any reason, this item shows a link to the
    // user profile page as a fallback.
    $link = MenuLinkDefault::create($this->container, [], 'navigation.user_links.user.wrapper', $this->menuLinkDefinition());
    $tree = new MenuLinkTreeElement($link, TRUE, 1, FALSE, $subtree);

    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $tree = $this->menuTree->transform([$tree], $manipulators);
    $build = $this->menuTree->build($tree);
    $build['#title'] = $this->configuration['label'];
    $build += [
      '#attached' => [
        'library' => [
          'navigation/internal.user-block',
        ],
      ],
      '#attributes' => [
        'data-user-block' => TRUE,
      ],
    ];

    return $build;
  }

  /**
   * Custom wrapper element definition.
   *
   * @return array
   *   The menu link definition.
   */
  protected function menuLinkDefinition(): array {
    return [
      'menu_name' => 'navigation-user-links',
      'route_name' => 'user.page',
      'title' => $this->t('My Account'),
      'description' => '',
      'options' => [],
      'provider' => 'navigation',
      'enabled' => '1',
    ];

  }

}

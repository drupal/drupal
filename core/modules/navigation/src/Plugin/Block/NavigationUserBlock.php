<?php

declare(strict_types=1);

namespace Drupal\navigation\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Menu\MenuLinkDefault;
use Drupal\Core\Menu\MenuLinkTreeElement;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Security\Attribute\TrustedCallback;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\Entity\User;

/**
 * Defines a user navigation block.
 *
 * @internal
 */
#[Block(
  id: 'navigation_user',
  admin_label: new TranslatableMarkup('User'),
)]
final class NavigationUserBlock extends BlockBase {

  const string NAVIGATION_LINKS_MENU = 'navigation-user-links';

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    return [
      '#create_placeholder' => TRUE,
      '#lazy_builder' => [static::class . '::buildLinks', [$this->configuration['label']]],
      '#cache' => [
        'keys' => ['navigation_user_block'],
      ],
    ];
  }

  /**
   * Lazy builder callback.
   */
  #[TrustedCallback]
  public static function buildLinks(string $label): array {
    $parameters = new MenuTreeParameters();
    $parameters
      ->setMinDepth(0)
      ->setMaxDepth(2)
      ->onlyEnabledLinks();
    /** @var \Drupal\Core\Menu\MenuLinkTreeInterface $menu_tree */
    $menu_tree = \Drupal::service('navigation.menu_tree');
    $subtree = $menu_tree->load(static::NAVIGATION_LINKS_MENU, $parameters);

    // Load the current user so that they can be added as a cacheable dependency
    // of the final render array.
    $account = User::load(\Drupal::currentUser()->id());

    $menu_definition = [
      'menu_name' => static::NAVIGATION_LINKS_MENU,
      'route_name' => 'user.page',
      'route_parameters' => [],
      'title' => $account->getDisplayName(),
      'description' => '',
      'options' => [],
      'provider' => 'navigation',
      'enabled' => '1',
    ];
    // Create a parent link that serves as a wrapper.
    // If the menu is removed for any reason, this item shows a link to the
    // user profile page as a fallback.
    $link = MenuLinkDefault::create(\Drupal::getContainer(), [], 'navigation.user_links.user.wrapper', $menu_definition);
    $tree = new MenuLinkTreeElement($link, TRUE, 1, FALSE, $subtree);

    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $tree = $menu_tree->transform([$tree], $manipulators);
    $build = $menu_tree->build($tree);
    $build['#title'] = $label;
    $build['#cache']['contexts'][] = 'user';
    $cacheable_metadata = CacheableMetadata::createFromRenderArray($build);
    $cacheable_metadata->addCacheableDependency($account);
    $cacheable_metadata->applyTo($build);

    return $build;
  }

}

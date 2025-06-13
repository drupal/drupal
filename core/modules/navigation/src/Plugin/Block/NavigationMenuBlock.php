<?php

declare(strict_types=1);

namespace Drupal\navigation\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\navigation\Menu\NavigationMenuLinkTreeManipulators;
use Drupal\navigation\Plugin\Derivative\SystemMenuNavigationBlock as SystemMenuNavigationBlockDeriver;
use Drupal\system\Plugin\Block\SystemMenuBlock;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a generic menu navigation block.
 *
 * @internal
 */
#[Block(
  id: "navigation_menu",
  admin_label: new TranslatableMarkup("Navigation menu"),
  category: new TranslatableMarkup("Menus (Navigation)"),
  deriver: SystemMenuNavigationBlockDeriver::class,
)]
final class NavigationMenuBlock extends SystemMenuBlock implements ContainerFactoryPluginInterface {

  const NAVIGATION_MAX_DEPTH = 3;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('navigation.menu_tree'),
      $container->get('menu.active_trail'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'level' => 1,
      'depth' => 0,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $form = parent::blockForm($form, $form_state);
    unset($form['menu_levels']['expand_all_items']);
    $form['menu_levels']['depth']['#options'] = range(1, static::NAVIGATION_MAX_DEPTH);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    $this->configuration['level'] = $form_state->getValue('level');
    $this->configuration['depth'] = $form_state->getValue('depth');
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $menu_name = $this->getDerivativeId();
    $level = $this->configuration['level'];
    $depth = $this->configuration['depth'];
    $parameters = new MenuTreeParameters();
    $parameters
      ->setMinDepth($level)
      ->setMaxDepth(min($level + $depth, $this->menuTree->maxDepth()))
      ->onlyEnabledLinks();
    $tree = $this->menuTree->load($menu_name, $parameters);
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => NavigationMenuLinkTreeManipulators::class . ':addSecondLevelOverviewLinks'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $tree = $this->menuTree->transform($tree, $manipulators);
    $build = $this->menuTree->build($tree);
    if (!empty($build)) {
      $build['#title'] = $this->configuration['label'];
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [
      'module' => [
        'system',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    // We don't use menu active trails here.
    return array_filter(parent::getCacheContexts(), static fn (string $tag) => !str_starts_with($tag, 'route.menu_active_trails'));
  }

}

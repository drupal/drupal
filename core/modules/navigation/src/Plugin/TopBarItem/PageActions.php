<?php

declare(strict_types=1);

namespace Drupal\navigation\Plugin\TopBarItem;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\navigation\Attribute\TopBarItem;
use Drupal\navigation\NavigationRenderer;
use Drupal\navigation\TopBarItemBase;
use Drupal\navigation\TopBarRegion;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the Page Actions basic top bar item.
 */
#[TopBarItem(
  id: 'page_actions',
  region: TopBarRegion::Actions,
  label: new TranslatableMarkup('Page Actions'),
)]
final class PageActions extends TopBarItemBase implements ContainerFactoryPluginInterface {

  public function __construct(array $configuration, $plugin_id, $plugin_definition, private NavigationRenderer $navigationRenderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get(NavigationRenderer::class)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $build = [];
    // Local tasks for content entities.
    if ($this->navigationRenderer->hasLocalTasks()) {
      $local_tasks = $this->navigationRenderer->getLocalTasks();
      $build = [
        '#theme' => 'top_bar_local_tasks',
        '#local_tasks' => $local_tasks['tasks'],
      ];
      assert($local_tasks['cacheability'] instanceof CacheableMetadata);
      $local_tasks['cacheability']->applyTo($build);
    }

    return $build;
  }

}

<?php

namespace Drupal\aggregator;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Config\Config;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Theme\Registry;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * View builder handler for aggregator feeds.
 */
class FeedViewBuilder extends EntityViewBuilder {

  /**
   * The 'aggregator.settings' config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new FeedViewBuilder.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Config\Config $config
   *   The 'aggregator.settings' config.
   * @param \Drupal\Core\Theme\Registry $theme_registry
   *   The theme registry.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityRepositoryInterface $entity_repository, LanguageManagerInterface $language_manager, Config $config, Registry $theme_registry, EntityDisplayRepositoryInterface $entity_display_repository, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($entity_type, $entity_repository, $language_manager, $theme_registry, $entity_display_repository);
    $this->config = $config;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.repository'),
      $container->get('language_manager'),
      $container->get('config.factory')->get('aggregator.settings'),
      $container->get('theme.registry'),
      $container->get('entity_display.repository'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode) {
    parent::buildComponents($build, $entities, $displays, $view_mode);

    foreach ($entities as $id => $entity) {
      $bundle = $entity->bundle();
      $display = $displays[$bundle];

      if ($display->getComponent('items')) {
        // When in summary view mode, respect the list_max setting.
        $limit = $view_mode == 'summary' ? $this->config->get('source.list_max') : 20;
        // Retrieve the items attached to this feed.
        $items = $this->entityTypeManager
          ->getStorage('aggregator_item')
          ->loadByFeed($entity->id(), $limit);

        $build[$id]['items'] = $this->entityTypeManager
          ->getViewBuilder('aggregator_item')
          ->viewMultiple($items, $view_mode, $entity->language()->getId());

        if ($view_mode == 'full') {
          // Also add the pager.
          $build[$id]['pager'] = ['#type' => 'pager'];
        }
      }

      // By default, the description and image fields are exposed as
      // pseudo-fields rendered in this function. However they can optionally
      // be rendered directly using a field formatter. Skip rendering here if a
      // field formatter type is set.
      $component = $display->getComponent('description');
      if ($component && !isset($component['type'])) {
        $build[$id]['description'] = [
          '#markup' => $entity->getDescription(),
          '#allowed_tags' => _aggregator_allowed_tags(),
          '#prefix' => '<div class="feed-description">',
          '#suffix' => '</div>',
        ];
      }

      $component = $display->getComponent('image');
      if ($component && !isset($component['type'])) {
        $image_link = [];
        // Render the image as link if it is available.
        $image = $entity->getImage();
        $label = $entity->label();
        $link_href = $entity->getWebsiteUrl();
        if ($image && $label && $link_href) {
          $link_title = [
            '#theme' => 'image',
            '#uri' => $image,
            '#alt' => $label,
          ];
          $image_link = [
            '#type' => 'link',
            '#title' => $link_title,
            '#url' => Url::fromUri($link_href),
            '#options' => [
              'attributes' => ['class' => ['feed-image']],
            ],
          ];
        }
        $build[$id]['image'] = $image_link;
      }

      if ($display->getComponent('feed_icon')) {
        $build[$id]['feed_icon'] = [
          '#theme' => 'feed_icon',
          '#url' => $entity->getUrl(),
          '#title' => t('@title feed', ['@title' => $entity->label()]),
        ];
      }

      if ($display->getComponent('more_link')) {
        $title_stripped = strip_tags($entity->label());
        $build[$id]['more_link'] = [
          '#type' => 'link',
          '#title' => t('More<span class="visually-hidden"> posts about @title</span>', [
            '@title' => $title_stripped,
          ]),
          '#url' => Url::fromRoute('entity.aggregator_feed.canonical', ['aggregator_feed' => $entity->id()]),
          '#options' => [
            'attributes' => [
              'title' => $title_stripped,
            ],
          ],
        ];
      }

    }
  }

}

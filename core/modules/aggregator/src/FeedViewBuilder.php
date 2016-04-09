<?php

namespace Drupal\aggregator;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Config\Config;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * View builder handler for aggregator feeds.
 */
class FeedViewBuilder extends EntityViewBuilder {

  /**
   * Constructs a new FeedViewBuilder.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Config\Config $config
   *   The 'aggregator.settings' config.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityManagerInterface $entity_manager, LanguageManagerInterface $language_manager, Config $config) {
    parent::__construct($entity_type, $entity_manager, $language_manager);
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager'),
      $container->get('language_manager'),
      $container->get('config.factory')->get('aggregator.settings')
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
        $items = $this->entityManager
          ->getStorage('aggregator_item')
          ->loadByFeed($entity->id(), $limit);

        $build[$id]['items'] = $this->entityManager
          ->getViewBuilder('aggregator_item')
          ->viewMultiple($items, $view_mode, $entity->language()->getId());

        if ($view_mode == 'full') {
          // Also add the pager.
          $build[$id]['pager'] = array('#type' => 'pager');
        }
      }

      if ($display->getComponent('description')) {
        $build[$id]['description'] = array(
          '#markup' => $entity->getDescription(),
          '#allowed_tags' => _aggregator_allowed_tags(),
          '#prefix' => '<div class="feed-description">',
          '#suffix' => '</div>',
        );
      }

      if ($display->getComponent('image')) {
        $image_link = array();
        // Render the image as link if it is available.
        $image = $entity->getImage();
        $label = $entity->label();
        $link_href = $entity->getWebsiteUrl();
        if ($image && $label && $link_href) {
          $link_title = array(
            '#theme' => 'image',
            '#uri' => $image,
            '#alt' => $label,
          );
          $image_link = array(
            '#type' => 'link',
            '#title' => $link_title,
            '#url' => Url::fromUri($link_href),
            '#options' => array(
              'attributes' => array('class' => array('feed-image')),
            ),
          );
        }
        $build[$id]['image'] = $image_link;
      }

      if ($display->getComponent('feed_icon')) {
        $build[$id]['feed_icon'] = array(
          '#theme' => 'feed_icon',
          '#url' => $entity->getUrl(),
          '#title' => t('@title feed', array('@title' => $entity->label())),
        );
      }

      if ($display->getComponent('more_link')) {
        $title_stripped = strip_tags($entity->label());
        $build[$id]['more_link'] = array(
          '#type' => 'link',
          '#title' => t('More<span class="visually-hidden"> posts about @title</span>', array(
            '@title' => $title_stripped,
          )),
          '#url' => Url::fromRoute('entity.aggregator_feed.canonical', ['aggregator_feed' => $entity->id()]),
          '#options' => array(
            'attributes' => array(
              'title' => $title_stripped,
            ),
          ),
        );
      }

    }
  }

}

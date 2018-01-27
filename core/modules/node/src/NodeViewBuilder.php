<?php

namespace Drupal\node;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;

/**
 * View builder handler for nodes.
 */
class NodeViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode) {
    /** @var \Drupal\node\NodeInterface[] $entities */
    if (empty($entities)) {
      return;
    }

    parent::buildComponents($build, $entities, $displays, $view_mode);

    foreach ($entities as $id => $entity) {
      $bundle = $entity->bundle();
      $display = $displays[$bundle];

      if ($display->getComponent('links')) {
        $build[$id]['links'] = [
          '#lazy_builder' => [
            get_called_class() . '::renderLinks', [
              $entity->id(),
              $view_mode,
              $entity->language()->getId(),
              !empty($entity->in_preview),
              $entity->isDefaultRevision() ? NULL : $entity->getLoadedRevisionId(),
            ],
          ],
        ];
      }

      // Add Language field text element to node render array.
      if ($display->getComponent('langcode')) {
        $build[$id]['langcode'] = [
          '#type' => 'item',
          '#title' => t('Language'),
          '#markup' => $entity->language()->getName(),
          '#prefix' => '<div id="field-language-display">',
          '#suffix' => '</div>'
        ];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getBuildDefaults(EntityInterface $entity, $view_mode) {
    $defaults = parent::getBuildDefaults($entity, $view_mode);

    // Don't cache nodes that are in 'preview' mode.
    if (isset($defaults['#cache']) && isset($entity->in_preview)) {
      unset($defaults['#cache']);
    }

    return $defaults;
  }

  /**
   * #lazy_builder callback; builds a node's links.
   *
   * @param string $node_entity_id
   *   The node entity ID.
   * @param string $view_mode
   *   The view mode in which the node entity is being viewed.
   * @param string $langcode
   *   The language in which the node entity is being viewed.
   * @param bool $is_in_preview
   *   Whether the node is currently being previewed.
   * @param $revision_id
   *   (optional) The identifier of the node revision to be loaded. If none
   *   is provided, the default revision will be loaded.
   *
   * @return array
   *   A renderable array representing the node links.
   */
  public static function renderLinks($node_entity_id, $view_mode, $langcode, $is_in_preview, $revision_id = NULL) {
    $links = [
      '#theme' => 'links__node',
      '#pre_render' => ['drupal_pre_render_links'],
      '#attributes' => ['class' => ['links', 'inline']],
    ];

    if (!$is_in_preview) {
      $storage = \Drupal::entityTypeManager()->getStorage('node');
      /** @var \Drupal\node\NodeInterface $revision */
      $revision = !isset($revision_id) ? $storage->load($node_entity_id) : $storage->loadRevision($revision_id);
      $entity = $revision->getTranslation($langcode);
      $links['node'] = static::buildLinks($entity, $view_mode);

      // Allow other modules to alter the node links.
      $hook_context = [
        'view_mode' => $view_mode,
        'langcode' => $langcode,
      ];
      \Drupal::moduleHandler()->alter('node_links', $links, $entity, $hook_context);
    }
    return $links;
  }

  /**
   * Build the default links (Read more) for a node.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   The node object.
   * @param string $view_mode
   *   A view mode identifier.
   *
   * @return array
   *   An array that can be processed by drupal_pre_render_links().
   */
  protected static function buildLinks(NodeInterface $entity, $view_mode) {
    $links = [];

    // Always display a read more link on teasers because we have no way
    // to know when a teaser view is different than a full view.
    if ($view_mode == 'teaser') {
      $node_title_stripped = strip_tags($entity->label());
      $links['node-readmore'] = [
        'title' => t('Read more<span class="visually-hidden"> about @title</span>', [
          '@title' => $node_title_stripped,
        ]),
        'url' => $entity->urlInfo(),
        'language' => $entity->language(),
        'attributes' => [
          'rel' => 'tag',
          'title' => $node_title_stripped,
        ],
      ];
    }

    return [
      '#theme' => 'links__node__node',
      '#links' => $links,
      '#attributes' => ['class' => ['links', 'inline']],
    ];
  }

}

<?php

namespace Drupal\layout_builder\Cache;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CalculatedCacheContextInterface;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Determines whether Layout Builder is active for a given entity type or not.
 *
 * Cache context ID: 'layout_builder_is_active:%entity_type_id', e.g.
 * 'layout_builder_is_active:node' (to vary by whether the Node entity type has
 * Layout Builder enabled).
 */
class LayoutBuilderIsActiveCacheContext implements CalculatedCacheContextInterface {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * LayoutBuilderCacheContext constructor.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(RouteMatchInterface $route_match) {
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Layout Builder');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext($entity_type_id = NULL) {
    if (!$entity_type_id) {
      throw new \LogicException('Missing entity type ID');
    }

    $display = $this->getDisplay($entity_type_id);
    return ($display && $display->getThirdPartySetting('layout_builder', 'allow_custom', FALSE)) ? '1' : '0';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($entity_type_id = NULL) {
    if (!$entity_type_id) {
      throw new \LogicException('Missing entity type ID');
    }

    $cacheable_metadata = new CacheableMetadata();
    if ($display = $this->getDisplay($entity_type_id)) {
      $cacheable_metadata->addCacheableDependency($display);
    }
    return $cacheable_metadata;
  }

  /**
   * Returns the entity view display for a given entity type and view mode.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $view_mode
   *   (optional) The view mode that should be used to render the entity.
   *
   * @return \Drupal\Core\Entity\Display\EntityViewDisplayInterface|null
   *   The entity view display, if it exists.
   */
  protected function getDisplay($entity_type_id, $view_mode = 'full') {
    if ($entity = $this->routeMatch->getParameter($entity_type_id)) {
      return EntityViewDisplay::collectRenderDisplay($entity, $view_mode);
    }
  }

}

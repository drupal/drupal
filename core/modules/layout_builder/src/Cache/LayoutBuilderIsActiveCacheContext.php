<?php

namespace Drupal\layout_builder\Cache;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CalculatedCacheContextInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\layout_builder\OverridesSectionStorageInterface;

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
    return ($display && $display->isOverridable()) ? '1' : '0';
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
   *
   * @return \Drupal\layout_builder\Entity\LayoutEntityDisplayInterface|null
   *   The entity view display, if it exists.
   */
  protected function getDisplay($entity_type_id) {
    if ($entity = $this->routeMatch->getParameter($entity_type_id)) {
      if ($entity instanceof OverridesSectionStorageInterface) {
        return $entity->getDefaultSectionStorage();
      }
    }
  }

}

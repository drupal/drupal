<?php

declare(strict_types=1);

namespace Drupal\navigation;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Routing\CurrentRouteMatch;

/**
 * Helper service to detect entity routes.
 *
 * @internal
 */
class EntityRouteHelper {

  const ENTITY_ROUTE_CID = 'navigation_content_entity_paths';

  /**
   * A list of all the link paths of enabled content entities.
   *
   * @var array
   */
  protected array $contentEntityPaths;

  /**
   * EntityRouteHelper constructor.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $routeMatch
   *   The route match.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   The cache backend.
   */
  public function __construct(
    protected CurrentRouteMatch $routeMatch,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected CacheBackendInterface $cacheBackend,
  ) {
  }

  /**
   * Determines if content entity route condition is met.
   *
   * @return bool
   *   TRUE if the content entity route condition is met, FALSE otherwise.
   */
  public function isContentEntityRoute(): bool {
    return array_key_exists($this->routeMatch->getRouteObject()->getPath(), $this->getContentEntityPaths());
  }

  public function getContentEntityFromRoute(): ?ContentEntityInterface {
    $path = $this->routeMatch->getRouteObject()->getPath();
    if (!$entity_type = $this->getContentEntityPaths()[$path] ?? NULL) {
      return NULL;
    }

    $entity = $this->routeMatch->getParameter($entity_type);
    if ($entity instanceof ContentEntityInterface && $entity->getEntityTypeId() === $entity_type) {
      return $entity;
    }

    return NULL;
  }

  /**
   * Returns the paths for the link templates of all content entities.
   *
   * @return array
   *   An array of all content entity type IDs, keyed by the corresponding link
   *   template paths.
   */
  protected function getContentEntityPaths(): array {
    if (isset($this->contentEntityPaths)) {
      return $this->contentEntityPaths;
    }

    $content_entity_paths = $this->cacheBackend->get(static::ENTITY_ROUTE_CID);

    if (isset($content_entity_paths->data)) {
      $this->contentEntityPaths = $content_entity_paths->data;
      return $this->contentEntityPaths;
    }

    $this->contentEntityPaths = $this->doGetContentEntityPaths();
    $this->cacheBackend->set(static::ENTITY_ROUTE_CID, $this->contentEntityPaths, CacheBackendInterface::CACHE_PERMANENT, ['entity_types', 'routes']);

    return $this->contentEntityPaths;
  }

  protected function doGetContentEntityPaths(): array {
    $content_entity_paths = [];
    $entity_types = $this->entityTypeManager->getDefinitions();
    foreach ($entity_types as $entity_type) {
      if ($entity_type->entityClassImplements(ContentEntityInterface::class)) {
        $entity_paths = $this->getContentEntityTypePaths($entity_type);
        $content_entity_paths = array_merge($content_entity_paths, $entity_paths);
      }
    }

    return $content_entity_paths;
  }

  /**
   * Returns the path for the link template for a given content entity type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   *
   * @return array
   *   Array containing the paths for the given content entity type.
   */
  protected function getContentEntityTypePaths(EntityTypeInterface $entity_type): array {
    $paths = array_filter($entity_type->getLinkTemplates(), fn ($template) => $template !== 'collection', ARRAY_FILTER_USE_KEY);
    if ($this->isLayoutBuilderEntityType($entity_type)) {
      $paths[] = $entity_type->getLinkTemplate('canonical') . '/layout';
    }
    return array_fill_keys($paths, $entity_type->id());
  }

  /**
   * Determines if a given entity type is layout builder relevant or not.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return bool
   *   Whether this entity type is a Layout builder candidate or not
   *
   * @see \Drupal\layout_builder\Plugin\SectionStorage\OverridesSectionStorage::getEntityTypes()
   */
  protected function isLayoutBuilderEntityType(EntityTypeInterface $entity_type): bool {
    return $entity_type->entityClassImplements(FieldableEntityInterface::class) && $entity_type->hasHandlerClass('form', 'layout_builder') && $entity_type->hasViewBuilderClass() && $entity_type->hasLinkTemplate('canonical');
  }

}

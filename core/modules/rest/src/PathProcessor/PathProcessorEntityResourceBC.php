<?php

namespace Drupal\rest\PathProcessor;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Path processor to maintain BC for entity REST resource URLs from Drupal 8.0.
 */
class PathProcessorEntityResourceBC implements InboundPathProcessorInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates a new PathProcessorEntityResourceBC instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    if ($request->getMethod() === 'POST' && strpos($path, '/entity/') === 0) {
      $parts = explode('/', $path);
      $entity_type_id = array_pop($parts);

      // Until Drupal 8.3, no entity types specified a link template for the
      // 'create' link relation type. As of Drupal 8.3, all core content entity
      // types provide this link relation type. This inbound path processor
      // provides automatic backwards compatibility: it allows both the old
      // default from \Drupal\rest\Plugin\rest\resource\EntityResource, i.e.
      // "/entity/{entity_type}" and the link template specified in a particular
      // entity type. The former is rewritten to the latter
      // specific one if it exists.
      $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
      if ($entity_type->hasLinkTemplate('create')) {
        return $entity_type->getLinkTemplate('create');
      }
    }
    return $path;
  }

}

<?php
/**
 * @file
 * Contains \Drupal\rest\LinkManager\LinkManager.
 */

namespace Drupal\rest\LinkManager;

class LinkManager implements LinkManagerInterface {

  /**
   * The type link manager.
   *
   * @var \Drupal\rest\LinkManager\TypeLinkManagerInterface
   */
  protected $typeLinkManager;

  /**
   * The relation link manager.
   *
   * @var \Drupal\rest\LinkManager\RelationLinkManagerInterface
   */
  protected $relationLinkManager;

  /**
   * Constructor.
   *
   * @param \Drupal\rest\LinkManager\TypeLinkManagerInterface $type_link_manager
   *   Manager for handling bundle URIs.
   * @param \Drupal\rest\LinkManager\RelationLinkManagerInterface $relation_link_manager
   *   Manager for handling bundle URIs.
   */
  public function __construct(TypeLinkManagerInterface $type_link_manager, RelationLinkManagerInterface $relation_link_manager) {
    $this->typeLinkManager = $type_link_manager;
    $this->relationLinkManager = $relation_link_manager;
  }

  /**
   * Implements \Drupal\rest\LinkManager\TypeLinkManagerInterface::getTypeUri().
   */
  public function getTypeUri($entity_type, $bundle) {
    return $this->typeLinkManager->getTypeUri($entity_type, $bundle);
  }

  /**
   * Implements \Drupal\rest\LinkManager\TypeLinkManagerInterface::getTypeInternalIds().
   */
  public function getTypeInternalIds($type_uri) {
    return $this->typeLinkManager->getTypeInternalIds($type_uri);
  }

  /**
   * Implements \Drupal\rest\LinkManager\RelationLinkManagerInterface::getRelationUri().
   */
  public function getRelationUri($entity_type, $bundle, $field_name) {
    return $this->relationLinkManager->getRelationUri($entity_type, $bundle, $field_name);
  }

  /**
   * Implements \Drupal\rest\LinkManager\RelationLinkManagerInterface::getRelationInternalIds().
   */
  public function getRelationInternalIds($relation_uri) {
    return $this->relationLinkManager->getRelationInternalIds($relation_uri);
  }
}

<?php

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
   * {@inheritdoc}
   */
  public function getTypeUri($entity_type, $bundle, $context = array()) {
    return $this->typeLinkManager->getTypeUri($entity_type, $bundle, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function getTypeInternalIds($type_uri, $context = array()) {
    return $this->typeLinkManager->getTypeInternalIds($type_uri, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function getRelationUri($entity_type, $bundle, $field_name, $context = array()) {
    return $this->relationLinkManager->getRelationUri($entity_type, $bundle, $field_name, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function getRelationInternalIds($relation_uri) {
    return $this->relationLinkManager->getRelationInternalIds($relation_uri);
  }

  /**
   * {@inheritdoc}
   */
  public function setLinkDomain($domain) {
    $this->relationLinkManager->setLinkDomain($domain);
    $this->typeLinkManager->setLinkDomain($domain);
    return $this;
  }

}

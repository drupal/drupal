<?php

/**
 * Contains \Drupal\node\NodeTypeListController.
 */

namespace Drupal\node;

use Drupal\Core\Config\Entity\ConfigEntityListController;
use Drupal\Core\Entity\EntityControllerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Component\Utility\Xss;
use Drupal\Component\Utility\String;

/**
 * Provides a listing of node types.
 */
class NodeTypeListController extends ConfigEntityListController implements EntityControllerInterface {

  /**
   * The url generator service.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * Constructs a NodeTypeFormController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageControllerInterface $storage
   *   The entity storage controller class.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The url generator service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageControllerInterface $storage, UrlGeneratorInterface $url_generator) {
    parent::__construct($entity_type, $storage);
    $this->urlGenerator = $url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorageController($entity_type->id()),
      $container->get('url_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['title'] = t('Name');
    $header['description'] = array(
      'data' => t('Description'),
      'class' => array(RESPONSIVE_PRIORITY_MEDIUM),
    );
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['title'] = array(
      'data' => $this->getLabel($entity),
      'class' => array('menu-label'),
    );
    $row['description'] = Xss::filterAdmin($entity->description);
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);
    // Place the edit operation after the operations added by field_ui.module
    // which have the weights 15, 20, 25.
    if (isset($operations['edit'])) {
      $operations['edit']['weight'] = 30;
    }
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['#empty'] = t('No content types available. <a href="@link">Add content type</a>.', array(
      '@link' => $this->urlGenerator->generateFromPath('admin/structure/types/add'),
    ));
    return $build;
  }

}

<?php

/**
 * Contains \Drupal\node\NodeTypeListController.
 */

namespace Drupal\node;

use Drupal\Core\Config\Entity\ConfigEntityListController;
use Drupal\Core\Entity\EntityControllerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\PathBasedGeneratorInterface;
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
   * @var \Drupal\Core\Routing\PathBasedGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * Constructs a NodeTypeFormController object.
   *
   * @param string $entity_type
   *   The type of entity to be listed.
   * @param array $entity_info
   *   An array of entity info for the entity type.
   * @param \Drupal\Core\Entity\EntityStorageControllerInterface $storage
   *   The entity storage controller class.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke hooks on.
   * @param \Drupal\Core\Routing\PathBasedGeneratorInterface $url_generator
   *   The url generator service.
   */
  public function __construct($entity_type, array $entity_info, EntityStorageControllerInterface $storage, ModuleHandlerInterface $module_handler, PathBasedGeneratorInterface $url_generator) {
    parent::__construct($entity_type, $entity_info, $storage, $module_handler);
    $this->urlGenerator = $url_generator;
  }
  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, $entity_type, array $entity_info) {
    return new static(
      $entity_type,
      $entity_info,
      $container->get('plugin.manager.entity')->getStorageController($entity_type),
      $container->get('module_handler'),
      $container->get('url_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $row['title'] = t('Name');
    $row['description'] = array(
      'data' => t('Description'),
      'class' => array(RESPONSIVE_PRIORITY_MEDIUM),
    );
    $row['operations'] = t('Operations');
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['title'] = array(
      'data' => String::checkPlain($entity->label()),
      'class' => array('menu-label'),
    );
    $row['description'] = Xss::filterAdmin($entity->description);
    $row['operations']['data'] = $this->buildOperations($entity);
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);
    $uri = $entity->uri();
    if ($this->moduleHandler->moduleExists('field_ui') && user_access('administer node fields')) {
      $operations['manage-fields'] = array(
        'title' => t('Manage fields'),
        'href' => $uri['path'] . '/fields',
        'options' => $uri['options'],
        'weight' => 0,
      );
    }
    if ($this->moduleHandler->moduleExists('field_ui') && user_access('administer node form display')) {
      $operations['manage-form-display'] = array(
        'title' => t('Manage form display'),
        'href' => $uri['path'] . '/form-display',
        'options' => $uri['options'],
        'weight' => 5,
      );
    }
    if ($this->moduleHandler->moduleExists('field_ui') && user_access('administer node display')) {
      $operations['manage-display'] = array(
        'title' => t('Manage display'),
        'href' => $uri['path'] . '/display',
        'options' => $uri['options'],
        'weight' => 10,
      );
    }
    if ($entity->isLocked()) {
      unset($operations['delete']);
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

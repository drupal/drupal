<?php

/**
 * Definition of Drupal\views_ui_listing\EntityListControllerBase.
 */

namespace Drupal\views_ui_listing;

use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Abstract base class for config entity listing plugins.
 */
abstract class EntityListControllerBase implements EntityListControllerInterface {

  /**
   * The Config storage controller class.
   *
   * @var Drupal\config\ConfigStorageController
   */
  protected $storage;

  /**
   * The Config entity type.
   *
   * @var string
   */
  protected $entityType;

  /**
   * The Config entity info.
   *
   * @var array
   */
  protected $entityInfo;

  /**
   * If ajax links are used on the listing page.
   *
   * @var bool
   */
  protected $usesAJAX;

  public function __construct($entity_type, $entity_info = FALSE) {
    $this->entityType = $entity_type;
    $this->storage = entity_get_controller($entity_type);
    if (!$entity_info) {
      $entity_info = entity_get_info($entity_type);
    }
    $this->entityInfo = $entity_info;
  }

  /**
   * Implements Drupal\views_ui_listing\EntityListControllerInterface::getList();
   */
  public function getList() {
    return $this->storage->load();
  }

  /**
   * Implements Drupal\views_ui_listing\EntityListControllerInterface::getStorageController();
   */
  public function getStorageController() {
    return $this->storage;
  }

  public function getPath() {
    return $this->entityInfo['list path'];
  }

  /**
   * Implements Drupal\views_ui_listing\EntityListControllerInterface::hookMenu();
   */
  public function hookMenu() {
    $items = array();
    $items[$this->entityInfo['list path']] = array(
      'page callback' => 'views_ui_listing_entity_listing_page',
      'page arguments' => array($this->entityType),
      // @todo Add a proper access callback here.
      'access callback' => TRUE,
    );
    return $items;
  }

  /**
   * Implements Drupal\views_ui_listing\EntityListControllerInterface::getRowData();
   */
  public function getRowData(EntityInterface $entity) {
    $row = array();

    $row['id'] = $entity->id();
    $row['label'] = $entity->label();
    $actions = $this->buildActionLinks($entity);
    $row['actions'] = drupal_render($actions);

    return $row;
  }

  /**
   * Implements Drupal\views_ui_listing\EntityListControllerInterface::getHeaderData();
   */
  public function getHeaderData() {
    $row = array();
    $row['id'] = t('ID');
    $row['label'] = t('Label');
    $row['actions'] = t('Actions');
    return $row;
  }

  /**
   * Implements Drupal\views_ui_listing\EntityListControllerInterface::buildActionLinks();
   */
  public function buildActionLinks(EntityInterface $entity) {
    $links = array();

    foreach ($this->defineActionLinks($entity) as $definition) {
      $attributes = array();

      if (!empty($definition['ajax'])) {
        $attributes['class'][] = 'use-ajax';
        // Set this to true if we haven't already.
        if (!isset($this->usesAJAX)) {
          $this->usesAJAX = TRUE;
        }
      }

      $links[] = array(
        'title' => $definition['title'],
        'href' => $definition['href'],
        'attributes' => $attributes,
      );
    }

    return array(
      '#theme' => 'links',
      '#links' => $links,
    );
  }

  /**
   * Implements Drupal\views_ui_listing\EntityListControllerInterface::renderList();
   */
  public function renderList() {
    $rows = array();

    foreach ($this->getList() as $entity) {
      $rows[] = $this->getRowData($entity);
    }

    // Add core AJAX library if we need to.
    if (!empty($this->usesAJAX)) {
      drupal_add_library('system', 'drupal.ajax');
    }

    return array(
      '#theme' => 'table',
      '#header' => $this->getHeaderData(),
      '#rows' => $rows,
      '#attributes' => array(
        'id' => 'config-entity-listing',
      ),
    );
  }

  /**
   * Implements Drupal\views_ui_listing\EntityListControllerInterface::renderList();
   */
  public function renderListAJAX() {
    $list = $this->renderList();
    $commands = array();
    $commands[] = ajax_command_replace('#config-entity-listing', drupal_render($list));

    return new JsonResponse(ajax_render($commands));
  }

}

<?php

/**
 * @file
 * Contains \Drupal\action\ActionListController.
 */

namespace Drupal\action;

use Drupal\Core\Action\ActionManager;
use Drupal\Core\Entity\EntityControllerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Config\Entity\ConfigEntityListController;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a listing of Actions.
 */
class ActionListController extends ConfigEntityListController implements EntityControllerInterface {

  /**
   * @var bool
   */
  protected $hasConfigurableActions = FALSE;

  /**
   * The action plugin manager.
   *
   * @var \Drupal\Core\Action\ActionManager
   */
  protected $actionManager;

  /**
   * Constructs a new ActionListController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageControllerInterface $storage
   *   The action storage controller.
   * @param \Drupal\Core\Action\ActionManager $action_manager
   *   The action plugin manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageControllerInterface $storage, ActionManager $action_manager) {
    parent::__construct($entity_type, $storage);

    $this->actionManager = $action_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorageController($entity_type->id()),
      $container->get('plugin.manager.action')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $entities = parent::load();
    foreach ($entities as $entity) {
      if ($entity->isConfigurable()) {
        $this->hasConfigurableActions = TRUE;
        continue;
      }
    }
    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['type'] = $entity->getType();
    $row['label'] = $this->getLabel($entity);
    if ($this->hasConfigurableActions) {
      $row += parent::buildRow($entity);
    }
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = array(
      'type' => t('Action type'),
      'label' => t('Label'),
    ) + parent::buildHeader();
    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = $entity->isConfigurable() ? parent::getOperations($entity) : array();
    if (isset($operations['edit'])) {
      $operations['edit']['title'] = t('Configure');
    }
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build['action_header']['#markup'] = '<h3>' . t('Available actions:') . '</h3>';
    $build['action_table'] = parent::render();
    if (!$this->hasConfigurableActions) {
      unset($build['action_table']['#header']['operations']);
    }
    $build['action_admin_manage_form'] = drupal_get_form('Drupal\action\Form\ActionAdminManageForm');
    return $build;
  }

}

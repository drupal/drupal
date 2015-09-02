<?php

/**
 * @file
 * Contains \Drupal\action\ActionListBuilder.
 */

namespace Drupal\action;

use Drupal\Core\Action\ActionManager;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of action entities.
 *
 * @see \Drupal\system\Entity\Action
 * @see action_entity_info()
 */
class ActionListBuilder extends ConfigEntityListBuilder {

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
   * Constructs a new ActionListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The action storage.
   * @param \Drupal\Core\Action\ActionManager $action_manager
   *   The action plugin manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, ActionManager $action_manager) {
    parent::__construct($entity_type, $storage);

    $this->actionManager = $action_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
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
    $row['label'] = $entity->label();
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
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = $entity->isConfigurable() ? parent::getDefaultOperations($entity) : array();
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
    $build['action_admin_manage_form'] = \Drupal::formBuilder()->getForm('Drupal\action\Form\ActionAdminManageForm');
    return $build;
  }

}

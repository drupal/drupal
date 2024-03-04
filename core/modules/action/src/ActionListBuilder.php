<?php

namespace Drupal\action;

use Drupal\action\Form\ActionAdminManageForm;
use Drupal\Core\Action\ActionManager;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of action entities.
 *
 * @see \Drupal\system\Entity\Action
 * @see action_entity_type_build()
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
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   The form builder.
   */
  public function __construct(
    EntityTypeInterface $entity_type,
    EntityStorageInterface $storage,
    ActionManager $action_manager,
    protected ?FormBuilderInterface $formBuilder = NULL,
  ) {
    parent::__construct($entity_type, $storage);

    $this->actionManager = $action_manager;
    if (!$formBuilder) {
      @trigger_error('Calling ' . __METHOD__ . ' without the $formBuilder argument is deprecated in drupal:10.3.0 and it will be required in drupal:11.0.0. See https://www.drupal.org/node/3159776', E_USER_DEPRECATED);
      $this->formBuilder = \Drupal::service('form_builder');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('plugin.manager.action'),
      $container->get('form_builder')
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
        break;
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
    $header = [
      'type' => t('Action type'),
      'label' => t('Label'),
    ] + parent::buildHeader();
    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    if (isset($operations['edit'])) {
      $operations['edit']['title'] = t('Configure');
    }
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build['action_admin_manage_form'] = $this->formBuilder->getForm(ActionAdminManageForm::class);
    $build['action_header']['#markup'] = '<h3>' . $this->t('Available actions:') . '</h3>';
    $build['action_table'] = parent::render();
    if (!$this->hasConfigurableActions) {
      unset($build['action_table']['table']['#header']['operations']);
    }
    return $build;
  }

}

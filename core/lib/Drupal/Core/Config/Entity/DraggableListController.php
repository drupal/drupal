<?php

/**
 * @file
 * Contains Drupal\Core\Config\Entity\DraggableListController.
 */

namespace Drupal\Core\Config\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormInterface;

/**
 * Provides a list controller for draggable configuration entities.
 */
abstract class DraggableListController extends ConfigEntityListController implements FormInterface {

  /**
   * The key to use for the form element containing the entities.
   *
   * @var string
   */
  protected $entitiesKey = 'entities';

  /**
   * The entities being listed.
   *
   * @var \Drupal\Core\Entity\EntityInterface[]
   */
  protected $entities = array();

  /**
   * Name of the entity's weight field or FALSE if no field is provided.
   *
   * @var string|bool
   */
  protected $weightKey = FALSE;

  /**
   * {@inheritdoc}
   */
  public function __construct($entity_type, array $entity_info, EntityStorageControllerInterface $storage, ModuleHandlerInterface $module_handler) {
    parent::__construct($entity_type, $entity_info, $storage, $module_handler);

    // Check if the entity type supports weighting.
    if (!empty($this->entityInfo['entity_keys']['weight'])) {
      $this->weightKey = $this->entityInfo['entity_keys']['weight'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = array();
    if (!empty($this->weightKey)) {
      $header['weight'] = t('Weight');
    }
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = array();
    if (!empty($this->weightKey)) {
      // Override default values to markup elements.
      $row['#attributes']['class'][] = 'draggable';
      $row['#weight'] = $entity->get($this->weightKey);
      // Add weight column.
      $row['weight'] = array(
        '#type' => 'weight',
        '#title' => t('Weight for @title', array('@title' => $entity->label())),
        '#title_display' => 'invisible',
        '#default_value' => $entity->get($this->weightKey),
        '#attributes' => array('class' => array('weight')),
      );
    }
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    if (!empty($this->weightKey)) {
      return drupal_get_form($this);
    }
    return parent::render();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $form[$this->entitiesKey] = array(
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#empty' => t('There is no @label yet.', array('@label' => $this->entityInfo['label'])),
      '#tabledrag' => array(
        array('order', 'sibling', 'weight'),
      ),
    );

    $this->entities = $this->load();
    foreach ($this->entities as $entity) {
      $row = $this->buildRow($entity);
      if (isset($row['label'])) {
        $row['label'] = array('#markup' => $row['label']);
      }
      $form[$this->entitiesKey][$entity->id()] = $row;
    }

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save order'),
      '#button_type' => 'primary',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    // No validation.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    foreach ($form_state['values'][$this->entitiesKey] as $id => $value) {
      if (isset($this->entities[$id]) && $this->entities[$id]->get($this->weightKey) != $value['weight']) {
        // Save entity only when its weight was changed.
        $this->entities[$id]->set($this->weightKey, $value['weight']);
        $this->entities[$id]->save();
      }
    }
  }

}

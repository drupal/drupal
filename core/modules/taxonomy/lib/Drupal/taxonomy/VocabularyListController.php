<?php

/**
 * @file
 * Contains \Drupal\taxonomy\VocabularyListController.
 */

namespace Drupal\taxonomy;

use Drupal\Core\Config\Entity\ConfigEntityListController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormInterface;

/**
 * Provides a listing of vocabularies.
 */
class VocabularyListController extends ConfigEntityListController implements FormInterface {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'taxonomy_overview_vocabularies';
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);
    $uri = $entity->uri();

    if (isset($operations['edit'])) {
      $operations['edit']['title'] = t('edit vocabulary');
      $operations['edit']['href'] = $uri['path'] . '/edit';
    }

    $operations['list'] = array(
      'title' => t('list terms'),
      'href' => $uri['path'],
      'options' => $uri['options'],
      'weight' => 0,
    );
    $operations['add'] = array(
      'title' => t('add terms'),
      'href' => $uri['path'] . '/add',
      'options' => $uri['options'],
      'weight' => 30,
    );
    unset($operations['delete']);

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = t('Vocabulary name');
    $header['weight'] = t('Weight');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    // Override default values to markup elements.
    $row['#attributes']['class'][] = 'draggable';

    $row['label'] = array(
      '#markup' => $this->getLabel($entity),
    );
    $row['#weight'] = $entity->get('weight');
    // Add weight column.
    $row['weight'] = array(
      '#type' => 'weight',
      '#title' => t('Weight for @title', array('@title' => $entity->label())),
      '#title_display' => 'invisible',
      '#default_value' => $entity->get('weight'),
      '#attributes' => array('class' => array('weight')),
    );
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $entities = $this->load();
    if (count($entities) > 1) {
      // Creates a form for manipulating vocabulary weights if more then one
      // vocabulary exists.
      return drupal_get_form($this);
    }
    $build = array(
      '#theme' => 'table',
      '#header' => $this->buildHeader(),
      '#rows' => array(),
      '#empty' => t('No vocabularies available. <a href="@link">Add vocabulary</a>.', array('@link' => url('admin/structure/taxonomy/add'))),
    );
    unset($build['#header']['weight']);
    foreach ($entities as $entity) {
      $row['label'] = $this->getLabel($entity);
      $build['#rows'][$entity->id()] = $row + parent::buildRow($entity);
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $form['vocabularies'] = array(
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#tabledrag' => array(
        array('order', 'sibling', 'weight'),
      ),
      '#attributes' => array(
        'id' => 'taxonomy',
      ),
    );

    foreach ($this->load() as $entity) {
      $form['vocabularies'][$entity->id()] = $this->buildRow($entity);
    }

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
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
    $vocabularies = $form_state['values']['vocabularies'];

    $entities = entity_load_multiple($this->entityType, array_keys($vocabularies));
    foreach ($vocabularies as $id => $value) {
      if (isset($entities[$id]) && $value['weight'] != $entities[$id]->get('weight')) {
        // Update changed weight.
        $entities[$id]->set('weight', $value['weight']);
        $entities[$id]->save();
      }
    }

    drupal_set_message(t('The configuration options have been saved.'));
  }

}

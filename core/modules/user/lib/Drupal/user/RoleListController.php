<?php

/**
 * @file
 * Contains \Drupal\user\RoleListController.
 */

namespace Drupal\user;

use Drupal\Core\Config\Entity\ConfigEntityListController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormInterface;

/**
 * Provides a listing of user roles.
 */
class RoleListController extends ConfigEntityListController implements FormInterface {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'user_admin_roles_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = t('Name');
    $header['weight'] = t('Weight');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);

    $operations['permissions'] = array(
      'title' => t('Edit permissions'),
      'href' => 'admin/people/permissions/' . $entity->id(),
      'weight' => 20,
    );
    // Built-in roles could not be deleted or disabled.
    if (in_array($entity->id(), array(DRUPAL_ANONYMOUS_RID, DRUPAL_AUTHENTICATED_RID))) {
      unset($operations['delete']);
    }
    return $operations;
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
    return drupal_get_form($this);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $form['entities'] = array(
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#empty' => t('There is no @label yet.', array('@label' => $this->entityInfo['label'])),
      '#tabledrag' => array(
        array('order', 'sibling', 'weight'),
      ),
    );

    foreach ($this->load() as $entity) {
      $form['entities'][$entity->id()] = $this->buildRow($entity);
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
    $values = $form_state['values']['entities'];

    $entities = entity_load_multiple($this->entityType, array_keys($values));
    foreach ($values as $id => $value) {
      if (isset($entities[$id]) && $value['weight'] != $entities[$id]->get('weight')) {
        // Update changed weight.
        $entities[$id]->set('weight', $value['weight']);
        $entities[$id]->save();
      }
    }

    drupal_set_message(t('The role settings have been updated.'));
  }
}

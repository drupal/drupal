<?php
/**
 * @file
 * Contains \Drupal\language\Form\LanguageListController.
 */

namespace Drupal\language;

use Drupal\Core\Config\Entity\ConfigEntityListController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormInterface;

/**
 * User interface for the language overview screen.
 */
class LanguageListController extends ConfigEntityListController implements FormInterface {

  /**
   * {@inheritdoc}
   */
  public function load() {
    $entities = $this->storage->loadByProperties(array('locked' => '0'));
    uasort($entities, array($this->entityInfo['class'], 'sort'));
    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'language_admin_overview_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);
    $default = language_default();

    // Edit and delete path for Languages entities have a different pattern
    // than other config entities.
    $path = 'admin/config/regional/language';
    if (isset($operations['edit'])) {
      $operations['edit']['href'] = $path . '/edit/' . $entity->id();
    }
    if (isset($operations['delete'])) {
      $operations['delete']['href'] = $path . '/delete/' . $entity->id();
    }

    // Deleting the site default language is not allowed.
    if ($entity->id() == $default->id) {
      unset($operations['delete']);
    }

    return $operations;
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
  public function buildRow(EntityInterface $entity) {
    $row['#attributes']['class'][] = 'draggable';

    $row['label'] = array(
      '#markup' => check_plain($entity->get('label')),
    );

    $row['#weight'] = $entity->get('weight');
    // Add weight column.
    $row['weight'] = array(
      '#type' => 'weight',
      '#title' => t('Weight for @title', array('@title' => $entity->label())),
      '#title_display' => 'invisible',
      '#default_value' => $entity->get('weight'),
      '#attributes' => array('class' => array('weight')),
      '#delta' => 30,
    );

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $languages = $this->load();

    $form['languages'] = array(
      '#languages' => $languages,
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#empty' => t('There are no languages', array('@label' => $this->entityInfo['label'])),
      '#tabledrag' => array(
        array('order', 'sibling', 'weight'),
      ),
    );

    foreach ($languages as $entity) {
      $form['languages'][$entity->id()] = $this->buildRow($entity);
    }

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save configuration'),
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
    $languages = $form_state['values']['languages'];

    $language_entities = $this->load();
    foreach ($languages as $langcode => $language) {
      if (isset($language_entities[$langcode]) && $language['weight'] != $language_entities[$langcode]->get('weight')) {
        // Update changed weight.
        $language_entities[$langcode]->set('weight', $language['weight']);
        $language_entities[$langcode]->save();
      }
    }

    // Kill the static cache in language_list().
    drupal_static_reset('language_list');

    // Update weight of locked system languages.
    language_update_locked_weights();

    drupal_set_message(t('Configuration saved.'));
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return drupal_get_form($this);
  }
}

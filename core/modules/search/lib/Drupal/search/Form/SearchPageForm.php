<?php

/**
 * @file
 * Contains \Drupal\search\Form\SearchPageForm.
 */

namespace Drupal\search\Form;

use Drupal\Core\Entity\EntityFormController;

/**
 * Provides a search form for site wide search.
 */
class SearchPageForm extends EntityFormController {

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\search\SearchPageInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'search_form';
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, array &$form_state) {
    $plugin = $this->entity->getPlugin();

    $form_state['search_page_id'] = $this->entity->id();
    $form['basic'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('container-inline'),
      ),
    );
    $form['basic']['keys'] = array(
      '#type' => 'search',
      '#title' => $this->t('Enter your keywords'),
      '#default_value' => $plugin->getKeywords(),
      '#size' => 30,
      '#maxlength' => 255,
    );
    // processed_keys is used to coordinate keyword passing between other forms
    // that hook into the basic search form.
    $form['basic']['processed_keys'] = array(
      '#type' => 'value',
      '#value' => '',
    );
    $form['basic']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Search'),
    );
    // Allow the plugin to add to or alter the search form.
    $plugin->searchFormAlter($form, $form_state);

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, array &$form_state) {
    // The submit button is added in the form directly.
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    form_set_value($form['basic']['processed_keys'], trim($form_state['values']['keys']), $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $keys = $form_state['values']['processed_keys'];
    if ($keys == '') {
      $this->setFormError('keys', $form_state, $this->t('Please enter some keywords.'));
      // Fall through to the form redirect.
    }

    $form_state['redirect_route'] = array(
      'route_name' => 'search.view_' . $this->entity->id(),
      'route_parameters' => array(
        'keys' => $keys,
      ),
    );
  }

}

<?php

/**
 * @file
 * Contains \Drupal\search\Form\SearchPageForm.
 */

namespace Drupal\search\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a search form for site wide search.
 *
 * Search plugins can define method searchFormAlter() to alter the form. If they
 * have additional or substitute fields, they will need to override the form
 * submit, making sure to redirect with a GET parameter of 'keys' included, to
 * trigger the search being processed by the controller, and adding in any
 * additional query parameters they need to execute search.
 */
class SearchPageForm extends EntityForm {

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
  public function form(array $form, FormStateInterface $form_state) {
    $plugin = $this->entity->getPlugin();
    $form_state->set('search_page_id', $this->entity->id());

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
  protected function actions(array $form, FormStateInterface $form_state) {
    // The submit button is added in the form directly.
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Redirect to the search page with keywords in the GET parameters.
    // Plugins with additional search parameters will need to provide their
    // own form submit handler to replace this, so they can put their values
    // into the GET as well. If so, make sure to put 'keys' into the GET
    // parameters so that the search results generation is triggered.
    $query = $this->entity->getPlugin()->buildSearchUrlQuery($form_state);
    $route = 'search.view_' . $form_state->get('search_page_id');
    $form_state->setRedirect(
      $route,
      array(),
      array('query' => $query)
    );
  }
}

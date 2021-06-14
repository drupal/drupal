<?php

namespace Drupal\search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\search\SearchPageInterface;

/**
 * Provides a search form for site wide search.
 *
 * Search plugins can define method searchFormAlter() to alter the form. If they
 * have additional or substitute fields, they will need to override the form
 * submit, making sure to redirect with a GET parameter of 'keys' included, to
 * trigger the search being processed by the controller, and adding in any
 * additional query parameters they need to execute search.
 *
 * @internal
 */
class SearchPageForm extends FormBase {

  /**
   * The search page entity.
   *
   * @var \Drupal\search\SearchPageInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'search_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, SearchPageInterface $search_page = NULL) {
    $this->entity = $search_page;

    $plugin = $this->entity->getPlugin();
    $form_state->set('search_page_id', $this->entity->id());

    $form['basic'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['container-inline'],
      ],
    ];
    $form['basic']['keys'] = [
      '#type' => 'search',
      '#title' => $this->t('Enter your keywords'),
      '#default_value' => $plugin->getKeywords(),
      '#size' => 30,
      '#maxlength' => 255,
    ];

    // processed_keys is used to coordinate keyword passing between other forms
    // that hook into the basic search form.
    $form['basic']['processed_keys'] = [
      '#type' => 'value',
      '#value' => '',
    ];
    $form['basic']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
    ];

    $form['help_link'] = [
      '#type' => 'link',
      '#url' => new Url('search.help_' . $this->entity->id()),
      '#title' => $this->t('About searching'),
      '#options' => ['attributes' => ['class' => 'search-help-link']],
    ];

    // Allow the plugin to add to or alter the search form.
    $plugin->searchFormAlter($form, $form_state);
    return $form;
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
      [],
      ['query' => $query]
    );
  }

}

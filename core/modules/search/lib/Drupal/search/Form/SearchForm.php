<?php

/**
 * @file
 * Contains \Drupal\search\Form\SearchForm.
 */

namespace Drupal\search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\search\Plugin\SearchInterface;
use Drupal\search\SearchPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a search form for site wide search.
 */
class SearchForm extends FormBase {

  /**
   * The search plugin manager.
   *
   * @var \Drupal\search\SearchPluginManager
   */
  protected $searchManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.search')
    );
  }

  /**
   * Constructs a search form.
   *
   * @param \Drupal\search\SearchPluginManager $search_plugin
   *   The search plugin manager.
   */
  public function __construct(SearchPluginManager $search_plugin) {
    $this->searchManager = $search_plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'search_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, SearchInterface $plugin = NULL, $action = '', $prompt = NULL) {
    $plugin_info = $plugin->getPluginDefinition();

    if (!$action) {
      $action = 'search/' . $plugin_info['path'];
    }
    if (!isset($prompt)) {
      $prompt = $this->t('Enter your keywords');
    }

    $form['#action'] = $this->urlGenerator()->generateFromPath($action);
    // Record the $action for later use in redirecting.
    $form_state['action'] = $action;
    $form['plugin_id'] = array(
      '#type' => 'value',
      '#value' => $plugin->getPluginId(),
    );
    $form['basic'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('container-inline'),
      ),
    );
    $form['basic']['keys'] = array(
      '#type' => 'search',
      '#title' => $prompt,
      '#default_value' => $plugin->getKeywords(),
      '#size' => $prompt ? 40 : 20,
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

    return $form;
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
      form_set_error('keys', $form_state, t('Please enter some keywords.'));
      // Fall through to the form redirect.
    }

    $form_state['redirect'] = $form_state['action'] . '/' . $keys;
  }
}

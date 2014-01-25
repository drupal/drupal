<?php

/**
 * @file
 * Contains \Drupal\search_extra_type\Plugin\Search\SearchExtraTypeSearch.
 */

namespace Drupal\search_extra_type\Plugin\Search;

use Drupal\search\Plugin\ConfigurableSearchPluginBase;

/**
 * Executes a keyword search against the search index.
 *
 * @SearchPlugin(
 *   id = "search_extra_type_search",
 *   title = @Translation("Dummy search type")
 * )
 */
class SearchExtraTypeSearch extends ConfigurableSearchPluginBase {

  /**
   * {@inheritdoc}
   */
  public function setSearch($keywords, array $parameters, array $attributes) {
    if (empty($parameters['search_conditions'])) {
      $parameters['search_conditions'] = '';
    }
    parent::setSearch($keywords, $parameters, $attributes);
  }

  /**
   * Verifies if the given parameters are valid enough to execute a search for.
   *
   * @return bool
   *   A true or false depending on the implementation.
   */
  public function isSearchExecutable() {
    return (bool) ($this->keywords || !empty($this->searchParameters['search_conditions']));
  }

  /**
   * Execute the search.
   *
   * This is a dummy search, so when search "executes", we just return a dummy
   * result containing the keywords and a list of conditions.
   *
   * @return array
   *   A structured list of search results
   */
  public function execute() {
    $results = array();
    if (!$this->isSearchExecutable()) {
      return $results;
    }
    return array(
      array(
        'link' => url('node'),
        'type' => 'Dummy result type',
        'title' => 'Dummy title',
        'snippet' => "Dummy search snippet to display. Keywords: {$this->keywords}\n\nConditions: " . print_r($this->searchParameters, TRUE),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildResults() {
    $results = $this->execute();
    $output['prefix']['#markup'] = '<h2>Test page text is here</h2> <ol class="search-results">';

    foreach ($results as $entry) {
      $output[] = array(
        '#theme' => 'search_result',
        '#result' => $entry,
        '#plugin_id' => 'search_extra_type_search',
      );
    }
    $pager = array(
      '#theme' => 'pager',
    );
    $output['suffix']['#markup'] = '</ol>' . drupal_render($pager);

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, array &$form_state) {
    // Output form for defining rank factor weights.
    $form['extra_type_settings'] = array(
      '#type' => 'fieldset',
      '#title' => t('Extra type settings'),
      '#tree' => TRUE,
    );

    $form['extra_type_settings']['boost'] = array(
      '#type' => 'select',
      '#title' => t('Boost method'),
      '#options' => array(
        'bi' => t('Bistromathic'),
        'ii' => t('Infinite Improbability'),
      ),
      '#default_value' => $this->configuration['boost'],
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, array &$form_state) {
    $this->configuration['boost'] = $form_state['values']['extra_type_settings']['boost'];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'boost' => 'bi',
    );
  }

}

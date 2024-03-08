<?php

namespace Drupal\search_extra_type\Plugin\Search;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\search\Attribute\Search;
use Drupal\search\Plugin\ConfigurableSearchPluginBase;

/**
 * Executes a dummy keyword search.
 */
#[Search(
  id: 'search_extra_type_search',
  title: new TranslatableMarkup('Dummy search type'),
  use_admin_theme: TRUE,
)]
class SearchExtraTypeSearch extends ConfigurableSearchPluginBase {

  /**
   * {@inheritdoc}
   */
  public function setSearch($keywords, array $parameters, array $attributes) {
    if (empty($parameters['search_conditions'])) {
      $parameters['search_conditions'] = '';
    }
    parent::setSearch($keywords, $parameters, $attributes);
    return $this;
  }

  /**
   * Verifies if the given parameters are valid enough to execute a search for.
   *
   * @return bool
   *   TRUE if there are keywords or search conditions in the query.
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
    $results = [];
    if (!$this->isSearchExecutable()) {
      return $results;
    }
    return [
      [
        'link' => Url::fromRoute('test_page_test.test_page')->toString(),
        'type' => 'Dummy result type',
        'title' => 'Dummy title',
        'snippet' => new FormattableMarkup("Dummy search snippet to display. Keywords: @keywords\n\nConditions: @search_parameters", ['@keywords' => $this->keywords, '@search_parameters' => print_r($this->searchParameters, TRUE)]),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildResults() {
    $results = $this->execute();
    $output['prefix']['#markup'] = '<h2>Test page text is here</h2> <ol class="search-results">';

    foreach ($results as $entry) {
      $output[] = [
        '#theme' => 'search_result',
        '#result' => $entry,
        '#plugin_id' => 'search_extra_type_search',
      ];
    }
    $pager = [
      '#type' => 'pager',
    ];
    $output['suffix']['#markup'] = '</ol>' . \Drupal::service('renderer')->render($pager);

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // Output form for defining rank factor weights.
    $form['extra_type_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Extra type settings'),
      '#tree' => TRUE,
    ];

    $form['extra_type_settings']['boost'] = [
      '#type' => 'select',
      '#title' => $this->t('Boost method'),
      '#options' => [
        'bi' => $this->t('Bistro mathematics'),
        'ii' => $this->t('Infinite Improbability'),
      ],
      '#default_value' => $this->configuration['boost'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['boost'] = $form_state->getValue(['extra_type_settings', 'boost']);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'boost' => 'bi',
    ];
  }

}

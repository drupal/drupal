<?php

namespace Drupal\search\Plugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ConfigurableTrait;

/**
 * Provides a base implementation for a configurable Search plugin.
 */
abstract class ConfigurableSearchPluginBase extends SearchPluginBase implements ConfigurableSearchPluginInterface {

  use ConfigurableTrait;

  /**
   * The unique ID for the search page using this plugin.
   *
   * @var string
   */
  protected $searchPageId;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function setSearchPageId($search_page_id) {
    $this->searchPageId = $search_page_id;
    return $this;
  }

}

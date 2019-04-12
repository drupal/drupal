<?php

namespace Drupal\search\Plugin;

use Drupal\Component\Plugin\ConfigurableTrait;
use Drupal\Core\Form\FormStateInterface;

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

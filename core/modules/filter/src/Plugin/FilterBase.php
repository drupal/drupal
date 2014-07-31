<?php

/**
 * @file
 * Contains \Drupal\filter\Plugin\Filter\FilterBase.
 */

namespace Drupal\filter\Plugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;

/**
 * Provides a base class for Filter plugins.
 *
 * @see \Drupal\filter\Annotation\Filter
 * @see \Drupal\filter\FilterPluginManager
 * @see \Drupal\filter\Plugin\FilterInterface
 * @see plugin_api
 */
abstract class FilterBase extends PluginBase implements FilterInterface {

  /**
   * The plugin ID of this filter.
   *
   * @var string
   */
  protected $plugin_id;

  /**
   * The name of the module that owns this filter.
   *
   * @var string
   */
  public $provider;

  /**
   * A Boolean indicating whether this filter is enabled.
   *
   * @var bool
   */
  public $status = FALSE;

  /**
   * The weight of this filter compared to others in a filter collection.
   *
   * @see FilterBase::$filterBag
   *
   * @var int
   */
  public $weight = 0;

  /**
   * An associative array containing the configured settings of this filter.
   *
   * @var array
   */
  public $settings = array();

  /**
   * A collection of all filters this filter participates in.
   *
   * @var \Drupal\filter\FilterBag
   */
  protected $bag;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->provider = $this->pluginDefinition['provider'];

    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    if (isset($configuration['status'])) {
      $this->status = (bool) $configuration['status'];
    }
    if (isset($configuration['weight'])) {
      $this->weight = (int) $configuration['weight'];
    }
    if (isset($configuration['settings'])) {
      $this->settings = (array) $configuration['settings'];
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return array(
      'id' => $this->getPluginId(),
      'provider' => $this->pluginDefinition['provider'],
      'status' => $this->status,
      'weight' => $this->weight,
      'settings' => $this->settings,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'provider' => $this->pluginDefinition['provider'],
      'status' => FALSE,
      'weight' => $this->pluginDefinition['weight'] ?: 0,
      'settings' => $this->pluginDefinition['settings'],
    );
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->pluginDefinition['type'];
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['title'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    // Implementations should work with and return $form. Returning an empty
    // array here allows the text format administration form to identify whether
    // the filter plugin has any settings form elements.
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function prepare($text, $langcode) {
    return $text;
  }

  /**
   * {@inheritdoc}
   */
  public function getHTMLRestrictions() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
  }

}

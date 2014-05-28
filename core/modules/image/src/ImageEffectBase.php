<?php

/**
 * @file
 * Contains \Drupal\image\Annotation\ImageEffectBase.
 */

namespace Drupal\image;

use Drupal\Core\Plugin\PluginBase;

/**
 * Provides a base class for image effects.
 */
abstract class ImageEffectBase extends PluginBase implements ImageEffectInterface {

  /**
   * The image effect ID.
   *
   * @var string
   */
  protected $uuid;

  /**
   * The weight of the image effect.
   *
   * @var int|string
   */
  protected $weight = '';

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
  public function transformDimensions(array &$dimensions) {
    $dimensions['width'] = $dimensions['height'] = NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return array(
      '#markup' => '',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getUuid() {
    return $this->uuid;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->weight = $weight;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->weight;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return array(
      'uuid' => $this->getUuid(),
      'id' => $this->getPluginId(),
      'weight' => $this->getWeight(),
      'data' => $this->configuration,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $configuration += array(
      'data' => array(),
      'uuid' => '',
      'weight' => '',
    );
    $this->configuration = $configuration['data'] + $this->defaultConfiguration();
    $this->uuid = $configuration['uuid'];
    $this->weight = $configuration['weight'];
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return array();
  }

}

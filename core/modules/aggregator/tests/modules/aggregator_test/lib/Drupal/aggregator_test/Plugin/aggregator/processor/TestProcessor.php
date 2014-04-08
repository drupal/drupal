<?php

/**
 * @file
 * Contains \Drupal\aggregator_test\Plugin\aggregator\processor\TestProcessor.
 */

namespace Drupal\aggregator_test\Plugin\aggregator\processor;

use Drupal\aggregator\Plugin\AggregatorPluginSettingsBase;
use Drupal\aggregator\Plugin\ProcessorInterface;
use Drupal\aggregator\FeedInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a default processor implementation.
 *
 * Creates lightweight records from feed items.
 *
 * @AggregatorProcessor(
 *   id = "aggregator_test_processor",
 *   title = @Translation("Test processor"),
 *   description = @Translation("Test generic processor functionality.")
 * )
 */
class TestProcessor extends AggregatorPluginSettingsBase implements ProcessorInterface, ContainerFactoryPluginInterface {

  /**
   * Contains the configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * Constructs a TestProcessor object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The configuration factory object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config) {
    $this->configFactory = $config;
    parent::__construct($configuration + $this->getConfiguration(), $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, array &$form_state) {
    $processors = $this->configFactory->get('aggregator.settings')->get('processors');
    $info = $this->getPluginDefinition();

    $form['processors'][$info['id']] = array(
      '#type' => 'details',
      '#title' => t('Test processor settings'),
      '#description' => $info['description'],
      '#open' => in_array($info['id'], $processors),
    );
    // Add some dummy settings to verify settingsForm is called.
    $form['processors'][$info['id']]['dummy_length'] = array(
      '#title' => t('Dummy length setting'),
      '#type' => 'number',
      '#min' => 1,
      '#max' => 1000,
      '#default_value' => $this->configuration['items']['dummy_length'],
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, array &$form_state) {
    $this->configuration['items']['dummy_length'] = $form_state['values']['dummy_length'];
    $this->setConfiguration($this->configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function process(FeedInterface $feed) {
    foreach ($feed->items as &$item) {
      // Prepend our test string.
      $item['title'] = 'testProcessor' . $item['title'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete(FeedInterface $feed) {
    // Append a random number, just to change the feed description.
    $feed->description->value .= rand(0, 10);
  }

  /**
   * {@inheritdoc}
   */
  public function postProcess(FeedInterface $feed) {
    // Double the refresh rate.
    $feed->refresh->value *= 2;
    $feed->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configFactory->get('aggregator_test.settings')->get();
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $config = $this->configFactory->get('aggregator_test.settings');
    foreach ($configuration as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();
  }

}

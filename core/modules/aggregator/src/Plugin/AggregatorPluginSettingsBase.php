<?php

namespace Drupal\aggregator\Plugin;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Base class for aggregator plugins that implement settings forms.
 *
 * @see \Drupal\aggregator\Annotation\AggregatorParser
 * @see \Drupal\aggregator\Annotation\AggregatorFetcher
 * @see \Drupal\aggregator\Annotation\AggregatorProcessor
 * @see \Drupal\aggregator\Plugin\AggregatorPluginManager
 * @see \Drupal\aggregator\Plugin\FetcherInterface
 * @see \Drupal\aggregator\Plugin\ProcessorInterface
 * @see \Drupal\aggregator\Plugin\ParserInterface
 * @see plugin_api
 */
abstract class AggregatorPluginSettingsBase extends PluginBase implements PluginFormInterface, ConfigurableInterface, DependentPluginInterface, ConfigurablePluginInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
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

}

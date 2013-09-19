<?php

/**
 * @file
 * Contains \Drupal\aggregator\Plugin\AggregatorPluginSettingsBase.
 */

namespace Drupal\aggregator\Plugin;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Base class for aggregator plugins that implement settings forms.
 */
abstract class AggregatorPluginSettingsBase extends PluginBase implements PluginFormInterface, ConfigurablePluginInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, array &$form_state) {
  }

}

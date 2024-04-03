<?php

namespace Drupal\migrate;

use Drupal\Core\Plugin\PluginBase;
use Drupal\migrate\Plugin\MigrateProcessInterface;

/**
 * The base class for all migrate process plugins.
 *
 * Migrate process plugins are taking a value and transform them. For example,
 * transform a human provided name into a machine name, look up an identifier
 * in a previous migration and so on.
 *
 * Process plugins extending this class can use any number of methods, thus
 * offering different alternative ways of processing. In this case, the
 * transform() method should not be implemented, and the plugin configuration
 * must provide the name of the method to be called via the "method" key. Each
 * method must have the same signature as transform().
 *
 * @see https://www.drupal.org/node/2129651
 * @see \Drupal\migrate\Plugin\MigratePluginManager
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 * @see \Drupal\migrate\Attribute\MigrateProcess
 * @see \Drupal\migrate\Plugin\migrate\process\SkipOnEmpty
 * @see d7_field_formatter_settings.yml
 * @see plugin_api
 *
 * @ingroup migration
 */
abstract class ProcessPluginBase extends PluginBase implements MigrateProcessInterface {

  /**
   * Determines if processing of the pipeline is stopped.
   *
   * @var bool
   */
  protected bool $stopPipeline = FALSE;

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Do not call this method from children.
    if (isset($this->configuration['method'])) {
      if (method_exists($this, $this->configuration['method'])) {
        return $this->{$this->configuration['method']}($value, $migrate_executable, $row, $destination_property);
      }
      throw new \BadMethodCallException(sprintf('The %s method does not exist in the %s plugin.', $this->configuration['method'], $this->pluginId));
    }
    else {
      throw new \BadMethodCallException(sprintf('The "method" key in the plugin configuration must to be set for the %s plugin.', $this->pluginId));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function multiple() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isPipelineStopped(): bool {
    return $this->stopPipeline;
  }

  /**
   * {@inheritdoc}
   */
  public function reset(): void {
    $this->stopPipeline = FALSE;
  }

  /**
   * Stops pipeline processing after this plugin finishes.
   */
  protected function stopPipeline(): void {
    $this->stopPipeline = TRUE;
  }

}

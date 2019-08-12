<?php

namespace Drupal\Core\Entity\EntityReferenceSelection;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;

/**
 * Provides a base class for configurable selection handlers.
 */
abstract class SelectionPluginBase extends PluginBase implements SelectionInterface, ConfigurableInterface, DependentPluginInterface, ConfigurablePluginInterface {

  /**
   * Constructs a new selection object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'target_type' => NULL,
      // @todo Remove this key in Drupal 9.0.x.
      'handler' => $this->getPluginId(),
      'entity' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    // Resolve backward compatibility level configurations, if any.
    $this->resolveBackwardCompatibilityConfiguration($configuration);

    // Merge in defaults.
    $this->configuration = NestedArray::mergeDeep(
      $this->defaultConfiguration(),
      $configuration
    );

    // Ensure a backward compatibility level configuration.
    $this->ensureBackwardCompatibilityConfiguration();
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
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function entityQueryAlter(SelectInterface $query) {}

  /**
   * Moves the backward compatibility level configurations in the right place.
   *
   * In order to keep backward compatibility, we copy all settings, except
   * 'target_type', 'handler' and 'entity' under 'handler_settings', following
   * the structure from the field config. If the plugin was instantiated using
   * the 'handler_settings' level, those values will be used. In case of
   * conflict, the root level settings will take precedence. The backward
   * compatibility aware configuration will have the next structure:
   * - target_type
   * - handler (will be removed in Drupal 9.0.x, it's the plugin id)
   * - entity
   * - setting_1
   * - setting_2
   *   ...
   * - setting_N
   * - handler_settings: (will be removed in Drupal 9.0.x)
   *   - setting_1
   *   - setting_2
   *     ...
   *   - setting_N
   *
   * @param array $configuration
   *   The configuration array to be altered.
   *
   * @internal
   *
   * @todo Remove this method call and its method in Drupal 9.
   *
   * @see https://www.drupal.org/project/drupal/issues/3069757
   * @see https://www.drupal.org/node/2870971
   */
  protected function resolveBackwardCompatibilityConfiguration(array &$configuration) {
    if (isset($this->defaultConfiguration()['handler_settings'])) {
      throw new \InvalidArgumentException("{$this->getPluginDefinition()['class']}::defaultConfiguration() should not contain a 'handler_settings' key. All settings should be placed in the root level.");
    }

    // Extract the BC level from the passed configuration, if any.
    if (array_key_exists('handler_settings', $configuration)) {
      if (!is_array($configuration['handler_settings'])) {
        throw new \InvalidArgumentException("The setting 'handler_settings' is reserved and cannot be used.");
      }
      @trigger_error("Providing settings under 'handler_settings' is deprecated in drupal:8.4.0 support for 'handler_settings' is removed from drupal:9.0.0. Move the settings in the root of the configuration array. See https://www.drupal.org/node/2870971", E_USER_DEPRECATED);

      // Settings passed in the root level take precedence over BC settings.
      $configuration += $configuration['handler_settings'];
      unset($configuration['handler_settings']);
    }
  }

  /**
   * Ensures a backward compatibility level configuration.
   *
   * @internal
   *
   * @todo Remove this method call and its method in Drupal 9.
   *
   * @see https://www.drupal.org/project/drupal/issues/3069757
   * @see https://www.drupal.org/node/2870971
   */
  protected function ensureBackwardCompatibilityConfiguration() {
    $keys = ['handler', 'target_type', 'entity', 'handler_settings'];
    // Synchronize back 'handler_settings'.
    foreach ($this->configuration as $key => $value) {
      // Filter out keys that belong strictly to the root level.
      if (!in_array($key, $keys, TRUE)) {
        $this->configuration['handler_settings'][$key] = $value;
      }
    }
  }

}

<?php

namespace Drupal\language\Plugin\migrate\process;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Processes the arrays for the language types' negotiation methods and weights.
 *
 * @deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. There is no
 *   replacement.
 *
 * @see https://www.drupal.org/node/3533560
 */
#[MigrateProcess(
  id: "language_negotiation",
  handle_multiples: TRUE,
)]
class LanguageNegotiation extends ProcessPluginBase {

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    @trigger_error(__CLASS__ . ' is deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. There is no replacement. See https://www.drupal.org/node/3533560', E_USER_DEPRECATED);
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $new_value = [
      'enabled' => [],
      'method_weights' => [],
    ];

    if (!is_array($value)) {
      throw new MigrateException('The input should be an array');
    }

    // If no weights are provided, use the keys by flipping the array.
    if (empty($value[1])) {
      $new_value['enabled'] = array_flip(array_map([$this, 'mapNewMethods'], array_keys($value[0])));
      unset($new_value['method_weights']);
    }
    else {
      foreach ($value[1] as $method => $weight) {
        $new_method = $this->mapNewMethods($method);
        $new_value['method_weights'][$new_method] = $weight;
        if (in_array($method, array_keys($value[0]))) {
          $new_value['enabled'][$new_method] = $weight;
        }
      }
    }

    return $new_value;
  }

  /**
   * Maps old negotiation method names to the new ones.
   *
   * @param string $value
   *   The old negotiation method name.
   *
   * @return string
   *   The new negotiation method name.
   */
  protected function mapNewMethods($value) {
    switch ($value) {
      case 'language-default':
        return 'language-selected';

      case 'locale-browser':
        return 'language-browser';

      case 'locale-interface':
        return 'language-interface';

      case 'locale-session':
        return 'language-session';

      case 'locale-url':
        return 'language-url';

      case 'locale-url-fallback':
        return 'language-url-fallback';

      case 'locale-user':
        return 'language-user';

      default:
        return $value;
    }
  }

}

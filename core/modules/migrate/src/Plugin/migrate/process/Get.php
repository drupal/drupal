<?php

namespace Drupal\migrate\Plugin\migrate\process;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Gets the source value.
 *
 * Available configuration keys:
 * - source: Source property.
 *
 * The get plugin returns the value of the property given by the "source"
 * configuration key.
 *
 * If the source configuration contains an empty value, the current value in the
 * process pipeline will be used. This applies when source is a list of source
 * properties and one of the list items is empty, such as null, an empty string,
 * or an empty array.
 *
 * The string '0' is not treated as empty.
 *
 * This makes it impossible to reference a source property with an empty string
 * as its name.
 *
 * Examples:
 *
 * @code
 * process:
 *   bar:
 *     plugin: get
 *     source: foo
 * @endcode
 *
 * This copies the source value of foo to the destination property "bar".
 *
 * Since get is the default process plugin, it can be shorthanded like this:
 *
 * @code
 * process:
 *   bar: foo
 * @endcode
 *
 * Get also supports a list of source properties.
 *
 * Example:
 *
 * @code
 * process:
 *   bar:
 *     plugin: get
 *     source:
 *       - foo1
 *       - foo2
 * @endcode
 *
 * This copies the array of source values [foo1, foo2] to the destination
 * property "bar".
 *
 * Get also supports copying destination values. These are indicated by a
 * starting @ sign. Values using @ must be wrapped in quotes.
 *
 * @code
 * process:
 *   foo:
 *     plugin: machine_name
 *     source: baz
 *   bar:
 *     plugin: get
 *     source: '@foo'
 * @endcode
 *
 * This will simply copy the destination value of foo to the destination
 * property bar. foo configuration is included for illustration purposes.
 *
 * Because of this, if the source or destination property actually starts with a
 * "@", that character must be escaped with "@@". The referenced property
 * becomes, for example, "@@@foo".
 *
 * @code
 * process:
 *   '@foo':
 *     plugin: machine_name
 *     source: baz
 *   bar:
 *     plugin: get
 *     source: '@@@foo'
 * @endcode
 *
 * This should occur extremely rarely.
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 */
#[MigrateProcess(
  id: "get",
  handle_multiples: TRUE,
)]
class Get extends ProcessPluginBase {

  /**
   * Flag indicating whether there are multiple values.
   *
   * @var bool
   */
  protected $multiple;

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $source = $this->configuration['source'];
    $properties = is_string($source) ? [$source] : $source;
    $return = [];
    foreach ($properties as $property) {
      if ($property || (string) $property === '0') {
        $return[] = $row->get($property);
      }
      else {
        $return[] = $value;
      }
    }

    if (is_string($source)) {
      $this->multiple = is_array($return[0]);
      return $return[0];
    }
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function multiple() {
    return $this->multiple;
  }

}

<?php

/**
 * @file
 * Definition of Drupal\Core\TypedData\TypedDataManager.
 */

namespace Drupal\Core\TypedData;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Core\Plugin\Discovery\CacheDecorator;
use Drupal\Core\Plugin\Discovery\HookDiscovery;

/**
 * Manages data type plugins.
 */
class TypedDataManager extends PluginManagerBase {

  public function __construct() {
    $this->discovery = new CacheDecorator(new HookDiscovery('data_type_info'), 'typed_data:types');
    $this->factory = new TypedDataFactory($this->discovery);
  }

  /**
   * Implements Drupal\Component\Plugin\PluginManagerInterface::createInstance().
   *
   * @param string $plugin_id
   *   The id of a plugin, i.e. the data type.
   * @param array $configuration
   *   The plugin configuration, i.e. the data definition.
   *
   * @return Drupal\Core\TypedData\TypedDataInterface
   */
  public function createInstance($plugin_id, array $configuration) {
    return $this->factory->createInstance($plugin_id, $configuration);
  }

  /**
   * Creates a new typed data object wrapping the passed value.
   *
   * @param array $definition
   *   The data definition array with the following array keys and values:
   *   - type: The data type of the data to wrap. Required.
   *   - label: A human readable label.
   *   - description: A human readable description.
   *   - list: Whether the data is multi-valued, i.e. a list of data items.
   *     Defaults to FALSE.
   *   - computed: A boolean specifying whether the data value is computed by
   *     the object, e.g. depending on some other values.
   *   - read-only: A boolean specifying whether the data is read-only. Defaults
   *     to TRUE for computed properties, to FALSE otherwise.
   *   - class: If set and 'list' is FALSE, the class to use for creating the
   *     typed data object; otherwise the default class of the data type will be
   *     used.
   *   - list class: If set and 'list' is TRUE, the class to use for creating
   *     the typed data object; otherwise the default list class of the data
   *     type will be used.
   *   - settings: An array of settings, as required by the used 'class'. See
   *     the documentation of the class for supported or required settings.
   *   - list settings: An array of settings as required by the used
   *     'list class'. See the documentation of the list class for support or
   *     required settings.
   *   - constraints: An array of type specific value constraints, e.g. for data
   *     of type 'entity' the 'entity type' and 'bundle' may be specified. See
   *     the documentation of the data type 'class' for supported constraints.
   *   - required: A boolean specifying whether a non-NULL value is mandatory.
   *   Further keys may be supported in certain usages, e.g. for further keys
   *   supported for entity field definitions see
   *   Drupal\Core\Entity\StorageControllerInterface::getPropertyDefinitions().
   * @param mixed $value
   *   (optional) The data value. If set, it has to match one of the supported
   *   data type format as documented for the data type classes.
   * @param array $context
   *   (optional) An array describing the context of the data object, e.g. its
   *   name or parent data structure. The context should be passed if a typed
   *   data object is created as part of a data structure. The following keys
   *   are supported:
   *   - name: The name associated with the data.
   *   - parent: The parent object containing the data. Must be an instance of
   *     Drupal\Core\TypedData\ComplexDataInterface or
   *     Drupal\Core\TypedData\ListInterface.
   *
   * @return Drupal\Core\TypedData\TypedDataInterface
   *
   * @see typed_data()
   * @see Drupal\Core\TypedData\Type\Integer
   * @see Drupal\Core\TypedData\Type\Float
   * @see Drupal\Core\TypedData\Type\String
   * @see Drupal\Core\TypedData\Type\Boolean
   * @see Drupal\Core\TypedData\Type\Duration
   * @see Drupal\Core\TypedData\Type\Date
   * @see Drupal\Core\TypedData\Type\Uri
   * @see Drupal\Core\TypedData\Type\Binary
   * @see Drupal\Core\Entity\Field\EntityWrapper
   */
  function create(array $definition, $value = NULL, array $context = array()) {
    $wrapper = $this->createInstance($definition['type'], $definition);
    if (isset($value)) {
      $wrapper->setValue($value);
    }
    if ($wrapper instanceof ContextAwareInterface) {
      if (isset($context['name'])) {
        $wrapper->setName($context['name']);
      }
      if (isset($context['parent'])) {
        $wrapper->setParent($context['parent']);
      }
    }
    return $wrapper;
  }
}

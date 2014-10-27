<?php

/**
 * @file
 * Contains \Drupal\Core\TypedData\TypedDataManager.
 */

namespace Drupal\Core\TypedData;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\String;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\TypedData\Validation\MetadataFactory;
use Drupal\Core\Validation\ConstraintManager;
use Drupal\Core\Validation\DrupalTranslator;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Manages data type plugins.
 */
class TypedDataManager extends DefaultPluginManager {

  /**
   * The validator used for validating typed data.
   *
   * @var \Symfony\Component\Validator\ValidatorInterface
   */
  protected $validator;

  /**
   * The validation constraint manager to use for instantiating constraints.
   *
   * @var \Drupal\Core\Validation\ConstraintManager
   */
  protected $constraintManager;

  /**
   * An array of typed data property prototypes.
   *
   * @var array
   */
  protected $prototypes = array();

 /**
  * Constructs a new TypedDataManager.
  *
  * @param \Traversable $namespaces
  *   An object that implements \Traversable which contains the root paths
  *   keyed by the corresponding namespace to look for plugin implementations.
  * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
  *   Cache backend instance to use.
  * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
  *   The module handler.
  */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    $this->alterInfo('data_type_info');
    $this->setCacheBackend($cache_backend, 'typed_data_types_plugins');

    parent::__construct('Plugin/DataType', $namespaces, $module_handler, NULL, 'Drupal\Core\TypedData\Annotation\DataType');
  }

  /**
   * Instantiates a typed data object.
   *
   * @param string $data_type
   *   The data type, for which a typed object should be instantiated.
   * @param array $configuration
   *   The plugin configuration array, i.e. an array with the following keys:
   *   - data definition: The data definition object, i.e. an instance of
   *     \Drupal\Core\TypedData\DataDefinitionInterface.
   *   - name: (optional) If a property or list item is to be created, the name
   *     of the property or the delta of the list item.
   *   - parent: (optional) If a property or list item is to be created, the
   *     parent typed data object implementing either the ListInterface or the
   *     ComplexDataInterface.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface
   *   The instantiated typed data object.
   */
  public function createInstance($data_type, array $configuration = array()) {
    $data_definition = $configuration['data_definition'];
    $type_definition = $this->getDefinition($data_type);

    if (!isset($type_definition)) {
      throw new \InvalidArgumentException(format_string('Invalid data type %plugin_id has been given.', array('%plugin_id' => $data_type)));
    }

    // Allow per-data definition overrides of the used classes, i.e. take over
    // classes specified in the type definition.
    $class = $data_definition->getClass();

    if (!isset($class)) {
      throw new PluginException(sprintf('The plugin (%s) did not specify an instance class.', $data_type));
    }
    return $class::createInstance($data_definition, $configuration['name'], $configuration['parent']);
  }

  /**
   * Creates a new typed data object instance.
   *
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $definition
   *   The data definition of the typed data object. For backwards-compatibility
   *   an array representation of the data definition may be passed also.
   * @param mixed $value
   *   (optional) The data value. If set, it has to match one of the supported
   *   data type format as documented for the data type classes.
   * @param string $name
   *   (optional) If a property or list item is to be created, the name of the
   *   property or the delta of the list item.
   * @param mixed $parent
   *   (optional) If a property or list item is to be created, the parent typed
   *   data object implementing either the ListInterface or the
   *   ComplexDataInterface.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface
   *   The instantiated typed data object.
   *
   * @see \Drupal::typedDataManager()
   * @see \Drupal\Core\TypedData\TypedDataManager::getPropertyInstance()
   * @see \Drupal\Core\TypedData\Plugin\DataType\Integer
   * @see \Drupal\Core\TypedData\Plugin\DataType\Float
   * @see \Drupal\Core\TypedData\Plugin\DataType\String
   * @see \Drupal\Core\TypedData\Plugin\DataType\Boolean
   * @see \Drupal\Core\TypedData\Plugin\DataType\Duration
   * @see \Drupal\Core\TypedData\Plugin\DataType\Date
   * @see \Drupal\Core\TypedData\Plugin\DataType\Uri
   * @see \Drupal\Core\TypedData\Plugin\DataType\Binary
   */
  public function create(DataDefinitionInterface $definition, $value = NULL, $name = NULL, $parent = NULL) {
    $typed_data = $this->createInstance($definition->getDataType(), array(
      'data_definition' => $definition,
      'name' => $name,
      'parent' => $parent,
    ));
    if (isset($value)) {
      $typed_data->setValue($value, FALSE);
    }
    return $typed_data;
  }

  /**
   * Creates a new data definition object.
   *
   * While data definitions objects may be created directly if the definition
   * class used by a data type is known, this method allows the creation of data
   * definitions for any given data type.
   *
   * E.g., if a definition for a map is to be created, the following code
   * could be used instead of calling this method with the argument 'map':
   * @code
   *   $map_definition = \Drupal\Core\TypedData\MapDataDefinition::create();
   * @endcode
   *
   * @param string $data_type
   *   The data type, for which a data definition should be created.
   *
   * @return \Drupal\Core\TypedData\DataDefinitionInterface
   *   A data definition for the given data type.
   *
   * @see \Drupal\Core\TypedData\TypedDataManager::createListDataDefinition()
   */
  public function createDataDefinition($data_type) {
    $type_definition = $this->getDefinition($data_type);
    if (!isset($type_definition)) {
      throw new \InvalidArgumentException(format_string('Invalid data type %plugin_id has been given.', array('%plugin_id' => $data_type)));
    }
    $class = $type_definition['definition_class'];
    return $class::createFromDataType($data_type);
  }

  /**
   * Creates a new list data definition for items of the given data type.
   *
   * @param string $item_type
   *   The item type, for which a list data definition should be created.
   *
   * @return \Drupal\Core\TypedData\ListDataDefinitionInterface
   *   A list definition for items of the given data type.
   *
   * @see \Drupal\Core\TypedData\TypedDataManager::createDataDefinition()
   */
  public function createListDataDefinition($item_type) {
    $type_definition = $this->getDefinition($item_type);
    if (!isset($type_definition)) {
      throw new \InvalidArgumentException(format_string('Invalid data type %plugin_id has been given.', array('%plugin_id' => $item_type)));
    }
    $class = $type_definition['list_definition_class'];
    return $class::createFromItemType($item_type);
  }

  /**
   * Implements \Drupal\Component\Plugin\PluginManagerInterface::getInstance().
   *
   * @param array $options
   *   An array of options with the following keys:
   *   - object: The parent typed data object, implementing the
   *     TypedDataInterface and either the ListInterface or the
   *     ComplexDataInterface.
   *   - property: The name of the property to instantiate, or the delta of the
   *     the list item to instantiate.
   *   - value: The value to set. If set, it has to match one of the supported
   *     data type formats as documented by the data type classes.
   *
   * @throws \InvalidArgumentException
   *   If the given property is not known, or the passed object does not
   *   implement the ListInterface or the ComplexDataInterface.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface
   *   The new property instance.
   *
   * @see \Drupal\Core\TypedData\TypedDataManager::getPropertyInstance()
   */
  public function getInstance(array $options) {
    return $this->getPropertyInstance($options['object'], $options['property'], $options['value']);
  }

  /**
   * Get a typed data instance for a property of a given typed data object.
   *
   * This method will use prototyping for fast and efficient instantiation of
   * many property objects with the same property path; e.g.,
   * when multiple comments are used comment_body.0.value needs to be
   * instantiated very often.
   * Prototyping is done by the root object's data type and the given
   * property path, i.e. all property instances having the same property path
   * and inheriting from the same data type are prototyped.
   *
   * @param \Drupal\Core\TypedData\TypedDataInterface $object
   *   The parent typed data object, implementing the TypedDataInterface and
   *   either the ListInterface or the ComplexDataInterface.
   * @param string $property_name
   *   The name of the property to instantiate, or the delta of an list item.
   * @param mixed $value
   *   (optional) The data value. If set, it has to match one of the supported
   *   data type formats as documented by the data type classes.
   *
   * @throws \InvalidArgumentException
   *   If the given property is not known, or the passed object does not
   *   implement the ListInterface or the ComplexDataInterface.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface
   *   The new property instance.
   *
   * @see \Drupal\Core\TypedData\TypedDataManager::create()
   */
  public function getPropertyInstance(TypedDataInterface $object, $property_name, $value = NULL) {
    $definition = $object->getRoot()->getDataDefinition();
    // If the definition is a list, we need to look at the data type and the
    // settings of its item definition.
    if ($definition instanceof ListDataDefinition) {
      $definition = $definition->getItemDefinition();
    }
    $key = $definition->getDataType();
    if ($settings = $definition->getSettings()) {
      $key .= ':' . Crypt::hashBase64(serialize($settings));
    }
    $key .= ':' . $object->getPropertyPath() . '.';
    // If we are creating list items, we always use 0 in the key as all list
    // items look the same.
    $key .= is_numeric($property_name) ? 0 : $property_name;

    // Make sure we have a prototype. Then, clone the prototype and set object
    // specific values, i.e. the value and the context.
    if (!isset($this->prototypes[$key]) || !$key) {
      // Create the initial prototype. For that we need to fetch the definition
      // of the to be created property instance from the parent.
      if ($object instanceof ComplexDataInterface) {
        $definition = $object->getDataDefinition()->getPropertyDefinition($property_name);
      }
      elseif ($object instanceof ListInterface) {
        $definition = $object->getItemDefinition();
      }
      else {
        throw new \InvalidArgumentException("The passed object has to either implement the ComplexDataInterface or the ListInterface.");
      }
      // Make sure we have got a valid definition.
      if (!$definition) {
        throw new \InvalidArgumentException('Property ' . String::checkPlain($property_name) . ' is unknown.');
      }
      // Now create the prototype using the definition, but do not pass the
      // given value as it will serve as prototype for any further instance.
      $this->prototypes[$key] = $this->create($definition, NULL, $property_name, $object);
    }

    // Clone from the prototype, then update the parent relationship and set the
    // data value if necessary.
    $property = clone $this->prototypes[$key];
    $property->setContext($property_name, $object);
    if (isset($value)) {
      $property->setValue($value, FALSE);
    }
    return $property;
  }

  /**
   * Sets the validator for validating typed data.
   *
   * @param \Symfony\Component\Validator\Validator\ValidatorInterface $validator
   *   The validator object to set.
   */
  public function setValidator(ValidatorInterface $validator) {
    $this->validator = $validator;
  }

  /**
   * Gets the validator for validating typed data.
   *
   * @return \Symfony\Component\Validator\Validator\ValidatorInterface
   *   The validator object.
   */
  public function getValidator() {
    if (!isset($this->validator)) {
      $this->validator = Validation::createValidatorBuilder()
        ->setMetadataFactory(new MetadataFactory())
        ->setTranslator(new DrupalTranslator())
        ->setApiVersion(Validation::API_VERSION_2_4)
        ->getValidator();
    }
    return $this->validator;
  }

  /**
   * Sets the validation constraint manager.
   *
   * The validation constraint manager is used to instantiate validation
   * constraint plugins.
   *
   * @param \Drupal\Core\Validation\ConstraintManager
   *   The constraint manager to set.
   */
  public function setValidationConstraintManager(ConstraintManager $constraintManager) {
    $this->constraintManager = $constraintManager;
  }

  /**
   * Gets the validation constraint manager.
   *
   * @return \Drupal\Core\Validation\ConstraintManager
   *   The constraint manager.
   */
  public function getValidationConstraintManager() {
    return $this->constraintManager;
  }

  /**
   * Gets default constraints for the given data definition.
   *
   * This generates default constraint definitions based on the data definition;
   * e.g. a NotNull constraint is generated if the data is defined as required.
   * Besides that any constraints defined for the data type, i.e. below the
   * 'constraint' key of the type's plugin definition, are taken into account.
   *
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $definition
   *   A data definition.
   *
   * @return array
   *   An array of validation constraint definitions, keyed by constraint name.
   *   Each constraint definition can be used for instantiating
   *   \Symfony\Component\Validator\Constraint objects.
   */
  public function getDefaultConstraints(DataDefinitionInterface $definition) {
    $constraints = array();
    $type_definition = $this->getDefinition($definition->getDataType());
    // Auto-generate a constraint for data types implementing a primitive
    // interface.
    if (is_subclass_of($type_definition['class'], '\Drupal\Core\TypedData\PrimitiveInterface')) {
      $constraints['PrimitiveType'] = array();
    }
    // Add in constraints specified by the data type.
    if (isset($type_definition['constraints'])) {
      $constraints += $type_definition['constraints'];
    }
    // Add the NotNull constraint for required data.
    if ($definition->isRequired()) {
      $constraints['NotNull'] = array();
    }
    // Check if the class provides allowed values.
    if (is_subclass_of($definition->getClass(),'Drupal\Core\TypedData\OptionsProviderInterface')) {
      $constraints['AllowedValues'] = array();
    }
    // Add any constraints about referenced data.
    if ($definition instanceof DataReferenceDefinitionInterface) {
      $constraints += $definition->getTargetDefinition()->getConstraints();
    }
    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedDefinitions() {
    parent::clearCachedDefinitions();
    $this->prototypes = array();
  }

}

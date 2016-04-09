<?php

namespace Drupal\Core\TypedData;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\TypedData\Validation\ExecutionContextFactory;
use Drupal\Core\TypedData\Validation\RecursiveValidator;
use Drupal\Core\Validation\ConstraintManager;
use Drupal\Core\Validation\ConstraintValidatorFactory;
use Drupal\Core\Validation\DrupalTranslator;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Manages data type plugins.
 */
class TypedDataManager extends DefaultPluginManager implements TypedDataManagerInterface {
  use DependencySerializationTrait;

  /**
   * The validator used for validating typed data.
   *
   * @var \Symfony\Component\Validator\Validator\ValidatorInterface
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
   * The class resolver.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface
   */
  protected $classResolver;

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
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   The class resolver.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, ClassResolverInterface $class_resolver) {
    $this->alterInfo('data_type_info');
    $this->setCacheBackend($cache_backend, 'typed_data_types_plugins');
    $this->classResolver = $class_resolver;

    parent::__construct('Plugin/DataType', $namespaces, $module_handler, NULL, 'Drupal\Core\TypedData\Annotation\DataType');
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($data_type, array $configuration = array()) {
    $data_definition = $configuration['data_definition'];
    $type_definition = $this->getDefinition($data_type);

    if (!isset($type_definition)) {
      throw new \InvalidArgumentException("Invalid data type '$data_type' has been given");
    }

    // Allow per-data definition overrides of the used classes, i.e. take over
    // classes specified in the type definition.
    $class = $data_definition->getClass();

    if (!isset($class)) {
      throw new PluginException(sprintf('The plugin (%s) did not specify an instance class.', $data_type));
    }
    $typed_data = $class::createInstance($data_definition, $configuration['name'], $configuration['parent']);
    $typed_data->setTypedDataManager($this);
    return $typed_data;
  }

  /**
   * {@inheritdoc}
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
   * {@inheritdoc}
   */
  public function createDataDefinition($data_type) {
    $type_definition = $this->getDefinition($data_type);
    if (!isset($type_definition)) {
      throw new \InvalidArgumentException("Invalid data type '$data_type' has been given");
    }
    $class = $type_definition['definition_class'];
    return $class::createFromDataType($data_type);
  }

  /**
   * {@inheritdoc}
   */
  public function createListDataDefinition($item_type) {
    $type_definition = $this->getDefinition($item_type);
    if (!isset($type_definition)) {
      throw new \InvalidArgumentException("Invalid data type '$item_type' has been given");
    }
    $class = $type_definition['list_definition_class'];
    return $class::createFromItemType($item_type);
  }

  /**
   * {@inheritdoc}
   */
  public function getInstance(array $options) {
    return $this->getPropertyInstance($options['object'], $options['property'], $options['value']);
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyInstance(TypedDataInterface $object, $property_name, $value = NULL) {
    // For performance, try to reuse existing prototypes instead of
    // constructing new objects when possible. A prototype is reused when
    // creating a data object:
    // - for a similar root object (same data type and settings),
    // - at the same property path under that root object.
    $root_definition = $object->getRoot()->getDataDefinition();
    // If the root object is a list, we want to look at the data type and the
    // settings of its item definition.
    if ($root_definition instanceof ListDataDefinition) {
      $root_definition = $root_definition->getItemDefinition();
    }

    // Root data type and settings.
    $parts[] = $root_definition->getDataType();
    if ($settings = $root_definition->getSettings()) {
      // Hash the settings into a string. crc32 is the fastest way to hash
      // something for non-cryptographic purposes.
      $parts[] = hash('crc32b', serialize($settings));
    }
    // Property path for the requested data object. When creating a list item,
    // use 0 in the key as all items look the same.
    $parts[] = $object->getPropertyPath() . '.' . (is_numeric($property_name) ? 0 : $property_name);
    $key = implode(':', $parts);

    // Create the prototype if needed.
    if (!isset($this->prototypes[$key])) {
      // Fetch the data definition for the child object from the parent.
      if ($object instanceof ComplexDataInterface) {
        $definition = $object->getDataDefinition()->getPropertyDefinition($property_name);
      }
      elseif ($object instanceof ListInterface) {
        $definition = $object->getItemDefinition();
      }
      else {
        throw new \InvalidArgumentException("The passed object has to either implement the ComplexDataInterface or the ListInterface.");
      }
      if (!$definition) {
        throw new \InvalidArgumentException("Property $property_name is unknown.");
      }
      // Create the prototype without any value, but with initial parenting
      // so that constructors can set up the objects correclty.
      $this->prototypes[$key] = $this->create($definition, NULL, $property_name, $object);
    }

    // Clone the prototype, update its parenting information, and assign the
    // value.
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
   * {@inheritdoc}
   */
  public function getValidator() {
    if (!isset($this->validator)) {
      $this->validator = new RecursiveValidator(
        new ExecutionContextFactory(new DrupalTranslator()),
        new ConstraintValidatorFactory($this->classResolver),
        $this
      );
    }
    return $this->validator;
  }

  /**
   * {@inheritdoc}
   */
  public function setValidationConstraintManager(ConstraintManager $constraintManager) {
    $this->constraintManager = $constraintManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getValidationConstraintManager() {
    return $this->constraintManager;
  }

  /**
   * {@inheritdoc}
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
    if (is_subclass_of($definition->getClass(), 'Drupal\Core\TypedData\OptionsProviderInterface')) {
      $constraints['AllowedValues'] = array();
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

  /**
   * {@inheritdoc}
   */
  public function getCanonicalRepresentation(TypedDataInterface $data) {
    $data_definition = $data->getDataDefinition();
    // In case a list is passed, respect the 'wrapped' key of its data type.
    if ($data_definition instanceof ListDataDefinitionInterface) {
      $data_definition = $data_definition->getItemDefinition();
    }
    // Get the plugin definition of the used data type.
    $type_definition = $this->getDefinition($data_definition->getDataType());
    if (!empty($type_definition['unwrap_for_canonical_representation'])) {
      return $data->getValue();
    }
    return $data;
  }

}

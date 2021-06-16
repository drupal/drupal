<?php

namespace Drupal\Core\TypedData\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a data type annotation object.
 *
 * The typed data API allows modules to support any kind of data based upon
 * pre-defined primitive types and interfaces for complex data and lists.
 *
 * Defined data types may map to one of the pre-defined primitive types in
 * \Drupal\Core\TypedData\Primitive or may be complex data types, containing on
 * or more data properties. Typed data objects for complex data types have to
 * implement the \Drupal\Core\TypedData\ComplexDataInterface. Further interface
 * that may be implemented are:
 *  - \Drupal\Core\Access\AccessibleInterface
 *  - \Drupal\Core\TypedData\TranslatableInterface
 *
 * Furthermore, lists of data items are represented by objects implementing the
 * \Drupal\Core\TypedData\ListInterface. A list contains items of the same data
 * type, is ordered and may contain duplicates. The class used for a list of
 * items of a certain type may be specified using the 'list class' key.
 *
 * @see \Drupal::typedDataManager()
 * @see \Drupal\Core\TypedData\TypedDataManager::create()
 * @see hook_data_type_info_alter()
 *
 * @ingroup typed_data
 *
 * @Annotation
 */
class DataType extends Plugin {

  /**
   * The data type plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the data type.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The description of the data type.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $description;

  /**
   * The definition class to use for defining data of this type.
   * Must implement the \Drupal\Core\TypedData\DataDefinitionInterface.
   *
   * @var string
   */
  public $definition_class = '\Drupal\Core\TypedData\DataDefinition';

  /**
   * The typed data class used for wrapping multiple data items of the type.
   * Must implement the \Drupal\Core\TypedData\ListInterface.
   *
   * @var string
   */
  public $list_class = '\Drupal\Core\TypedData\Plugin\DataType\ItemList';

  /**
   * The definition class to use for defining a list of items of this type.
   * Must implement the \Drupal\Core\TypedData\ListDataDefinitionInterface.
   *
   * @var string
   */
  public $list_definition_class = '\Drupal\Core\TypedData\ListDataDefinition';

  /**
   * The pre-defined primitive type that this data type maps to.
   *
   * If set, it must be a constant defined by \Drupal\Core\TypedData\Primitive
   * such as \Drupal\Core\TypedData\Primitive::STRING.
   *
   * @var string
   */
  public $primitive_type;

  /**
   * An array of validation constraints for this type.
   *
   * @var array
   *
   * @see \Drupal\Core\TypedData\TypedDataManager::getConstraints().
   */
  public $constraints;

  /**
   * Whether the typed object wraps the canonical representation of the data.
   *
   * @var bool
   *
   * @see \Drupal\Core\TypedData\TypedDataManager::getCanonicalRepresentation()
   */
  public $unwrap_for_canonical_representation = TRUE;

}

<?php

declare(strict_types=1);

namespace Drupal\Core\TypedData\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\Core\TypedData\Plugin\DataType\ItemList;

/**
 * Defines a data type attribute.
 *
 * The typed data API allows modules to support any kind of data based upon
 * pre-defined primitive types and interfaces for complex data and lists.
 *
 * Defined data types may map to one of the pre-defined primitive types below
 * \Drupal\Core\TypedData\Type or may be complex data types, containing
 * one or more data properties. Typed data objects for complex data types have
 * to implement the \Drupal\Core\TypedData\ComplexDataInterface. Further
 * interfaces that may be implemented are:
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
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class DataType extends Plugin {

  /**
   * Constructs a new DataType attribute.
   *
   * @param string $id
   *   The data type plugin ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $label
   *   The human-readable name of the data type.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $description
   *   (optional) The description of the data type.
   * @param string|null $definition_class
   *   (optional) The definition class to use for defining data of this type.
   * @param string|null $list_class
   *   (optional) The typed data class used for wrapping multiple data items of
   *   the type.
   * @param string|null $list_definition_class
   *   (optional) The definition class to use for defining a list of items of
   *   this type.
   * @param array $constraints
   *   (optional) An array of validation constraints for this type.
   * @param bool $unwrap_for_canonical_representation
   *   Whether the typed object wraps the canonical representation of the data.
   * @param class-string|null $deriver
   *   (optional) The deriver class for the data type.
   *
   * @see \Drupal\Core\TypedData\TypedDataManager::getConstraints()
   * @see \Drupal\Core\TypedData\TypedDataManager::getCanonicalRepresentation()
   */
  public function __construct(
    public readonly string $id,
    public readonly TranslatableMarkup $label,
    public readonly ?TranslatableMarkup $description = NULL,
    public readonly ?string $definition_class = DataDefinition::class,
    public readonly ?string $list_class = ItemList::class,
    public readonly ?string $list_definition_class = ListDataDefinition::class,
    public readonly array $constraints = [],
    public readonly bool $unwrap_for_canonical_representation = TRUE,
    public readonly ?string $deriver = NULL,
  ) {}

}

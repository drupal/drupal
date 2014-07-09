<?php

/**
 * @file
 * Contains \Drupal\migrate\Row.
 */

namespace Drupal\migrate;

use Drupal\Component\Utility\NestedArray;
use Drupal\migrate\Plugin\MigrateIdMapInterface;

/**
 * Stores a row.
 */
class Row {

  /**
   * The actual values of the source row.
   *
   * @var array
   */
  protected $source = array();

  /**
   * The source identifiers.
   *
   * @var array
   */
  protected $sourceIds = array();

  /**
   * The destination values.
   *
   * @var array
   */
  protected $destination = array();

  /**
   * Level separator of destination and source properties.
   */
  const PROPERTY_SEPARATOR = '/';

  /**
   * The mapping between source and destination identifiers.
   *
   * @var array
   */
  protected $idMap = array(
    'original_hash' => '',
    'hash' => '',
    'source_row_status' => MigrateIdMapInterface::STATUS_NEEDS_UPDATE,
  );

  /**
   * Whether the source has been frozen already.
   *
   * Once frozen the source can not be changed any more.
   *
   * @var bool
   */
  protected $frozen = FALSE;

  /**
   * The raw destination properties.
   *
   * Unlike $destination which is set by using
   * \Drupal\Component\Utility\NestedArray::setValue() this array contains
   * the destination as setDestinationProperty was called.
   *
   * @var array
   *   The raw destination.
   *
   * @see getRawDestination()
   */
  protected $rawDestination;

  /**
   * TRUE when this row is a stub.
   *
   * @var bool
   */
  protected $stub = FALSE;

  /**
   * Constructs a \Drupal\Migrate\Row object.
   *
   * @param array $values
   *   An array of values to add as properties on the object.
   * @param array $source_ids
   *   An array containing the IDs of the source using the keys as the field
   *   names.
   *
   * @throws \InvalidArgumentException
   *   Thrown when a source ID property does not exist.
   */
  public function __construct(array $values, array $source_ids) {
    $this->source = $values;
    $this->sourceIds = $source_ids;
    foreach (array_keys($source_ids) as $id) {
      if (!$this->hasSourceProperty($id)) {
        throw new \InvalidArgumentException("$id has no value");
      }
    }
  }

  /**
   * Retrieves the values of the source identifiers.
   *
   * @return array
   *   An array containing the values of the source identifiers.
   */
  public function getSourceIdValues() {
    return array_intersect_key($this->source, $this->sourceIds);
  }

  /**
   * Determines whether a source has a property.
   *
   * @param string $property
   *   A property on the source.
   *
   * @return bool
   *   TRUE if the source has property; FALSE otherwise.
   */
  public function hasSourceProperty($property) {
    return NestedArray::keyExists($this->source, explode(static::PROPERTY_SEPARATOR, $property));
  }

  /**
   * Retrieves a source property.
   *
   * @param string $property
   *   A property on the source.
   *
   * @return mixed|null
   *   The found returned property or NULL if not found.
   */
  public function getSourceProperty($property) {
    $return = NestedArray::getValue($this->source, explode(static::PROPERTY_SEPARATOR, $property), $key_exists);
    if ($key_exists) {
      return $return;
    }
  }

  /**
   * Returns the whole source array.
   *
   * @return array
   *   An array of source plugins.
   */
  public function getSource() {
    return $this->source;
  }

  /**
   * Sets a source property.
   *
   * This can only be called from the source plugin.
   *
   * @param string $property
   *   A property on the source.
   * @param mixed $data
   *   The property value to set on the source.
   *
   * @throws \Exception
   */
  public function setSourceProperty($property, $data) {
    if ($this->frozen) {
      throw new \Exception("The source is frozen and can't be changed any more");
    }
    else {
      NestedArray::setValue($this->source, explode(static::PROPERTY_SEPARATOR, $property), $data, TRUE);
    }
  }

  /**
   * Freezes the source.
   */
  public function freezeSource() {
    $this->frozen = TRUE;
  }

  /**
   * Tests if destination property exists.
   *
   * @param array|string $property
   *   An array of properties on the destination.
   *
   * @return bool
   *   TRUE if the destination property exists.
   */
  public function hasDestinationProperty($property) {
    return NestedArray::keyExists($this->destination, explode(static::PROPERTY_SEPARATOR, $property));
  }

  /**
   * Sets destination properties.
   *
   * @param string $property
   *   The name of the destination property.
   * @param mixed $value
   *   The property value to set on the destination.
   */
  public function setDestinationProperty($property, $value) {
    $this->rawDestination[$property] = $value;
    NestedArray::setValue($this->destination, explode(static::PROPERTY_SEPARATOR, $property), $value, TRUE);
  }

  /**
   * Returns the whole destination array.
   *
   * @return array
   *   An array of destination values.
   */
  public function getDestination() {
    return $this->destination;
  }

  /**
   * Returns the raw destination. Rarely necessary.
   *
   * For example calling setDestination('foo/bar', 'baz') results in
   * @code
   * $this->destination['foo']['bar'] = 'baz';
   * $this->rawDestination['foo/bar'] = 'baz';
   * @encode
   *
   * @return array
   *   The raw destination values.
   */
  public function getRawDestination() {
    return $this->rawDestination;
  }

  /**
   * Returns the value of a destination property.
   *
   * @param string $property
   *   The name of a property on the destination.
   *
   * @return mixed
   *   The destination value.
   */
  public function getDestinationProperty($property) {
    return NestedArray::getValue($this->destination, explode(static::PROPERTY_SEPARATOR, $property));
  }

  /**
   * Sets the Migrate ID mappings.
   *
   * @param array $id_map
   *   An array of mappings between source ID and destination ID.
   */
  public function setIdMap(array $id_map) {
    $this->idMap = $id_map;
  }

  /**
   * Retrieves the Migrate ID mappings.
   *
   * @return array
   *   An array of mapping between source and destination identifiers.
   */
  public function getIdMap() {
    return $this->idMap;
  }

  /**
   * Recalculates the hash for the row.
   */
  public function rehash() {
    $this->idMap['original_hash'] = $this->idMap['hash'];
    $this->idMap['hash'] = hash('sha256', serialize($this->source));
  }

  /**
   * Checks whether the row has changed compared to the original ID map.
   *
   * @return bool
   *   TRUE if the row has changed, FALSE otherwise. If setIdMap() was not
   *   called, this always returns FALSE.
   */
  public function changed() {
    return $this->idMap['original_hash'] != $this->idMap['hash'];
  }

  /**
   * Returns if this row needs an update.
   *
   * @return bool
   *   TRUE if the row needs updating, FALSE otherwise.
   */
  public function needsUpdate() {
    return $this->idMap['source_row_status'] == MigrateIdMapInterface::STATUS_NEEDS_UPDATE;
  }

  /**
   * Returns the hash for the source values..
   *
   * @return mixed
   *   The hash of the source values.
   */
  public function getHash() {
    return $this->idMap['hash'];
  }

  /**
   * Flags and reports this row as a stub.
   *
   * @param bool|null $new_value
   *   TRUE when the row is a stub. Omit to determine whether the row is a
   *   stub.
   *
   * @return mixed
   *   The current stub value.
   */
  public function stub($new_value = NULL) {
    if (isset($new_value)) {
      $this->stub = $new_value;
    }
    return $this->stub;
  }
}

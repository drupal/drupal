<?php

namespace Drupal\jsonapi\ResourceType;

/**
 * Abstract value object containing all metadata for a JSON:API resource field.
 *
 * @internal JSON:API maintains no PHP API since its API is the HTTP API. This
 *   class may change at any time and this will break any dependencies on it.
 *
 * @see https://www.drupal.org/project/drupal/issues/3032787
 * @see jsonapi.api.php
 *
 * @see \Drupal\jsonapi\ResourceType\ResourceTypeRepository
 */
abstract class ResourceTypeField {

  /**
   * The internal field name.
   *
   * @var string
   */
  protected $internalName;

  /**
   * The public field name.
   *
   * @var string
   */
  protected $publicName;

  /**
   * Whether the field is disabled.
   *
   * @var bool
   */
  protected $enabled;

  /**
   * Whether the field can only have one value.
   *
   * @var bool
   */
  protected $hasOne;

  /**
   * ResourceTypeField constructor.
   *
   * @param string $internal_name
   *   The internal field name.
   * @param string $public_name
   *   (optional) The public field name. Defaults to the internal field name.
   * @param bool $enabled
   *   (optional) Whether the field is enabled. Defaults to TRUE.
   * @param bool $has_one
   *   (optional) Whether the field can only have ony value. Defaults to TRUE.
   */
  public function __construct($internal_name, $public_name = NULL, $enabled = TRUE, $has_one = TRUE) {
    $this->internalName = $internal_name;
    $this->publicName = $public_name ?: $internal_name;
    $this->enabled = $enabled;
    $this->hasOne = $has_one;
  }

  /**
   * Gets the internal name of the field.
   *
   * @return string
   *   The internal name of the field.
   */
  public function getInternalName() {
    return $this->internalName;
  }

  /**
   * Gets the public name of the field.
   *
   * @return string
   *   The public name of the field.
   */
  public function getPublicName() {
    return $this->publicName;
  }

  /**
   * Establishes a new public name for the field.
   *
   * @param string $public_name
   *   The public name.
   *
   * @return static
   *   A new instance of the field with the given public name.
   */
  public function withPublicName($public_name) {
    return new static($this->internalName, $public_name, $this->enabled, $this->hasOne);
  }

  /**
   * Gets a new instance of the field that is disabled.
   *
   * @return static
   *   A new instance of the field that is disabled.
   */
  public function disabled() {
    return new static($this->internalName, $this->publicName, FALSE, $this->hasOne);
  }

  /**
   * Gets a new instance of the field that is enabled.
   *
   * @return static
   *   A new instance of the field that is enabled.
   */
  public function enabled(): static {
    return new static($this->internalName, $this->publicName, TRUE, $this->hasOne);
  }

  /**
   * Whether the field is enabled.
   *
   * @return bool
   *   Whether the field is enabled. FALSE if the field should not be in the
   *   JSON:API response.
   */
  public function isFieldEnabled() {
    return $this->enabled;
  }

  /**
   * Whether the field can only have one value.
   *
   * @return bool
   *   TRUE if the field can have only one value, FALSE otherwise.
   */
  public function hasOne() {
    return $this->hasOne;
  }

  /**
   * Whether the field can have many values.
   *
   * @return bool
   *   TRUE if the field can have more than one value, FALSE otherwise.
   */
  public function hasMany() {
    return !$this->hasOne;
  }

}

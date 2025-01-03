<?php

namespace Drupal\Component\Plugin\Attribute;

/**
 * Defines a common interface for classed attributes.
 */
interface AttributeInterface {

  /**
   * Gets the value of an attribute.
   */
  public function get(): mixed;

  /**
   * Gets the name of the provider of the attribute class.
   *
   * @return string|null
   *   The provider of the attribute class.
   */
  public function getProvider(): ?string;

  /**
   * Sets the name of the provider of the attribute class.
   *
   * @param string $provider
   *   The provider of the annotated class.
   */
  public function setProvider(string $provider): void;

  /**
   * Gets the unique ID for this attribute class.
   *
   * @return string
   *   The attribute class ID.
   */
  public function getId(): string;

  /**
   * Gets the class of the attribute class.
   *
   * @return class-string|null
   *   The attribute class.
   */
  public function getClass(): ?string;

  /**
   * Sets the class of the attributed class.
   *
   * @param class-string $class
   *   The class of the attributed class.
   */
  public function setClass(string $class): void;

}

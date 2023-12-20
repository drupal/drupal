<?php

namespace Drupal\Core\Field;

/**
 * Provides an object that returns the category info about the field type.
 */
interface FieldTypeCategoryInterface {

  /**
   * Returns the field group label.
   *
   * @return string|\Stringable
   *   The category label.
   */
  public function getLabel(): string|\Stringable;

  /**
   * Returns the field group description.
   *
   * @return string|\Stringable
   *   The category description.
   */
  public function getDescription(): string|\Stringable;

  /**
   * Returns the field group weight.
   *
   * @return int
   *   The weight.
   */
  public function getWeight(): int;

  /**
   * Returns asset libraries for the field group.
   *
   * @return array
   *   The asset libraries to attach.
   */
  public function getLibraries(): array;

}

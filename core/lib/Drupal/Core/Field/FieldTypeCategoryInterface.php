<?php

namespace Drupal\Core\Field;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides an object that returns the category info about the field type.
 */
interface FieldTypeCategoryInterface {

  /**
   * Returns the field group label.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The category label.
   */
  public function getLabel(): TranslatableMarkup;

  /**
   * Returns the field group description.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The category description.
   */
  public function getDescription(): TranslatableMarkup;

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

<?php

namespace Drupal\layout_builder\Plugin\DataType;

use Drupal\Core\TypedData\TypedData;
use Drupal\layout_builder\Section;

/**
 * Provides a data type wrapping \Drupal\layout_builder\Section.
 *
 * @DataType(
 *   id = "layout_section",
 *   label = @Translation("Layout Section"),
 *   description = @Translation("A layout section"),
 * )
 *
 * @internal
 *   Plugin classes are internal.
 */
class SectionData extends TypedData {

  /**
   * The section object.
   *
   * @var \Drupal\layout_builder\Section
   */
  protected $value;

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = TRUE) {
    if ($value && !$value instanceof Section) {
      throw new \InvalidArgumentException(sprintf('Value assigned to "%s" is not a valid section', $this->getName()));
    }
    parent::setValue($value, $notify);
  }

}

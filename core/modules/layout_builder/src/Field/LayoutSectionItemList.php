<?php

namespace Drupal\layout_builder\Field;

use Drupal\Core\Field\FieldItemList;
use Drupal\layout_builder\SectionListInterface;
use Drupal\layout_builder\SectionStorage\SectionStorageTrait;

/**
 * Defines a item list class for layout section fields.
 *
 * @internal
 *
 * @see \Drupal\layout_builder\Plugin\Field\FieldType\LayoutSectionItem
 */
class LayoutSectionItemList extends FieldItemList implements SectionListInterface {

  use SectionStorageTrait;

  /**
   * {@inheritdoc}
   */
  public function getSections() {
    $sections = [];
    /** @var \Drupal\layout_builder\Plugin\Field\FieldType\LayoutSectionItem $item */
    foreach ($this->list as $delta => $item) {
      $sections[$delta] = $item->section;
    }
    return $sections;
  }

  /**
   * {@inheritdoc}
   */
  protected function setSections(array $sections) {
    $this->list = [];
    $sections = array_values($sections);
    /** @var \Drupal\layout_builder\Plugin\Field\FieldType\LayoutSectionItem $item */
    foreach ($sections as $section) {
      $item = $this->appendItem();
      $item->section = $section;
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity() {
    $entity = parent::getEntity();

    // Ensure the entity is updated with the latest value.
    $entity->set($this->getName(), $this->getValue());
    return $entity;
  }

}

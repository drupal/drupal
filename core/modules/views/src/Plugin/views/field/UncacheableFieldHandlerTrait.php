<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\field\UncacheableFieldHandlerTrait.
 */

namespace Drupal\views\Plugin\views\field;

use Drupal\views\ResultRow;

/**
 * Trait encapsulating the logic for uncacheable field handlers.
 */
trait UncacheableFieldHandlerTrait {

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\views\Plugin\views\Field\FieldHandlerInterface::render()
   */
  public function render(ResultRow $row) {
    return $this->getFieldTokenPlaceholder();
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\views\Plugin\views\Field\FieldHandlerInterface::postRender()
   */
  public function postRender(ResultRow $row, $output) {
    $placeholder = $this->getFieldTokenPlaceholder();
    $value = $this->doRender($row);
    $this->last_render = str_replace($placeholder, $value, $output);
    return [$placeholder => $value];
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\views\Plugin\views\Field\FieldPluginBase::getFieldTokenPlaceholder()
   */
  abstract protected function getFieldTokenPlaceholder();

  /**
   * Actually renders the field markup.
   *
   * @param \Drupal\views\ResultRow $row
   *   A result row.
   *
   * @return string
   *   The field markup.
   */
  protected function doRender(ResultRow $row) {
    return $this->getValue($row);
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\views\Plugin\views\Field\FieldHandlerInterface::getValue()
   */
  abstract protected function getValue(ResultRow $row, $field = NULL);

}

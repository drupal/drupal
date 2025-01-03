<?php

namespace Drupal\layout_builder;

/**
 * A trait for generating IDs used to highlight active UI elements.
 */
trait LayoutBuilderHighlightTrait {

  /**
   * Provides the ID used to highlight the active Layout Builder UI element.
   *
   * @param int $delta
   *   The section the block is in.
   * @param string $region
   *   The section region in which the block is placed.
   *
   * @return string
   *   The highlight ID of the block.
   */
  protected function blockAddHighlightId($delta, $region) {
    return "block-$delta-$region";
  }

  /**
   * Provides the ID used to highlight the active Layout Builder UI element.
   *
   * @param string $uuid
   *   The uuid of the block.
   *
   * @return string
   *   The highlight ID of the block.
   */
  protected function blockUpdateHighlightId($uuid) {
    return $uuid;
  }

  /**
   * Provides the ID used to highlight the active Layout Builder UI element.
   *
   * @param int $delta
   *   The location of the section.
   *
   * @return string
   *   The highlight ID of the section.
   */
  protected function sectionAddHighlightId($delta) {
    return "section-$delta";
  }

  /**
   * Provides the ID used to highlight the active Layout Builder UI element.
   *
   * @param int $delta
   *   The location of the section.
   *
   * @return string
   *   The highlight ID of the section.
   */
  protected function sectionUpdateHighlightId($delta) {
    return "section-update-$delta";
  }

}

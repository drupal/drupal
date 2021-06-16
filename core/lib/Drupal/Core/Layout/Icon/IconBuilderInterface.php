<?php

namespace Drupal\Core\Layout\Icon;

/**
 * Provides an interface for building layout icons.
 */
interface IconBuilderInterface {

  /**
   * Builds a render array representation of an SVG based on an icon map.
   *
   * @param string[][] $icon_map
   *   A two-dimensional array representing the visual output of the layout.
   *   For the following shape:
   *   |------------------------------|
   *   |                              |
   *   |             100%             |
   *   |                              |
   *   |-------|--------------|-------|
   *   |       |              |       |
   *   |       |      50%     |  25%  |
   *   |       |              |       |
   *   |  25%  |--------------|-------|
   *   |       |                      |
   *   |       |         75%          |
   *   |       |                      |
   *   |------------------------------|
   *   The corresponding array would be:
   *   - ['top']
   *   - ['first', 'second', 'second', 'third']
   *   - ['first', 'bottom', 'bottom', 'bottom'].
   *
   * @return array
   *   A render array representing a SVG icon.
   */
  public function build(array $icon_map);

  /**
   * Sets the ID.
   *
   * @param string $id
   *   The machine name of the layout.
   *
   * @return $this
   */
  public function setId($id);

  /**
   * Sets the label.
   *
   * @param string $label
   *   The label of the layout.
   *
   * @return $this
   */
  public function setLabel($label);

  /**
   * Sets the width.
   *
   * @param int $width
   *   The width of the SVG.
   *
   * @return $this
   */
  public function setWidth($width);

  /**
   * Sets the height.
   *
   * @param int $height
   *   The height of the SVG.
   *
   * @return $this
   */
  public function setHeight($height);

  /**
   * Sets the padding.
   *
   * @param int $padding
   *   The padding between regions.
   *
   * @return $this
   */
  public function setPadding($padding);

  /**
   * Sets the stroke width.
   *
   * @param int|null $stroke_width
   *   The width of region borders.
   *
   * @return $this
   */
  public function setStrokeWidth($stroke_width);

}

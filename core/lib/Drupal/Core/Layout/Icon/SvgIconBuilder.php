<?php

namespace Drupal\Core\Layout\Icon;

use Drupal\Component\Utility\Html;

/**
 * Builds SVG layout icons.
 */
class SvgIconBuilder implements IconBuilderInterface {

  /**
   * The machine name of the layout.
   *
   * @var string
   */
  protected $id;

  /**
   * The label of the layout.
   *
   * @var string
   */
  protected $label;

  /**
   * The width of the SVG.
   *
   * @var int
   */
  protected $width = 125;

  /**
   * The height of the SVG.
   *
   * @var int
   */
  protected $height = 150;

  /**
   * The padding between regions.
   *
   * @var int
   */
  protected $padding = 4;

  /**
   * The width of region borders.
   *
   * @var int|null
   */
  protected $strokeWidth = 1;

  /**
   * {@inheritdoc}
   */
  public function build(array $icon_map) {
    $regions = $this->calculateSvgValues($icon_map, $this->width, $this->height, $this->strokeWidth, $this->padding);
    return $this->buildRenderArray($regions, $this->width, $this->height, $this->strokeWidth);
  }

  /**
   * Builds a render array representation of an SVG.
   *
   * @param mixed[] $regions
   *   An array keyed by region name, with each element containing the 'height',
   *   'width', and 'x' and 'y' offsets of each region.
   * @param int $width
   *   The width of the SVG.
   * @param int $height
   *   The height of the SVG.
   * @param int|null $stroke_width
   *   The width of region borders.
   *
   * @return array
   *   A render array representing a SVG icon.
   */
  protected function buildRenderArray(array $regions, $width, $height, $stroke_width) {
    $build = [
      '#type' => 'html_tag',
      '#tag' => 'svg',
      '#attributes' => [
        'width' => $width,
        'height' => $height,
        'class' => [
          'layout-icon',
        ],
      ],
    ];

    if ($this->id) {
      $build['#attributes']['class'][] = Html::getClass('layout-icon--' . $this->id);
    }

    if ($this->label) {
      $build['title'] = [
        '#type' => 'html_tag',
        '#tag' => 'title',
        '#value' => $this->label,
      ];
    }

    // Append each polygon to the SVG.
    foreach ($regions as $region => $attributes) {
      // Wrapping with a <g> element allows for metadata to exist alongside the
      // rectangle.
      $build['region'][$region] = [
        '#type' => 'html_tag',
        '#tag' => 'g',
      ];

      $build['region'][$region]['title'] = [
        '#type' => 'html_tag',
        '#tag' => 'title',
        '#value' => $region,
      ];

      // Assemble the rectangle SVG element.
      $build['region'][$region]['rect'] = [
        '#type' => 'html_tag',
        '#tag' => 'rect',
        '#attributes' => [
          'x' => $attributes['x'],
          'y' => $attributes['y'],
          'width' => $attributes['width'],
          'height' => $attributes['height'],
          'stroke-width' => $stroke_width,
          'class' => [
            'layout-icon__region',
            Html::getClass('layout-icon__region--' . $region),
          ],
        ],
      ];
    }

    return $build;
  }

  /**
   * Calculates the dimensions and offsets of all regions.
   *
   * @param string[][] $rows
   *   A two-dimensional array representing the visual output of the layout. See
   *   the documentation for the $icon_map parameter of
   *   \Drupal\Core\Layout\Icon\IconBuilderInterface::build().
   * @param int $width
   *   The width of the SVG.
   * @param int $height
   *   The height of the SVG.
   * @param int $stroke_width
   *   The width of region borders.
   * @param int $padding
   *   The padding between regions.
   *
   * @return mixed[][]
   *   An array keyed by region name, with each element containing the 'height',
   *   'width', and 'x' and 'y' offsets of each region.
   */
  protected function calculateSvgValues(array $rows, $width, $height, $stroke_width, $padding) {
    $region_rects = [];

    $row_height = $this->getLength(count($rows), $height, $stroke_width, $padding);
    foreach ($rows as $row => $cols) {
      $column_width = $this->getLength(count($cols), $width, $stroke_width, $padding);
      $vertical_offset = $this->getOffset($row, $row_height, $stroke_width, $padding);
      foreach ($cols as $col => $region) {
        $horizontal_offset = $this->getOffset($col, $column_width, $stroke_width, $padding);

        // Check if this region is new, or already exists in the rectangle.
        if (!isset($region_rects[$region])) {
          $region_rects[$region] = [
            'x' => $horizontal_offset,
            'y' => $vertical_offset,
            'width' => $column_width,
            'height' => $row_height,
          ];
        }
        else {
          // In order to include the area of the previous region and any padding
          // or border, subtract the calculated offset from the original offset.
          $region_rects[$region]['width'] = $column_width + ($horizontal_offset - $region_rects[$region]['x']);
          $region_rects[$region]['height'] = $row_height + ($vertical_offset - $region_rects[$region]['y']);
        }
      }
    }

    return $region_rects;
  }

  /**
   * Gets the offset for this region.
   *
   * @param int $delta
   *   The zero-based delta of the region.
   * @param int $length
   *   The height or width of the region.
   * @param int $stroke_width
   *   The width of the region borders.
   * @param int $padding
   *   The padding between regions.
   *
   * @return int
   *   The offset for this region.
   */
  protected function getOffset($delta, $length, $stroke_width, $padding) {
    // Half of the stroke width is drawn outside the dimensions.
    $stroke_width /= 2;
    // For every region in front of this add two strokes, as well as one
    // directly in front.
    $num_of_strokes = 2 * $delta + 1;
    return ($num_of_strokes * $stroke_width) + ($delta * ($length + $padding));
  }

  /**
   * Gets the height or width of a region.
   *
   * @param int $number_of_regions
   *   The total number of regions.
   * @param int $length
   *   The total height or width of the icon.
   * @param int $stroke_width
   *   The width of the region borders.
   * @param int $padding
   *   The padding between regions.
   *
   * @return float|int
   *   The height or width of a region.
   */
  protected function getLength($number_of_regions, $length, $stroke_width, $padding) {
    if ($number_of_regions === 0) {
      return 0;
    }

    // Half of the stroke width is drawn outside the dimensions.
    $total_stroke = $number_of_regions * $stroke_width;
    // Padding does not precede the first region.
    $total_padding = ($number_of_regions - 1) * $padding;
    // Divide the remaining length by the number of regions.
    return ($length - $total_padding - $total_stroke) / $number_of_regions;
  }

  /**
   * {@inheritdoc}
   */
  public function setId($id) {
    $this->id = $id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setLabel($label) {
    $this->label = $label;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setWidth($width) {
    $this->width = $width;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setHeight($height) {
    $this->height = $height;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setPadding($padding) {
    $this->padding = $padding;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setStrokeWidth($stroke_width) {
    $this->strokeWidth = $stroke_width;
    return $this;
  }

}

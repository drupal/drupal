<?php

namespace Drupal\Component\Utility;

/**
 * Rectangle rotation algebra class.
 *
 * This class is used by the image system to abstract, from toolkit
 * implementations, the calculation of the expected dimensions resulting from
 * an image rotate operation.
 *
 * Different versions of PHP for the GD toolkit, and alternative toolkits, use
 * different algorithms to perform the rotation of an image and result in
 * different dimensions of the output image. This prevents predictability of
 * the final image size for instance by the image rotate effect, or by image
 * toolkit rotate operations.
 *
 * This class implements a calculation algorithm that returns, given input
 * width, height and rotation angle, dimensions of the expected image after
 * rotation that are consistent with those produced by the GD rotate image
 * toolkit operation using PHP 5.5 and above.
 *
 * @see \Drupal\system\Plugin\ImageToolkit\Operation\gd\Rotate
 */
class Rectangle {

  /**
   * The width of the rectangle.
   *
   * @var int
   */
  protected $width;

  /**
   * The height of the rectangle.
   *
   * @var int
   */
  protected $height;

  /**
   * The width of the rotated rectangle.
   *
   * @var int
   */
  protected $boundingWidth;

  /**
   * The height of the rotated rectangle.
   *
   * @var int
   */
  protected $boundingHeight;

  /**
   * Constructs a new Rectangle object.
   *
   * @param int $width
   *   The width of the rectangle.
   * @param int $height
   *   The height of the rectangle.
   */
  public function __construct($width, $height) {
    if ($width > 0 && $height > 0) {
      $this->width = $width;
      $this->height = $height;
      $this->boundingWidth = $width;
      $this->boundingHeight = $height;
    }
    else {
      throw new \InvalidArgumentException("Invalid dimensions ({$width}x{$height}) specified for a Rectangle object");
    }
  }

  /**
   * Rotates the rectangle.
   *
   * @param float $angle
   *   Rotation angle.
   *
   * @return $this
   */
  public function rotate($angle) {
    // PHP 5.5 GD bug: https://bugs.php.net/bug.php?id=65148: To prevent buggy
    // behavior on negative multiples of 30 degrees we convert any negative
    // angle to a positive one between 0 and 360 degrees.
    $angle -= floor($angle / 360) * 360;

    // For some rotations that are multiple of 30 degrees, we need to correct
    // an imprecision between GD that uses C floats internally, and PHP that
    // uses C doubles. Also, for rotations that are not multiple of 90 degrees,
    // we need to introduce a correction factor of 0.5 to match the GD
    // algorithm used in PHP 5.5 (and above) to calculate the width and height
    // of the rotated image.
    if ((int) $angle == $angle && $angle % 90 == 0) {
      $imprecision = 0;
      $correction = 0;
    }
    else {
      $imprecision = -0.00001;
      $correction = 0.5;
    }

    // Do the trigonometry, applying imprecision fixes where needed.
    $rad = deg2rad($angle);
    $cos = cos($rad);
    $sin = sin($rad);
    $a = $this->width * $cos;
    $b = $this->height * $sin + $correction;
    $c = $this->width * $sin;
    $d = $this->height * $cos + $correction;
    if ((int) $angle == $angle && in_array($angle, [60, 150, 300])) {
      $a = $this->fixImprecision($a, $imprecision);
      $b = $this->fixImprecision($b, $imprecision);
      $c = $this->fixImprecision($c, $imprecision);
      $d = $this->fixImprecision($d, $imprecision);
    }

    // This is how GD on PHP5.5 calculates the new dimensions.
    $this->boundingWidth = abs((int) $a) + abs((int) $b);
    $this->boundingHeight = abs((int) $c) + abs((int) $d);

    return $this;
  }

  /**
   * Performs an imprecision check on the input value and fixes it if needed.
   *
   * GD that uses C floats internally, whereas we at PHP level use C doubles.
   * In some cases, we need to compensate imprecision.
   *
   * @param float $input
   *   The input value.
   * @param float $imprecision
   *   The imprecision factor.
   *
   * @return float
   *   A value, where imprecision is added to input if the delta part of the
   *   input is lower than the absolute imprecision.
   */
  protected function fixImprecision($input, $imprecision) {
    if ($this->delta($input) < abs($imprecision)) {
      return $input + $imprecision;
    }
    return $input;
  }

  /**
   * Returns the fractional part of a float number, unsigned.
   *
   * @param float $input
   *   The input value.
   *
   * @return float
   *   The fractional part of the input number, unsigned.
   */
  protected function fraction($input) {
    return abs((int) $input - $input);
  }

  /**
   * Returns the difference of a fraction from the closest between 0 and 1.
   *
   * @param float $input
   *   The input value.
   *
   * @return float
   *   the difference of a fraction from the closest between 0 and 1.
   */
  protected function delta($input) {
    $fraction = $this->fraction($input);
    return $fraction > 0.5 ? (1 - $fraction) : $fraction;
  }

  /**
   * Gets the bounding width of the rectangle.
   *
   * @return int
   *   The bounding width of the rotated rectangle.
   */
  public function getBoundingWidth() {
    return $this->boundingWidth;
  }

  /**
   * Gets the bounding height of the rectangle.
   *
   * @return int
   *   The bounding height of the rotated rectangle.
   */
  public function getBoundingHeight() {
    return $this->boundingHeight;
  }

}

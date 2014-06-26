<?php

/**
 * @file
 * Contains \Drupal\image_test\Plugin\ImageToolkit\TestToolkit.
 */

namespace Drupal\image_test\Plugin\ImageToolkit;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\ImageToolkit\ImageToolkitBase;

/**
 * Defines a Test toolkit for image manipulation within Drupal.
 *
 * @ImageToolkit(
 *   id = "test",
 *   title = @Translation("A dummy toolkit that works")
 * )
 */
class TestToolkit extends ImageToolkitBase {

  /**
   * Image type represented by a PHP IMAGETYPE_* constant (e.g. IMAGETYPE_JPEG).
   *
   * @var int
   */
  protected $type;

  /**
   * The width of the image.
   *
   * @var int
   */
  protected $width;

  /**
   * The height of the image.
   *
   * @var int
   */
  protected $height;

  /**
   * {@inheritdoc}
   */
  public function settingsForm() {
    $this->logCall('settings', array());
    $form['test_parameter'] = array(
      '#type' => 'number',
      '#title' => $this->t('Test toolkit parameter'),
      '#description' => $this->t('A toolkit parameter for testing purposes.'),
      '#min' => 0,
      '#max' => 100,
      '#default_value' => \Drupal::config('system.image.test_toolkit')->get('test_parameter'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsFormSubmit($form, &$form_state) {
    \Drupal::config('system.image.test_toolkit')
      ->set('test_parameter', $form_state['values']['test']['test_parameter'])
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public function parseFile() {
    $this->logCall('parseFile', array());
    $data = @getimagesize($this->getImage()->getSource());
    if ($data && in_array($data[2], static::supportedTypes())) {
      $this->setType($data[2]);
      $this->width = $data[0];
      $this->height = $data[1];
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function save($destination) {
    $this->logCall('save', array($destination));
    // Return false so that image_save() doesn't try to chmod the destination
    // file that we didn't bother to create.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function crop($x, $y, $width, $height) {
    $this->logCall('crop', array($x, $y, $width, $height));
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function resize($width, $height) {
    $this->logCall('resize', array($width, $height));
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function rotate($degrees, $background = NULL) {
    $this->logCall('rotate', array($degrees, $background));
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function desaturate() {
    $this->logCall('desaturate', array());
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function scale($width = NULL, $height = NULL, $upscale = FALSE) {
    $this->logCall('scale', array($width, $height, $upscale));
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function scaleAndCrop($width, $height) {
    $this->logCall('scaleAndCrop', array($width, $height));
    return TRUE;
  }

  /**
   * Stores the values passed to a toolkit call.
   *
   * @param string $op
   *   One of the image toolkit operations: 'parseFile', 'save', 'settings',
   *   'resize', 'rotate', 'crop', 'desaturate'.
   * @param array $args
   *   Values passed to hook.
   *
   * @see \Drupal\system\Tests\Image\ToolkitTestBase::imageTestReset()
   * @see \Drupal\system\Tests\Image\ToolkitTestBase::imageTestGetAllCalls()
   */
  protected function logCall($op, $args) {
    $results = \Drupal::state()->get('image_test.results') ?: array();
    $results[$op][] = $args;
    \Drupal::state()->set('image_test.results', $results);
  }

  /**
   * {@inheritdoc}
   */
  public function getWidth() {
    return $this->width;
  }

  /**
   * {@inheritdoc}
   */
  public function getHeight() {
    return $this->height;
  }

  /**
   * Returns the type of the image.
   *
   * @return int
   *   The image type represented by a PHP IMAGETYPE_* constant (e.g.
   *   IMAGETYPE_JPEG).
   */
  public function getType() {
    return $this->type;
  }

  /**
   * Sets the PHP type of the image.
   *
   * @param int $type
   *   The image type represented by a PHP IMAGETYPE_* constant (e.g.
   *   IMAGETYPE_JPEG).
   *
   * @return this
   */
  public function setType($type) {
    if (in_array($type, static::supportedTypes())) {
      $this->type = $type;
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMimeType() {
    return $this->getType() ? image_type_to_mime_type($this->getType()) : '';
  }

  /**
   * {@inheritdoc}
   */
  public static function isAvailable() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSupportedExtensions() {
    $extensions = array();
    foreach (static::supportedTypes() as $image_type) {
      $extensions[] = Unicode::strtolower(image_type_to_extension($image_type, FALSE));
    }
    return $extensions;
  }

  /**
   * Returns a list of image types supported by the toolkit.
   *
   * @return array
   *   An array of available image types. An image type is represented by a PHP
   *   IMAGETYPE_* constant (e.g. IMAGETYPE_JPEG, IMAGETYPE_PNG, etc.).
   */
  protected static function supportedTypes() {
    return array(IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_GIF);
  }

}

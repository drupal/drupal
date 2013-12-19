<?php

/**
 * @file
 * Contains \Drupal\image\Plugin\ImageEffect\RotateImageEffect.
 */

namespace Drupal\image\Plugin\ImageEffect;

use Drupal\Core\Image\ImageInterface;
use Drupal\image\ConfigurableImageEffectInterface;
use Drupal\image\ImageEffectBase;

/**
 * Rotates an image resource.
 *
 * @ImageEffect(
 *   id = "image_rotate",
 *   label = @Translation("Rotate"),
 *   description = @Translation("Rotating an image may cause the dimensions of an image to increase to fit the diagonal.")
 * )
 */
class RotateImageEffect extends ImageEffectBase implements ConfigurableImageEffectInterface {

  /**
   * {@inheritdoc}
   */
  public function applyEffect(ImageInterface $image) {
    // Convert short #FFF syntax to full #FFFFFF syntax.
    if (strlen($this->configuration['bgcolor']) == 4) {
      $c = $this->configuration['bgcolor'];
      $this->configuration['bgcolor'] = $c[0] . $c[1] . $c[1] . $c[2] . $c[2] . $c[3] . $c[3];
    }

    // Convert #FFFFFF syntax to hexadecimal colors.
    if ($this->configuration['bgcolor'] != '') {
      $this->configuration['bgcolor'] = hexdec(str_replace('#', '0x', $this->configuration['bgcolor']));
    }
    else {
      $this->configuration['bgcolor'] = NULL;
    }

    if (!empty($this->configuration['random'])) {
      $degrees = abs((float) $this->configuration['degrees']);
      $this->configuration['degrees'] = rand(-1 * $degrees, $degrees);
    }

    if (!$image->rotate($this->configuration['degrees'], $this->configuration['bgcolor'])) {
      watchdog('image', 'Image rotate failed using the %toolkit toolkit on %path (%mimetype, %dimensions)', array('%toolkit' => $image->getToolkitId(), '%path' => $image->getSource(), '%mimetype' => $image->getMimeType(), '%dimensions' => $image->getWidth() . 'x' . $image->getHeight()), WATCHDOG_ERROR);
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function transformDimensions(array &$dimensions) {
    // If the rotate is not random and the angle is a multiple of 90 degrees,
    // then the new dimensions can be determined.
    if (!$this->configuration['random'] && ((int) ($this->configuration['degrees']) == $this->configuration['degrees']) && ($this->configuration['degrees'] % 90 == 0)) {
      if ($this->configuration['degrees'] % 180 != 0) {
        $temp = $dimensions['width'];
        $dimensions['width'] = $dimensions['height'];
        $dimensions['height'] = $temp;
      }
    }
    else {
      $dimensions['width'] = $dimensions['height'] = NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return array(
      '#theme' => 'image_rotate_summary',
      '#data' => $this->configuration,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'degrees' => 0,
      'bgcolor' => NULL,
      'random' => FALSE,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getForm() {
    $form['degrees'] = array(
      '#type' => 'number',
      '#default_value' => $this->configuration['degrees'],
      '#title' => t('Rotation angle'),
      '#description' => t('The number of degrees the image should be rotated. Positive numbers are clockwise, negative are counter-clockwise.'),
      '#field_suffix' => '&deg;',
      '#required' => TRUE,
    );
    $form['bgcolor'] = array(
      '#type' => 'textfield',
      '#default_value' => $this->configuration['bgcolor'],
      '#title' => t('Background color'),
      '#description' => t('The background color to use for exposed areas of the image. Use web-style hex colors (#FFFFFF for white, #000000 for black). Leave blank for transparency on image types that support it.'),
      '#size' => 7,
      '#maxlength' => 7,
      '#element_validate' => array(array($this, 'validateColorEffect')),
    );
    $form['random'] = array(
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['random'],
      '#title' => t('Randomize'),
      '#description' => t('Randomize the rotation angle for each image. The angle specified above is used as a maximum.'),
    );
    return $form;
  }

  /**
   * Validates to ensure a hexadecimal color value.
   */
  public function validateColorEffect(array $element, array &$form_state) {
    if ($element['#value'] != '') {
      if (!preg_match('/^#[0-9A-F]{3}([0-9A-F]{3})?$/', $element['#value'])) {
        form_error($element, $form_state, t('!name must be a hexadecimal color value.', array('!name' => $element['#title'])));
      }
    }
  }

}

<?php

/**
 * @file
 * Contains \Drupal\tour_test\Plugin\tour\tour\TipPluginImage.
 */

namespace Drupal\tour_test\Plugin\tour\tip;

use Drupal\Component\Utility\String;
use Drupal\tour\TipPluginBase;

/**
 * Displays an image as a tip.
 *
 * @Tip(
 *   id = "image",
 *   title = @Translation("Image")
 * )
 */
class TipPluginImage extends TipPluginBase {

  /**
   * The url which is used for the image in this Tip.
   *
   * @var string
   *   A url used for the image.
   */
  protected $url;

  /**
   * The alt text which is used for the image in this Tip.
   *
   * @var string
   *   A alt text used for the image.
   */
  protected $alt;

  /**
   * {@inheritdoc}
   */
  public function getOutput() {
    $image = array(
      '#theme' => 'image',
      '#uri' => $this->get('url'),
      '#alt' => $this->get('alt'),
    );
    $output = '<h2 class="tour-tip-label" id="tour-tip-' . $this->get('ariaId') . '-label">' . String::checkPlain($this->get('label')) . '</h2>';
    $output .= '<p class="tour-tip-image" id="tour-tip-' . $this->get('ariaId') . '-contents">' . drupal_render($image) . '</p>';
    return array('#markup' => $output);
  }

}

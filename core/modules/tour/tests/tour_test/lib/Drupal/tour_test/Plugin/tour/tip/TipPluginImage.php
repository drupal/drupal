<?php

/**
 * @file
 * Contains \Drupal\tour_test\Plugin\tour\tour\TipPluginImage.
 */

namespace Drupal\tour_test\Plugin\tour\tip;

use Drupal\Component\Annotation\Plugin;
use Drupal\tour\TipPluginBase;

/**
 * Displays an image as a tip.
 *
 * @Plugin(
 *   id = "image",
 *   module = "tour_test"
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
   * Overrides \Drupal\tour\Plugin\tour\tour\TipPluginInterface::getOutput().
   */
  public function getOutput() {
    $output = '<h2 class="tour-tip-label" id="tour-tip-' . $this->get('ariaId') . '-label">' . check_plain($this->get('label')) . '</h2>';
    $output .= '<p class="tour-tip-image" id="tour-tip-' . $this->get('ariaId') . '-contents">' . theme('image', array('uri' => $this->get('url'), 'alt' => $this->get('alt'))) . '</p>';
    return array('#markup' => $output);
  }

}

<?php

namespace Drupal\tour_test\Plugin\tour\tip;

use Drupal\Component\Utility\Html;
use Drupal\Core\Render\Element\Image;
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
   *   An alt text used for the image.
   */
  protected $alt;

  /**
   * {@inheritdoc}
   */
  public function getOutput() {
    $prefix = '<h2 class="tour-tip-label" id="tour-tip-' . $this->get('ariaId') . '-label">' . Html::escape($this->get('label')) . '</h2>';
    $prefix .= '<p class="tour-tip-image" id="tour-tip-' . $this->get('ariaId') . '-contents">';

    return Image::getBuilder()
      ->setPrefix($prefix)
      ->setUri($this->get('url'))
      ->setAlt($this->get('alt'))
      ->setSuffix('</p>')
      ->toRenderable();
  }

}

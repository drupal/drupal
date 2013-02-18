<?php

/**
 * @file
 * Contains \Drupal\tour\TipPluginText.
 */

namespace Drupal\tour\Plugin\tour\tip;

use Drupal\Core\Annotation\Plugin;
use Drupal\tour\TipPluginBase;

/**
 * Displays some text as a tip.
 *
 * @Plugin(
 *   id = "text",
 *   module = "tour"
 * )
 */
class TipPluginText extends TipPluginBase {

  /**
   * The body text which is used for render of this Text Tip.
   *
   * @var string
   *   A string of text used as the body.
   */
  protected $body;

  /**
   * The forced position of where the tip will be located.
   *
   * @var string
   *   A string of left|right|top|bottom.
   */
  protected $location;

  /**
   * Returns a ID that is guaranteed uniqueness.
   *
   * @return string
   *   A unique string.
   */
  public function getAriaId() {
    static $id;
    if (!isset($id)) {
      $id = drupal_html_id($this->get('id'));
    }
    return $id;
  }

  /**
   * Returns body of the text tip.
   *
   * @return string
   *   The body of the text tip.
   */
  public function getBody() {
    return $this->get('body');
  }

  /**
   * Returns location of the text tip.
   *
   * @return string
   *   The location (left|right|top|bottom) of the text tip.
   */
  public function getLocation() {
    return $this->get('location');
  }

  /**
   * Overrides \Drupal\tour\Plugin\tour\tour\TipPluginInterface::getAttributes();
   */
  public function getAttributes() {
    $attributes = parent::getAttributes();
    $attributes['data-aria-describedby'] = 'tour-tip-' . $this->getAriaId() . '-contents';
    $attributes['data-aria-labelledby'] = 'tour-tip-' . $this->getAriaId() . '-label';
    if ($location = $this->get('location')) {
      $attributes['data-options'] = 'tipLocation:' . $location;
    }
    return $attributes;
  }

  /**
   * Overrides \Drupal\tour\Plugin\tour\tour\TipPluginInterface::getOutput();
   */
  public function getOutput() {
    return array(
      '#markup' => '<h2 class="tour-tip-label" id="tour-tip-' . $this->getAriaId() . '-label">' . check_plain($this->getLabel()) . '</h2>
      <p class="tour-tip-body" id="tour-tip-' . $this->getAriaId() . '-contents">' . filter_xss_admin($this->getBody()) . '</p>'
    );
  }
}

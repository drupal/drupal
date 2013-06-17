<?php

/**
 * @file
 * Contains \Drupal\tour\Plugin\tour\tip\TipPluginText.
 */

namespace Drupal\tour\Plugin\tour\tip;

use Drupal\tour\Annotation\Tip;
use Drupal\tour\TipPluginBase;

/**
 * Displays some text as a tip.
 *
 * @Tip("text")
 */
class TipPluginText extends TipPluginBase {

  /**
   * The body text which is used for render of this Text Tip.
   *
   * @var string
   */
  protected $body;

  /**
   * The forced position of where the tip will be located.
   *
   * @var string
   */
  protected $location;

  /**
   * Returns a ID that is guaranteed uniqueness.
   *
   * @return string
   *   A unique id to be used to generate aria attributes.
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
   *   The tip body.
   */
  public function getBody() {
    return $this->get('body');
  }

  /**
   * Returns location of the text tip.
   *
   * @return string
   *   The tip location.
   */
  public function getLocation() {
    return $this->get('location');
  }

  /**
   * Overrides \Drupal\tour\TipPluginBase::getAttributes().
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
   * Implements \Drupal\tour\TipPluginInterface::getOutput().
   */
  public function getOutput() {
    $output = '<h2 class="tour-tip-label" id="tour-tip-' . $this->getAriaId() . '-label">' . check_plain($this->getLabel()) . '</h2>';
    $output .= '<p class="tour-tip-body" id="tour-tip-' . $this->getAriaId() . '-contents">' . \Drupal::token()->replace(filter_xss_admin($this->getBody())) . '</p>';
    return array('#markup' => $output);
  }
}

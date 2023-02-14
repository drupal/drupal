<?php

namespace Drupal\tour;

use Drupal\Core\Plugin\PluginBase;

/**
 * Defines a base tour item implementation.
 *
 * @see \Drupal\tour\Annotation\Tip
 * @see \Drupal\tour\TipPluginInterface
 * @see \Drupal\tour\TipPluginManager
 * @see plugin_api
 */
abstract class TipPluginBase extends PluginBase implements TipPluginInterface {

  /**
   * The label which is used for render of this tip.
   *
   * @var string
   */
  protected $label;

  /**
   * Allows tips to take more priority that others.
   *
   * @var string
   */
  protected $weight;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->get('id');
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->get('label');
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->get('weight');
  }

  /**
   * {@inheritdoc}
   */
  public function get($key) {
    if (!empty($this->configuration[$key])) {
      return $this->configuration[$key];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function set($key, $value) {
    $this->configuration[$key] = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getLocation(): ?string {
    $location = $this->get('position');

    // The location values accepted by PopperJS, the library used for
    // positioning the tip.
    assert(in_array(trim($location ?? ''), [
      'auto',
      'auto-start',
      'auto-end',
      'top',
      'top-start',
      'top-end',
      'bottom',
      'bottom-start',
      'bottom-end',
      'right',
      'right-start',
      'right-end',
      'left',
      'left-start',
      'left-end',
      '',
    ], TRUE), "$location is not a valid Tour Tip position value");

    return $location;
  }

  /**
   * {@inheritdoc}
   */
  public function getSelector(): ?string {
    return $this->get('selector');
  }

  /**
   * {@inheritdoc}
   */
  public function getBody(): array {
    return [];
  }

}

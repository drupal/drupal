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
   * The attributes that will be applied to the markup of this tip.
   *
   * @var array
   *
   * @deprecated in drupal:9.2.0 and is removed from drupal:10.0.0. There is no
   *   direct replacement. Note that this was never actually used.
   *
   * @see https://www.drupal.org/node/3204096
   */
  protected $attributes;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    if (!$this instanceof TourTipPluginInterface) {
      @trigger_error('Implementing ' . __NAMESPACE__ . '\TipPluginInterface without also implementing ' . __NAMESPACE__ . '\TourTipPluginInterface is deprecated in drupal:9.2.0. See https://www.drupal.org/node/3204096', E_USER_DEPRECATED);
    }
  }

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
   *
   * @todo remove in https://drupal.org/node/3195193
   */
  public function getAttributes() {
    // This method is deprecated and rewritten to be as backwards compatible as
    // possible with pre-existing uses. Due to the flexibility of tip plugins,
    // this backwards compatibility can't be fully guaranteed. Because of this,
    // we trigger a warning to caution the use of this function. This warning
    // does not stop page execution, but will be logged.
    trigger_error(__NAMESPACE__ . '\TipPluginInterface::getAttributes is deprecated. Tour tip plugins should implement ' . __NAMESPACE__ . '\TourTipPluginInterface and Tour configs should use the \'selector\' property instead of \'attributes\' to target an element.', E_USER_WARNING);

    // The _tour_update_joyride() updates the deprecated 'attributes' property
    // to the current 'selector' property. It's possible that additional
    // attributes not supported by Drupal core exist and these need to merged
    // in.
    $attributes = $this->get('attributes') ?: [];

    // Convert the selector property to the expected structure.
    $selector = $this->get('selector');
    $first_char = substr($selector, 0, 1);
    if ($first_char === '#') {
      $attributes['data-id'] = substr($selector, 1);
    }
    elseif ($first_char === '.') {
      $attributes['data-class'] = substr($selector, 1);
    }

    return $attributes;
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
   * This method should not be used. It is deprecated from TipPluginInterface.
   *
   * @return array
   *   An intentionally empty array.
   *
   * @todo remove in https://drupal.org/node/3195193
   */
  public function getOutput() {
    // The getOutput() method was a requirement of TipPluginInterface, but was
    // not part of TipPluginBase prior to it being deprecated. As a result, all
    // tip plugins have their own implementations of getOutput() making it
    // unlikely that this implementation will be called. If it does get called,
    // however, the tour tip will have no content due to this method returning
    // an empty array. To help tour tips from unexpectedly having no content, a
    // warning is triggered. This warning does not stop page
    // execution, but will be logged.
    trigger_error(__NAMESPACE__ . 'TipPluginInterface::getOutput is deprecated. Use getBody() instead. See https://www.drupal.org/node/3204096', E_USER_WARNING);

    // This class must implement TipPluginInterface, but this method is
    // deprecated. An empty array is returned to meet interface requirements.
    return [];
  }

  /**
   * Determines the placement of the tip relative to the element.
   *
   * If null, the tip will automatically determine the best position based on
   * the element's position in the viewport.
   *
   * @return string|null
   *   The tip placement relative to the element.
   *
   * @see https://shepherdjs.dev/docs/Step.html
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
   * The selector the tour tip will attach to.
   *
   * This is mapped to the `attachTo.element` property of the Shepherd tooltip
   * options.
   *
   * @return null|string
   *   A selector string, or null for an unattached tip.
   *
   * @see https://shepherdjs.dev/docs/Step.html
   */
  public function getSelector(): ?string {
    return $this->get('selector');
  }

}

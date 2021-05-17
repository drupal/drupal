<?php

namespace Drupal\tour;

use Drupal\Core\DependencyInjection\DeprecatedServicePropertyTrait;
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

  use DeprecatedServicePropertyTrait;

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
  protected $deprecatedProperties = ['attributes' => 'attributes'];

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    if (!$this instanceof TourTipPluginInterface) {
      @trigger_error('Tip plugins implementing ' . __NAMESPACE__ . '\TipPluginInterface that don\'t also implement ' . __NAMESPACE__ . '\TourTipPluginInterface are deprecated in drupal:9.2.0. See https://www.drupal.org/node/3204096', E_USER_DEPRECATED);
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

    // When available, use the selector property to return an array with the
    // expected structure.
    if ($selector = $this->get('selector')) {
      $first_char = substr($selector, 0, 1);
      $other_chars = substr($selector, 1);
      if ($first_char === '#') {
        return ['data-id' => $other_chars];
      }
      if ($first_char === '.') {
        return ['data-class' => $other_chars];
      }
    }

    // The tour_post_update_joyride_selectors_to_selector_property() post_update
    // hook converts all uses of the deprecated 'attributes' property to the
    // current 'selector' property. It's possible for tour config with this
    // deprecated property to be installed without this hook having run. Return
    // the attributes value in those instances. If attributes has no value,
    // return an empty array.
    // @see tour_post_update_joyride_selectors_to_selector_property()
    return $this->get('attributes') ?: [];
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
    // however, the return value of an empty array is not likely the desired
    // result, so a warning is triggered. This warning does not stop page
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
  public function getLocation() {
    $location = $this->get('position');

    // The location values accepted by PopperJS, the library used for
    // positioning the tip.
    $valid_values = [
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
      NULL,
    ];

    assert(in_array(trim($location), $valid_values), new \LogicException("$location is not a valid Tour Tip position value."));

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
   *
   * @todo this can probably be simplified in https://drupal.org/node/3195193
   *    to `$this->get('selector')`.
   */
  public function getSelector() {
    // If selector isn't null, return immediately. If it is null, it may be
    // intentional, but it may also be due to the selector value being provided
    // in deprecated Joyride config. Check for that before returning a value.
    if ($selector = $this->get('selector')) {
      return $selector;
    }

    // The tour_post_update_joyride_selectors_to_selector_property() post_update
    // hook converts all uses of the deprecated 'attributes' property to the
    // current 'selector' property. It's possible for tour config with this
    // deprecated property to be installed without this hook having run. In
    // those instance it may use `attributes` instead of the `selector` property
    // to associate the tip with an element.
    // @see tour_post_update_joyride_selectors_to_selector_property()
    $attributes = $this->get('attributes');
    if (isset($attributes['data-id'])) {
      $selector = "#{$attributes['data-id']}";
    }
    elseif (isset($attributes['data-class'])) {
      $selector = ".{$attributes['data-class']}";
    }
    return $selector;
  }

}

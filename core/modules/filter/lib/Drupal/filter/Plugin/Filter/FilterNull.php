<?php

/**
 * @file
 * Contains \Drupal\filter\Plugin\Filter\FilterNull.
 */

namespace Drupal\filter\Plugin\Filter;

use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a fallback placeholder filter to use for missing filters.
 *
 * The filter system uses this filter to replace missing filters (for example,
 * if a filter module has been disabled) that are still part of defined text
 * formats. It returns an empty string.
 *
 * @Filter(
 *   id = "filter_null",
 *   title = @Translation("Provides a fallback for missing filters. Do not use."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_HTML_RESTRICTOR,
 *   weight = -10
 * )
 */
class FilterNull extends FilterBase {

  /**
   * Tracks if an alert about this filter has been logged.
   *
   * @var bool
   */
  protected $logged = FALSE;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    // Once per filter, log that a filter plugin was missing.
    if (!$this->logged) {
      $this->logged = TRUE;
      watchdog('filter', 'Missing filter plugin: %filter.', array('%filter' => $plugin_id), WATCHDOG_ALERT);
    }
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode, $cache, $cache_id) {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getHTMLRestrictions() {
    // Nothing is allowed.
    return array('allowed' => array());
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    return t('Missing filter. All text is removed');
  }

}

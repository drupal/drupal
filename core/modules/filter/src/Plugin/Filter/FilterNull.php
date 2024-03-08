<?php

namespace Drupal\filter\Plugin\Filter;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\filter\Attribute\Filter;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\Plugin\FilterInterface;

/**
 * Provides a fallback placeholder filter to use for missing filters.
 *
 * The filter system uses this filter to replace missing filters (for example,
 * if a filter module has been disabled) that are still part of defined text
 * formats. It returns an empty string.
 */
#[Filter(
  id: "filter_null",
  title: new TranslatableMarkup("Provides a fallback for missing filters. Do not use."),
  type: FilterInterface::TYPE_HTML_RESTRICTOR,
  weight: -10
)]
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
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    // Once per filter, log that a filter plugin was missing.
    if (!$this->logged) {
      $this->logged = TRUE;
      \Drupal::logger('filter')->alert('Missing filter plugin: %filter.', ['%filter' => $plugin_id]);
    }
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    return new FilterProcessResult('');
  }

  /**
   * {@inheritdoc}
   */
  public function getHTMLRestrictions() {
    // Nothing is allowed.
    return ['allowed' => []];
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    return $this->t('Missing filter. All text is removed');
  }

}

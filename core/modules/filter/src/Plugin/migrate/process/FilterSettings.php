<?php

namespace Drupal\filter\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Adds the default allowed attributes to filter_html's allowed_html setting.
 *
 * E.g. map '<a>' to '<a href hreflang dir>'.
 *
 * @MigrateProcessPlugin(
 *   id = "filter_settings",
 *   handle_multiples = TRUE
 * )
 */
class FilterSettings extends ProcessPluginBase {

  /**
   * Default attributes for migrating filter_html's 'allowed_html' setting.
   *
   * @var string[]
   */
  protected $allowedHtmlDefaultAttributes = [
    '<a>' => '<a href hreflang>',
    '<blockquote>' => '<blockquote cite>',
    '<ol>' => '<ol start type>',
    '<ul>' => '<ul type>',
    '<img>' => '<img src alt height width>',
    '<h2>' => '<h2 id>',
    '<h3>' => '<h3 id>',
    '<h4>' => '<h4 id>',
    '<h5>' => '<h5 id>',
    '<h6>' => '<h6 id>',
  ];

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Only the filter_html filter's settings have a changed format.
    if ($row->getDestinationProperty('id') === 'filter_html') {
      if (!empty($value['allowed_html'])) {
        $value['allowed_html'] = str_replace(array_keys($this->allowedHtmlDefaultAttributes), array_values($this->allowedHtmlDefaultAttributes), $value['allowed_html']);
      }
    }
    // Filters that don't exist in Drupal 8 will have been mapped to filter_null
    // but will have their settings (if any) retained. Those filter settings
    // need to be dropped, otherwise saving the resulting FilterFormat config
    // entity will be unable to save due to config schema validation errors.
    // The migration warning message in the "filter_id" migration process plugin
    // warns the user about this.
    // @see \Drupal\filter\Plugin\migrate\process\FilterID::transform()
    elseif ($row->getDestinationProperty('id') === 'filter_null') {
      $value = [];
    }
    return $value;
  }

}

<?php

namespace Drupal\text\Plugin\migrate\field\d7;

use Drupal\migrate\Row;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

/**
 * @MigrateField(
 *   id = "d7_text",
 *   type_map = {
 *     "text" = "text",
 *     "text_long" = "text_long",
 *     "text_with_summary" = "text_with_summary"
 *   },
 *   core = {7},
 *   source_module = "text",
 *   destination_module = "text",
 * )
 */
class TextField extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldFormatterType(Row $row) {
    $field_type = $this->getFieldType($row);
    $formatter_type = $row->getSourceProperty('formatter/type');

    switch ($field_type) {
      case 'string':
        $formatter_type = str_replace(['text_default', 'text_plain'], 'string', $formatter_type);
        break;

      case 'string_long':
        $formatter_type = str_replace(['text_default', 'text_plain'], 'basic_string', $formatter_type);
        break;
    }

    return $formatter_type;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldWidgetType(Row $row) {
    $field_type = $this->getFieldType($row);
    $widget_type = $row->getSourceProperty('widget/type');

    switch ($field_type) {
      case 'string':
        $widget_type = str_replace('text_textfield', 'string_textfield', $widget_type);
        break;

      case 'string_long':
        $widget_type = str_replace('text_textarea', 'string_textarea', $widget_type);
        break;
    }

    return $widget_type;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldType(Row $row) {
    $type = $row->getSourceProperty('type');
    $plain_text = FALSE;
    $filtered_text = FALSE;

    foreach ($row->getSourceProperty('instances') as $instance) {
      // Check if this field has plain text instances, filtered text instances,
      // or both.
      $data = unserialize($instance['data']);
      switch ($data['settings']['text_processing']) {
        case '0':
          $plain_text = TRUE;
          break;

        case '1':
          $filtered_text = TRUE;
          break;
      }
    }

    if (in_array($type, ['text', 'text_long'])) {
      // If a text or text_long field has only plain text instances, migrate it
      // to a string or string_long field.
      if ($plain_text && !$filtered_text) {
        $type = str_replace(['text', 'text_long'], ['string', 'string_long'], $type);
      }
      // If a text or text_long field has both plain text and filtered text
      // instances, skip the row.
      elseif ($plain_text && $filtered_text) {
        $field_name = $row->getSourceProperty('field_name');
        throw new MigrateSkipRowException("Can't migrate source field $field_name configured with both plain text and filtered text processing. See https://www.drupal.org/docs/8/upgrade/known-issues-when-upgrading-from-drupal-6-or-7-to-drupal-8#plain-text");
      }
    }
    elseif ($type == 'text_with_summary' && $plain_text) {
      // If a text_with_summary field has plain text instances, skip the row
      // since there's no such thing as a string_with_summary field.
      $field_name = $row->getSourceProperty('field_name');
      throw new MigrateSkipRowException("Can't migrate source field $field_name of type text_with_summary configured with plain text processing. See https://www.drupal.org/docs/8/upgrade/known-issues-when-upgrading-from-drupal-6-or-7-to-drupal-8#plain-text");
    }

    return $type;
  }

}

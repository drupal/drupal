<?php

/**
 * @file
 * Contains \Drupal\text\Plugin\field\formatter\TextSummaryOrTrimmedFormatter.
 */

namespace Drupal\text\Plugin\Field\FieldFormatter;

/**
 * Plugin implementation of the 'text_summary_or_trimmed' formatter.
 *
 * @FieldFormatter(
 *   id = "text_summary_or_trimmed",
 *   label = @Translation("Summary or trimmed"),
 *   field_types = {
 *     "text_with_summary"
 *   },
 *   settings = {
 *     "trim_length" = "600"
 *   },
 *   edit = {
 *     "editor" = "form"
 *   }
 * )
 */
class TextSummaryOrTrimmedFormatter extends TextTrimmedFormatter { }

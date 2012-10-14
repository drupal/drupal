<?php

/**
 * @file
 *
 * Definition of Drupal\text\Plugin\field\formatter\TextSummaryOrTrimmedFormatter.
 */
namespace Drupal\text\Plugin\field\formatter;

use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Plugin implementation of the 'text_summary_or_trimmed' formatter.
 *
 * @Plugin(
 *   id = "text_summary_or_trimmed",
 *   module = "text",
 *   label = @Translation("Summary or trimmed"),
 *   field_types = {
 *     "text_with_summary"
 *   },
 *   settings = {
 *     "trim_length" = "600"
 *   }
 * )
 */
class TextSummaryOrTrimmedFormatter extends TextTrimmedFormatter { }

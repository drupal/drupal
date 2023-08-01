<?php

namespace Drupal\editor;

use Drupal\filter\FilterFormatInterface;

/**
 * Defines an interface for text editor XSS (Cross-site scripting) filters.
 */
interface EditorXssFilterInterface {

  /**
   * Filters HTML to prevent XSS attacks when a user edits it in a text editor.
   *
   * Should filter as minimally as possible, only to remove XSS attack vectors.
   *
   * Is only called when:
   * - loading a non-XSS-safe text editor for a $format that contains a filter
   *   preventing XSS attacks (a FilterInterface::TYPE_HTML_RESTRICTOR filter):
   *   if the output is safe, it should also be safe to edit.
   * - loading a non-XSS-safe text editor for a $format that doesn't contain a
   *   filter preventing XSS attacks, but we're switching from a previous text
   *   format ($original_format is not NULL) that did prevent XSS attacks: if
   *   the output was previously safe, it should be safe to switch to another
   *   text format and edit.
   *
   * @param string $html
   *   The HTML to be filtered.
   * @param \Drupal\filter\FilterFormatInterface $format
   *   The text format configuration entity. Provides context based upon which
   *   one may want to adjust the filtering.
   * @param \Drupal\filter\FilterFormatInterface|null $original_format
   *   (optional) The original text format configuration entity (when switching
   *   text formats/editors). Also provides context based upon which one may
   *   want to adjust the filtering.
   *
   * @return string
   *   The filtered HTML that cannot cause any XSS anymore.
   */
  public static function filterXss($html, FilterFormatInterface $format, FilterFormatInterface $original_format = NULL);

}

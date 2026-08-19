<?php

namespace Drupal\Component\Diff;

/**
 * FIXME: bad name.
 *
 * @todo document
 * @private
 * @subpackage DifferenceEngine
 */
class MappedDiff extends Diff {

  /**
   * Constructor.
   *
   * Computes diff between sequences of strings.
   *
   * This can be used to compute things like
   * case-insensitive diffs, or diffs which ignore
   * changes in white-space.
   *
   * @param array $from_lines
   *   An array of strings.
   *   (Typically these are lines from a file.)
   * @param array $to_lines
   *   An array of strings.
   * @param array $mapped_from_lines
   *   This array should have the same size number of elements as $from_lines.
   *   The elements in $mapped_from_lines and $mapped_to_lines are what is
   *   actually compared when computing the diff.
   * @param array $mapped_to_lines
   *   This array should have the same number of elements as $to_lines.
   */
  public function __construct($from_lines, $to_lines, $mapped_from_lines, $mapped_to_lines) {

    assert(count($from_lines) == count($mapped_from_lines));
    assert(count($to_lines) == count($mapped_to_lines));

    parent::__construct($mapped_from_lines, $mapped_to_lines);

    $xi = $yi = 0;
    for ($i = 0; $i < count($this->edits); $i++) {
      $orig = &$this->edits[$i]->orig;
      if (is_array($orig)) {
        $orig = array_slice($from_lines, $xi, count($orig));
        $xi += count($orig);
      }

      $closing = &$this->edits[$i]->closing;
      if (is_array($closing)) {
        $closing = array_slice($to_lines, $yi, count($closing));
        $yi += count($closing);
      }
    }
  }

}

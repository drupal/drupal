<?php

namespace Drupal\Component\Diff;

use SebastianBergmann\Diff\Differ;

/**
 * Class representing a 'diff' between two sequences of strings.
 *
 * Component code originally taken from https://www.drupal.org/project/diff
 * which was itself based on the PHP diff engine for phpwiki. The original code
 * in phpwiki was copyright (C) 2000, 2001 Geoffrey T. Dairiki
 * <dairiki@dairiki.org> and licensed under GPL.
 */
class Diff {

  /**
   * The list of differences as an array of diff operations.
   *
   * @var \Drupal\Component\Diff\Engine\DiffOp[]
   */
  protected $edits;

  /**
   * Constructor.
   * Computes diff between sequences of strings.
   *
   * @param array $from_lines
   *   An array of strings.
   *   (Typically these are lines from a file.)
   * @param array $to_lines
   *   An array of strings.
   */
  public function __construct($from_lines, $to_lines) {
    $diffOpBuilder = new DiffOpOutputBuilder();
    $differ = new Differ($diffOpBuilder);
    $this->edits = $diffOpBuilder->toOpsArray($differ->diffToArray($from_lines, $to_lines));
  }

  /**
   * Compute reversed Diff.
   *
   * SYNOPSIS:
   *
   *  $diff = new Diff($lines1, $lines2);
   *  $rev = $diff->reverse();
   * @return object
   *   A Diff object representing the inverse of the original diff.
   *
   * @deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. There is no
   *   replacement.
   *
   * @see https://www.drupal.org/node/3337942
   */
  public function reverse() {
    @trigger_error(__METHOD__ . '() is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. There is no replacement. See https://www.drupal.org/node/3337942', E_USER_DEPRECATED);
    $rev = $this;
    $rev->edits = [];
    foreach ($this->edits as $edit) {
      $rev->edits[] = $edit->reverse();
    }
    return $rev;
  }

  /**
   * Check for empty diff.
   *
   * @return bool True iff two sequences were identical.
   *
   * @deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. There is no
   *   replacement.
   *
   * @see https://www.drupal.org/node/3337942
   */
  public function isEmpty() {
    @trigger_error(__METHOD__ . '() is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. There is no replacement. See https://www.drupal.org/node/3337942', E_USER_DEPRECATED);
    foreach ($this->edits as $edit) {
      if ($edit->type != 'copy') {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Compute the length of the Longest Common Subsequence (LCS).
   *
   * This is mostly for diagnostic purposed.
   *
   * @return int The length of the LCS.
   *
   * @deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. There is no
   *   replacement.
   *
   * @see https://www.drupal.org/node/3337942
   */
  public function lcs() {
    @trigger_error(__METHOD__ . '() is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. There is no replacement. See https://www.drupal.org/node/3337942', E_USER_DEPRECATED);
    $lcs = 0;
    foreach ($this->edits as $edit) {
      if ($edit->type == 'copy') {
        $lcs += sizeof($edit->orig);
      }
    }
    return $lcs;
  }

  /**
   * Gets the original set of lines.
   *
   * This reconstructs the $from_lines parameter passed to the
   * constructor.
   *
   * @return array The original sequence of strings.
   *
   * @deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. There is no
   *   replacement.
   *
   * @see https://www.drupal.org/node/3337942
   */
  public function orig() {
    @trigger_error(__METHOD__ . '() is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. There is no replacement. See https://www.drupal.org/node/3337942', E_USER_DEPRECATED);
    $lines = [];

    foreach ($this->edits as $edit) {
      if ($edit->orig) {
        array_splice($lines, sizeof($lines), 0, $edit->orig);
      }
    }
    return $lines;
  }

  /**
   * Gets the closing set of lines.
   *
   * This reconstructs the $to_lines parameter passed to the
   * constructor.
   *
   * @return array The sequence of strings.
   *
   * @deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. There is no
   *   replacement.
   *
   * @see https://www.drupal.org/node/3337942
   */
  public function closing() {
    @trigger_error(__METHOD__ . '() is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. There is no replacement. See https://www.drupal.org/node/3337942', E_USER_DEPRECATED);
    $lines = [];

    foreach ($this->edits as $edit) {
      if ($edit->closing) {
        array_splice($lines, sizeof($lines), 0, $edit->closing);
      }
    }
    return $lines;
  }

  /**
   * Check a Diff for validity.
   *
   * This is here only for debugging purposes.
   *
   * @deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. There is no
   *   replacement.
   *
   * @see https://www.drupal.org/node/3337942
   */
  public function check($from_lines, $to_lines) {
    @trigger_error(__METHOD__ . '() is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. There is no replacement. See https://www.drupal.org/node/3337942', E_USER_DEPRECATED);
    if (serialize($from_lines) != serialize($this->orig())) {
      trigger_error("Reconstructed original doesn't match", E_USER_WARNING);
    }
    if (serialize($to_lines) != serialize($this->closing())) {
      trigger_error("Reconstructed closing doesn't match", E_USER_WARNING);
    }

    $rev = $this->reverse();
    if (serialize($to_lines) != serialize($rev->orig())) {
      trigger_error("Reversed original doesn't match", E_USER_WARNING);
    }
    if (serialize($from_lines) != serialize($rev->closing())) {
      trigger_error("Reversed closing doesn't match", E_USER_WARNING);
    }

    $prevtype = 'none';
    foreach ($this->edits as $edit) {
      if ( $prevtype == $edit->type ) {
        trigger_error("Edit sequence is non-optimal", E_USER_WARNING);
      }
      $prevtype = $edit->type;
    }

    $lcs = $this->lcs();
    trigger_error('Diff okay: LCS = ' . $lcs, E_USER_NOTICE);
  }

  /**
   * Gets the list of differences as an array of diff operations.
   *
   * @return \Drupal\Component\Diff\Engine\DiffOp[]
   *   The list of differences as an array of diff operations.
   */
  public function getEdits() {
    return $this->edits;
  }

}

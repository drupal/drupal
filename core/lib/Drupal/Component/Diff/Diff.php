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
   * Gets the list of differences as an array of diff operations.
   *
   * @return \Drupal\Component\Diff\Engine\DiffOp[]
   *   The list of differences as an array of diff operations.
   */
  public function getEdits() {
    return $this->edits;
  }

}

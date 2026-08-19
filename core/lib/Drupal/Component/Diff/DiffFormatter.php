<?php

namespace Drupal\Component\Diff;

use Drupal\Component\Diff\Engine\DiffOpCopy;

/**
 * A class to format Diffs.
 *
 * This class formats the diff in classic diff format.
 * It is intended that this class be customized via inheritance,
 * to obtain fancier outputs.
 *
 * @private
 * @subpackage DifferenceEngine
 */
class DiffFormatter {
  /**
   * Whether a block header should be shown.
   *
   * @var bool
   */
  // phpcs:ignore Drupal.NamingConventions.ValidVariableName.LowerCamelName
  public $show_header = TRUE;

  /**
   * Number of leading context "lines" to preserve.
   *
   * This should be left at zero for this class, but subclasses
   * may want to set this to other values.
   *
   * @var int
   */
  // phpcs:ignore Drupal.NamingConventions.ValidVariableName.LowerCamelName
  public $leading_context_lines = 0;

  /**
   * Number of trailing context "lines" to preserve.
   *
   * This should be left at zero for this class, but subclasses
   * may want to set this to other values.
   *
   * @var int
   */
  // phpcs:ignore Drupal.NamingConventions.ValidVariableName.LowerCamelName
  public $trailing_context_lines = 0;

  /**
   * The line stats.
   *
   * @var array
   */
  // phpcs:ignore Drupal.NamingConventions.ValidVariableName.LowerCamelName
  protected $line_stats = [
    'counter' => ['x' => 0, 'y' => 0],
    'offset' => ['x' => 0, 'y' => 0],
  ];

  /**
   * Format a diff.
   *
   * @param \Drupal\Component\Diff\Diff $diff
   *   A Diff object.
   *
   * @return string
   *   The formatted output.
   */
  public function format(Diff $diff) {
    $x0 = $y0 = 0;
    $xi = $yi = 1;
    $block = FALSE;
    $context = [];

    $nlead = $this->leading_context_lines;
    $ntrail = $this->trailing_context_lines;

    $this->_start_diff();

    foreach ($diff->getEdits() as $edit) {
      if ($edit->type == 'copy') {
        if (is_array($block)) {
          if (count($edit->orig) <= $nlead + $ntrail) {
            $block[] = $edit;
          }
          else {
            if ($ntrail) {
              $context = array_slice($edit->orig, 0, $ntrail);
              $block[] = new DiffOpCopy($context);
            }
            $this->_block($x0, $ntrail + $xi - $x0, $y0, $ntrail + $yi - $y0, $block);
            $block = FALSE;
          }
        }
        $context = $edit->orig;
      }
      else {
        if (!is_array($block)) {
          $context = array_slice($context, count($context) - $nlead);
          $x0 = $xi - count($context);
          $y0 = $yi - count($context);
          $block = [];
          if ($context) {
            $block[] = new DiffOpCopy($context);
          }
        }
        $block[] = $edit;
      }

      if ($edit->orig) {
        $xi += count($edit->orig);
      }
      if ($edit->closing) {
        $yi += count($edit->closing);
      }
    }

    if (is_array($block)) {
      $this->_block($x0, $xi - $x0, $y0, $yi - $y0, $block);
    }
    $end = $this->_end_diff();

    if (!empty($xi)) {
      $this->line_stats['counter']['x'] += $xi;
    }
    if (!empty($yi)) {
      $this->line_stats['counter']['y'] += $yi;
    }

    return $end;
  }

  /**
   * Format a diff block.
   *
   * @param int $xbeg
   *   Starting line number in the original block.
   * @param int $xlen
   *   Length of the changed lines in the original block.
   * @param int $ybeg
   *   Starting line number in the new block.
   * @param int $ylen
   *   Length of the changed lines in the new block.
   * @param \Drupal\Component\Diff\Engine\DiffOp[] $edits
   *   An array of diff operations.
   *
   * @return void
   *   No return value.
   */
  protected function _block($xbeg, $xlen, $ybeg, $ylen, &$edits) {
    $this->_start_block($this->_block_header($xbeg, $xlen, $ybeg, $ylen));
    foreach ($edits as $edit) {
      if ($edit->type == 'copy') {
        $this->_context($edit->orig);
      }
      elseif ($edit->type == 'add') {
        $this->_added($edit->closing);
      }
      elseif ($edit->type == 'delete') {
        $this->_deleted($edit->orig);
      }
      elseif ($edit->type == 'change') {
        $this->_changed($edit->orig, $edit->closing);
      }
      else {
        trigger_error('Unknown edit type', E_USER_WARNING);
      }
    }
    $this->_end_block();
  }

  /**
   * Prepare the start of the diff output.
   *
   * @return void
   *   No return value.
   */
  protected function _start_diff() {
    ob_start();
  }

  /**
   * Complete the output of the diff.
   *
   * @return string
   *   Returns the diff output.
   */
  protected function _end_diff() {
    $val = ob_get_contents();
    ob_end_clean();
    return $val;
  }

  /**
   * Create a block header.
   *
   * @param int $xbeg
   *   Starting line number in the original block.
   * @param int $xlen
   *   Length of the changed lines in the original block.
   * @param int $ybeg
   *   Starting line number in the new block.
   * @param int $ylen
   *   Length of the changed lines in the new block.
   *
   * @return string
   *   Returns the block header.
   */
  protected function _block_header($xbeg, $xlen, $ybeg, $ylen) {
    if ($xlen > 1) {
      $xbeg .= "," . ($xbeg + $xlen - 1);
    }
    if ($ylen > 1) {
      $ybeg .= "," . ($ybeg + $ylen - 1);
    }

    return $xbeg . ($xlen ? ($ylen ? 'c' : 'd') : 'a') . $ybeg;
  }

  /**
   * Format the header at the start of the diff block.
   *
   * @param string $header
   *   The header to output.
   *
   * @return void
   *   No return value.
   */
  protected function _start_block($header) {
    if ($this->show_header) {
      echo $header . "\n";
    }
  }

  /**
   * Format the end of the diff block.
   *
   * @return void
   *   No return value.
   */
  protected function _end_block() {
  }

  /**
   * Format each line prefixed with the given character.
   *
   * @param string[] $lines
   *   An array of lines.
   * @param string $prefix
   *   The character to use when prefixing the lines (like '<' or '>');
   *   defaults to ' '.
   *
   * @return void
   *   No return value.
   */
  protected function _lines($lines, $prefix = ' ') {
    foreach ($lines as $line) {
      echo "$prefix $line\n";
    }
  }

  /**
   * Format lines that provide context to the changed lines.
   *
   * @param string[] $lines
   *   An array of lines.
   *
   * @return void
   *   No return value.
   */
  protected function _context($lines) {
    $this->_lines($lines);
  }

  /**
   * Format lines that have been added.
   *
   * @param string[] $lines
   *   An array of lines.
   *
   * @return void
   *   No return value.
   */
  protected function _added($lines) {
    $this->_lines($lines, '>');
  }

  /**
   * Format lines that have been deleted.
   *
   * @param string[] $lines
   *   An array of lines.
   *
   * @return void
   *   No return value.
   */
  protected function _deleted($lines) {
    $this->_lines($lines, '<');
  }

  /**
   * Format lines that have been changed.
   *
   * @param string[] $orig
   *   An array of the original lines.
   * @param string[] $closing
   *   An array of the new lines.
   *
   * @return void
   *   No return value.
   */
  protected function _changed($orig, $closing) {
    $this->_deleted($orig);
    echo "---\n";
    $this->_added($closing);
  }

}

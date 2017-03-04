<?php

namespace Drupal\Component\Diff;

use Drupal\Component\Diff\Engine\DiffOpCopy;

/**
 * A class to format Diffs
 *
 * This class formats the diff in classic diff format.
 * It is intended that this class be customized via inheritance,
 * to obtain fancier outputs.
 * @todo document
 * @private
 * @subpackage DifferenceEngine
 */
class DiffFormatter {
  /**
   * Should a block header be shown?
   */
  public $show_header = TRUE;

  /**
   * Number of leading context "lines" to preserve.
   *
   * This should be left at zero for this class, but subclasses
   * may want to set this to other values.
   */
  public $leading_context_lines = 0;

  /**
   * Number of trailing context "lines" to preserve.
   *
   * This should be left at zero for this class, but subclasses
   * may want to set this to other values.
   */
  public $trailing_context_lines = 0;

  /**
   * The line stats.
   *
   * @var array
   */
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
    $xi = $yi = 1;
    $block = FALSE;
    $context = [];

    $nlead = $this->leading_context_lines;
    $ntrail = $this->trailing_context_lines;

    $this->_start_diff();

    foreach ($diff->getEdits() as $edit) {
      if ($edit->type == 'copy') {
        if (is_array($block)) {
          if (sizeof($edit->orig) <= $nlead + $ntrail) {
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
          $context = array_slice($context, sizeof($context) - $nlead);
          $x0 = $xi - sizeof($context);
          $y0 = $yi - sizeof($context);
          $block = [];
          if ($context) {
            $block[] = new DiffOpCopy($context);
          }
        }
        $block[] = $edit;
      }

      if ($edit->orig) {
        $xi += sizeof($edit->orig);
      }
      if ($edit->closing) {
        $yi += sizeof($edit->closing);
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
        trigger_error('Unknown edit type', E_USER_ERROR);
      }
    }
    $this->_end_block();
  }

  protected function _start_diff() {
    ob_start();
  }

  protected function _end_diff() {
    $val = ob_get_contents();
    ob_end_clean();
    return $val;
  }

  protected function _block_header($xbeg, $xlen, $ybeg, $ylen) {
    if ($xlen > 1) {
      $xbeg .= "," . ($xbeg + $xlen - 1);
    }
    if ($ylen > 1) {
      $ybeg .= "," . ($ybeg + $ylen - 1);
    }

    return $xbeg . ($xlen ? ($ylen ? 'c' : 'd') : 'a') . $ybeg;
  }

  protected function _start_block($header) {
    if ($this->show_header) {
      echo $header . "\n";
    }
  }

  protected function _end_block() {
  }

  protected function _lines($lines, $prefix = ' ') {
    foreach ($lines as $line) {
      echo "$prefix $line\n";
    }
  }

  protected function _context($lines) {
    $this->_lines($lines);
  }

  protected function _added($lines) {
    $this->_lines($lines, '>');
  }

  protected function _deleted($lines) {
    $this->_lines($lines, '<');
  }

  protected function _changed($orig, $closing) {
    $this->_deleted($orig);
    echo "---\n";
    $this->_added($closing);
  }

}

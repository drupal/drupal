<?php

/**
 * @file
 * A PHP diff engine for phpwiki. (Taken from phpwiki-1.3.3)
 *
 * Copyright (C) 2000, 2001 Geoffrey T. Dairiki <dairiki@dairiki.org>
 * You may copy this code freely under the conditions of the GPL.
 */

define('USE_ASSERTS', FALSE);

/**
 * @todo document
 * @private
 * @subpackage DifferenceEngine
 */
class _DiffOp {
  var $type;
  var $orig;
  var $closing;

  function reverse() {
    trigger_error('pure virtual', E_USER_ERROR);
  }

  function norig() {
    return $this->orig ? sizeof($this->orig) : 0;
  }

  function nclosing() {
    return $this->closing ? sizeof($this->closing) : 0;
  }
}

/**
 * @todo document
 * @private
 * @subpackage DifferenceEngine
 */
class _DiffOp_Copy extends _DiffOp {
  var $type = 'copy';

  function _DiffOp_Copy($orig, $closing = FALSE) {
    if (!is_array($closing)) {
      $closing = $orig;
    }
    $this->orig = $orig;
    $this->closing = $closing;
  }

  function reverse() {
    return new _DiffOp_Copy($this->closing, $this->orig);
  }
}

/**
 * @todo document
 * @private
 * @subpackage DifferenceEngine
 */
class _DiffOp_Delete extends _DiffOp {
  var $type = 'delete';

  function _DiffOp_Delete($lines) {
    $this->orig = $lines;
    $this->closing = FALSE;
  }

  function reverse() {
    return new _DiffOp_Add($this->orig);
  }
}

/**
 * @todo document
 * @private
 * @subpackage DifferenceEngine
 */
class _DiffOp_Add extends _DiffOp {
  var $type = 'add';

  function _DiffOp_Add($lines) {
    $this->closing = $lines;
    $this->orig = FALSE;
  }

  function reverse() {
    return new _DiffOp_Delete($this->closing);
  }
}

/**
 * @todo document
 * @private
 * @subpackage DifferenceEngine
 */
class _DiffOp_Change extends _DiffOp {
  var $type = 'change';

  function _DiffOp_Change($orig, $closing) {
    $this->orig = $orig;
    $this->closing = $closing;
  }

  function reverse() {
    return new _DiffOp_Change($this->closing, $this->orig);
  }
}


/**
 * Class used internally by Diff to actually compute the diffs.
 *
 * The algorithm used here is mostly lifted from the perl module
 * Algorithm::Diff (version 1.06) by Ned Konz, which is available at:
 *   http://www.perl.com/CPAN/authors/id/N/NE/NEDKONZ/Algorithm-Diff-1.06.zip
 *
 * More ideas are taken from:
 *   http://www.ics.uci.edu/~eppstein/161/960229.html
 *
 * Some ideas are (and a bit of code) are from from analyze.c, from GNU
 * diffutils-2.7, which can be found at:
 *   ftp://gnudist.gnu.org/pub/gnu/diffutils/diffutils-2.7.tar.gz
 *
 * closingly, some ideas (subdivision by NCHUNKS > 2, and some optimizations)
 * are my own.
 *
 * Line length limits for robustness added by Tim Starling, 2005-08-31
 *
 * @author Geoffrey T. Dairiki, Tim Starling
 * @private
 * @subpackage DifferenceEngine
 */
class _DiffEngine {
  function MAX_XREF_LENGTH() {
    return 10000;
  }

  function diff($from_lines, $to_lines) {

    $n_from = sizeof($from_lines);
    $n_to = sizeof($to_lines);

    $this->xchanged = $this->ychanged = array();
    $this->xv = $this->yv = array();
    $this->xind = $this->yind = array();
    unset($this->seq);
    unset($this->in_seq);
    unset($this->lcs);

    // Skip leading common lines.
    for ($skip = 0; $skip < $n_from && $skip < $n_to; $skip++) {
      if ($from_lines[$skip] !== $to_lines[$skip]) {
        break;
      }
      $this->xchanged[$skip] = $this->ychanged[$skip] = FALSE;
    }
    // Skip trailing common lines.
    $xi = $n_from;
    $yi = $n_to;
    for ($endskip = 0; --$xi > $skip && --$yi > $skip; $endskip++) {
      if ($from_lines[$xi] !== $to_lines[$yi]) {
        break;
      }
      $this->xchanged[$xi] = $this->ychanged[$yi] = FALSE;
    }

    // Ignore lines which do not exist in both files.
    for ($xi = $skip; $xi < $n_from - $endskip; $xi++) {
      $xhash[$this->_line_hash($from_lines[$xi])] = 1;
    }

    for ($yi = $skip; $yi < $n_to - $endskip; $yi++) {
      $line = $to_lines[$yi];
      if ($this->ychanged[$yi] = empty($xhash[$this->_line_hash($line)])) {
        continue;
      }
      $yhash[$this->_line_hash($line)] = 1;
      $this->yv[] = $line;
      $this->yind[] = $yi;
    }
    for ($xi = $skip; $xi < $n_from - $endskip; $xi++) {
      $line = $from_lines[$xi];
      if ($this->xchanged[$xi] = empty($yhash[$this->_line_hash($line)])) {
        continue;
      }
      $this->xv[] = $line;
      $this->xind[] = $xi;
    }

    // Find the LCS.
    $this->_compareseq(0, sizeof($this->xv), 0, sizeof($this->yv));

    // Merge edits when possible
    $this->_shift_boundaries($from_lines, $this->xchanged, $this->ychanged);
    $this->_shift_boundaries($to_lines, $this->ychanged, $this->xchanged);

    // Compute the edit operations.
    $edits = array();
    $xi = $yi = 0;
    while ($xi < $n_from || $yi < $n_to) {
      USE_ASSERTS && assert($yi < $n_to || $this->xchanged[$xi]);
      USE_ASSERTS && assert($xi < $n_from || $this->ychanged[$yi]);

      // Skip matching "snake".
      $copy = array();
      while ( $xi < $n_from && $yi < $n_to && !$this->xchanged[$xi] && !$this->ychanged[$yi]) {
        $copy[] = $from_lines[$xi++];
        ++$yi;
      }
      if ($copy) {
        $edits[] = new _DiffOp_Copy($copy);
      }
      // Find deletes & adds.
      $delete = array();
      while ($xi < $n_from && $this->xchanged[$xi]) {
        $delete[] = $from_lines[$xi++];
      }
      $add = array();
      while ($yi < $n_to && $this->ychanged[$yi]) {
        $add[] = $to_lines[$yi++];
      }
      if ($delete && $add) {
        $edits[] = new _DiffOp_Change($delete, $add);
      }
      elseif ($delete) {
        $edits[] = new _DiffOp_Delete($delete);
      }
      elseif ($add) {
        $edits[] = new _DiffOp_Add($add);
      }
    }
    return $edits;
  }

  /**
   * Returns the whole line if it's small enough, or the MD5 hash otherwise.
   */
  function _line_hash($line) {
    if (drupal_strlen($line) > $this->MAX_XREF_LENGTH()) {
      return md5($line);
    }
    else {
      return $line;
    }
  }


  /**
   * Divide the Largest Common Subsequence (LCS) of the sequences
   * [XOFF, XLIM) and [YOFF, YLIM) into NCHUNKS approximately equally
   * sized segments.
   *
   * Returns (LCS, PTS).  LCS is the length of the LCS. PTS is an
   * array of NCHUNKS+1 (X, Y) indexes giving the diving points between
   * sub sequences.  The first sub-sequence is contained in [X0, X1),
   * [Y0, Y1), the second in [X1, X2), [Y1, Y2) and so on.  Note
   * that (X0, Y0) == (XOFF, YOFF) and
   * (X[NCHUNKS], Y[NCHUNKS]) == (XLIM, YLIM).
   *
   * This function assumes that the first lines of the specified portions
   * of the two files do not match, and likewise that the last lines do not
   * match.  The caller must trim matching lines from the beginning and end
   * of the portions it is going to specify.
   */
  function _diag($xoff, $xlim, $yoff, $ylim, $nchunks) {
    $flip = FALSE;

    if ($xlim - $xoff > $ylim - $yoff) {
      // Things seems faster (I'm not sure I understand why)
      // when the shortest sequence in X.
      $flip = TRUE;
      list($xoff, $xlim, $yoff, $ylim) = array($yoff, $ylim, $xoff, $xlim);
    }

    if ($flip) {
      for ($i = $ylim - 1; $i >= $yoff; $i--) {
        $ymatches[$this->xv[$i]][] = $i;
      }
    }
    else {
      for ($i = $ylim - 1; $i >= $yoff; $i--) {
        $ymatches[$this->yv[$i]][] = $i;
      }
    }
    $this->lcs = 0;
    $this->seq[0]= $yoff - 1;
    $this->in_seq = array();
    $ymids[0] = array();

    $numer = $xlim - $xoff + $nchunks - 1;
    $x = $xoff;
    for ($chunk = 0; $chunk < $nchunks; $chunk++) {
      if ($chunk > 0) {
        for ($i = 0; $i <= $this->lcs; $i++) {
          $ymids[$i][$chunk-1] = $this->seq[$i];
        }
      }

      $x1 = $xoff + (int)(($numer + ($xlim-$xoff)*$chunk) / $nchunks);
      for ( ; $x < $x1; $x++) {
        $line = $flip ? $this->yv[$x] : $this->xv[$x];
        if (empty($ymatches[$line])) {
          continue;
        }
        $matches = $ymatches[$line];
        reset($matches);
        while (list ($junk, $y) = each($matches)) {
          if (empty($this->in_seq[$y])) {
            $k = $this->_lcs_pos($y);
            USE_ASSERTS && assert($k > 0);
            $ymids[$k] = $ymids[$k-1];
            break;
          }
        }
        while (list ($junk, $y) = each($matches)) {
          if ($y > $this->seq[$k-1]) {
            USE_ASSERTS && assert($y < $this->seq[$k]);
            // Optimization: this is a common case:
            // next match is just replacing previous match.
            $this->in_seq[$this->seq[$k]] = FALSE;
            $this->seq[$k] = $y;
            $this->in_seq[$y] = 1;
          }
          elseif (empty($this->in_seq[$y])) {
            $k = $this->_lcs_pos($y);
            USE_ASSERTS && assert($k > 0);
            $ymids[$k] = $ymids[$k-1];
          }
        }
      }
    }

    $seps[] = $flip ? array($yoff, $xoff) : array($xoff, $yoff);
    $ymid = $ymids[$this->lcs];
    for ($n = 0; $n < $nchunks - 1; $n++) {
      $x1 = $xoff + (int)(($numer + ($xlim - $xoff) * $n) / $nchunks);
      $y1 = $ymid[$n] + 1;
      $seps[] = $flip ? array($y1, $x1) : array($x1, $y1);
    }
    $seps[] = $flip ? array($ylim, $xlim) : array($xlim, $ylim);

    return array($this->lcs, $seps);
  }

  function _lcs_pos($ypos) {

    $end = $this->lcs;
    if ($end == 0 || $ypos > $this->seq[$end]) {
      $this->seq[++$this->lcs] = $ypos;
      $this->in_seq[$ypos] = 1;
      return $this->lcs;
    }

    $beg = 1;
    while ($beg < $end) {
      $mid = (int)(($beg + $end) / 2);
      if ($ypos > $this->seq[$mid]) {
        $beg = $mid + 1;
      }
      else {
        $end = $mid;
      }
    }

    USE_ASSERTS && assert($ypos != $this->seq[$end]);

    $this->in_seq[$this->seq[$end]] = FALSE;
    $this->seq[$end] = $ypos;
    $this->in_seq[$ypos] = 1;
    return $end;
  }

  /**
   * Find LCS of two sequences.
   *
   * The results are recorded in the vectors $this->{x,y}changed[], by
   * storing a 1 in the element for each line that is an insertion
   * or deletion (ie. is not in the LCS).
   *
   * The subsequence of file 0 is [XOFF, XLIM) and likewise for file 1.
   *
   * Note that XLIM, YLIM are exclusive bounds.
   * All line numbers are origin-0 and discarded lines are not counted.
   */
  function _compareseq($xoff, $xlim, $yoff, $ylim) {

    // Slide down the bottom initial diagonal.
    while ($xoff < $xlim && $yoff < $ylim && $this->xv[$xoff] == $this->yv[$yoff]) {
      ++$xoff;
      ++$yoff;
    }

    // Slide up the top initial diagonal.
    while ($xlim > $xoff && $ylim > $yoff && $this->xv[$xlim - 1] == $this->yv[$ylim - 1]) {
      --$xlim;
      --$ylim;
    }

    if ($xoff == $xlim || $yoff == $ylim) {
      $lcs = 0;
    }
    else {
      // This is ad hoc but seems to work well.
      //$nchunks = sqrt(min($xlim - $xoff, $ylim - $yoff) / 2.5);
      //$nchunks = max(2, min(8, (int)$nchunks));
      $nchunks = min(7, $xlim - $xoff, $ylim - $yoff) + 1;
      list($lcs, $seps)
      = $this->_diag($xoff, $xlim, $yoff, $ylim, $nchunks);
    }

    if ($lcs == 0) {
      // X and Y sequences have no common subsequence:
      // mark all changed.
      while ($yoff < $ylim) {
        $this->ychanged[$this->yind[$yoff++]] = 1;
      }
      while ($xoff < $xlim) {
        $this->xchanged[$this->xind[$xoff++]] = 1;
      }
    }
    else {
      // Use the partitions to split this problem into subproblems.
      reset($seps);
      $pt1 = $seps[0];
      while ($pt2 = next($seps)) {
        $this->_compareseq ($pt1[0], $pt2[0], $pt1[1], $pt2[1]);
        $pt1 = $pt2;
      }
    }
  }

  /**
   * Adjust inserts/deletes of identical lines to join changes
   * as much as possible.
   *
   * We do something when a run of changed lines include a
   * line at one end and has an excluded, identical line at the other.
   * We are free to choose which identical line is included.
   * `compareseq' usually chooses the one at the beginning,
   * but usually it is cleaner to consider the following identical line
   * to be the "change".
   *
   * This is extracted verbatim from analyze.c (GNU diffutils-2.7).
   */
  function _shift_boundaries($lines, &$changed, $other_changed) {
    $i = 0;
    $j = 0;

    USE_ASSERTS && assert('sizeof($lines) == sizeof($changed)');
    $len = sizeof($lines);
    $other_len = sizeof($other_changed);

    while (1) {
      /*
       * Scan forwards to find beginning of another run of changes.
       * Also keep track of the corresponding point in the other file.
       *
       * Throughout this code, $i and $j are adjusted together so that
       * the first $i elements of $changed and the first $j elements
       * of $other_changed both contain the same number of zeros
       * (unchanged lines).
       * Furthermore, $j is always kept so that $j == $other_len or
       * $other_changed[$j] == FALSE.
       */
      while ($j < $other_len && $other_changed[$j]) {
        $j++;
      }
      while ($i < $len && ! $changed[$i]) {
        USE_ASSERTS && assert('$j < $other_len && ! $other_changed[$j]');
        $i++;
        $j++;
        while ($j < $other_len && $other_changed[$j]) {
          $j++;
        }
      }

      if ($i == $len) {
        break;
      }
      $start = $i;

      // Find the end of this run of changes.
      while (++$i < $len && $changed[$i]) {
        continue;
      }

      do {
        /*
         * Record the length of this run of changes, so that
         * we can later determine whether the run has grown.
         */
        $runlength = $i - $start;

        /*
         * Move the changed region back, so long as the
         * previous unchanged line matches the last changed one.
         * This merges with previous changed regions.
         */
        while ($start > 0 && $lines[$start - 1] == $lines[$i - 1]) {
          $changed[--$start] = 1;
          $changed[--$i] = FALSE;
          while ($start > 0 && $changed[$start - 1]) {
            $start--;
          }
          USE_ASSERTS && assert('$j > 0');
          while ($other_changed[--$j]) {
            continue;
          }
          USE_ASSERTS && assert('$j >= 0 && !$other_changed[$j]');
        }

        /*
         * Set CORRESPONDING to the end of the changed run, at the last
         * point where it corresponds to a changed run in the other file.
         * CORRESPONDING == LEN means no such point has been found.
         */
        $corresponding = $j < $other_len ? $i : $len;

        /*
         * Move the changed region forward, so long as the
         * first changed line matches the following unchanged one.
         * This merges with following changed regions.
         * Do this second, so that if there are no merges,
         * the changed region is moved forward as far as possible.
         */
        while ($i < $len && $lines[$start] == $lines[$i]) {
          $changed[$start++] = FALSE;
          $changed[$i++] = 1;
          while ($i < $len && $changed[$i]) {
            $i++;
          }
          USE_ASSERTS && assert('$j < $other_len && ! $other_changed[$j]');
          $j++;
          if ($j < $other_len && $other_changed[$j]) {
            $corresponding = $i;
            while ($j < $other_len && $other_changed[$j]) {
              $j++;
            }
          }
        }
      } while ($runlength != $i - $start);

      /*
       * If possible, move the fully-merged run of changes
       * back to a corresponding run in the other file.
       */
      while ($corresponding < $i) {
        $changed[--$start] = 1;
        $changed[--$i] = 0;
        USE_ASSERTS && assert('$j > 0');
        while ($other_changed[--$j]) {
          continue;
        }
        USE_ASSERTS && assert('$j >= 0 && !$other_changed[$j]');
      }
    }
  }
}

/**
 * Class representing a 'diff' between two sequences of strings.
 * @todo document
 * @private
 * @subpackage DifferenceEngine
 */
class Diff {
  var $edits;

  /**
   * Constructor.
   * Computes diff between sequences of strings.
   *
   * @param $from_lines array An array of strings.
   *      (Typically these are lines from a file.)
   * @param $to_lines array An array of strings.
   */
  function Diff($from_lines, $to_lines) {
    $eng = new _DiffEngine;
    $this->edits = $eng->diff($from_lines, $to_lines);
    //$this->_check($from_lines, $to_lines);
  }

  /**
   * Compute reversed Diff.
   *
   * SYNOPSIS:
   *
   *  $diff = new Diff($lines1, $lines2);
   *  $rev = $diff->reverse();
   * @return object A Diff object representing the inverse of the
   *          original diff.
   */
  function reverse() {
    $rev = $this;
    $rev->edits = array();
    foreach ($this->edits as $edit) {
      $rev->edits[] = $edit->reverse();
    }
    return $rev;
  }

  /**
   * Check for empty diff.
   *
   * @return bool True iff two sequences were identical.
   */
  function isEmpty() {
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
   */
  function lcs() {
    $lcs = 0;
    foreach ($this->edits as $edit) {
      if ($edit->type == 'copy') {
        $lcs += sizeof($edit->orig);
      }
    }
    return $lcs;
  }

  /**
   * Get the original set of lines.
   *
   * This reconstructs the $from_lines parameter passed to the
   * constructor.
   *
   * @return array The original sequence of strings.
   */
  function orig() {
    $lines = array();

    foreach ($this->edits as $edit) {
      if ($edit->orig) {
        array_splice($lines, sizeof($lines), 0, $edit->orig);
      }
    }
    return $lines;
  }

  /**
   * Get the closing set of lines.
   *
   * This reconstructs the $to_lines parameter passed to the
   * constructor.
   *
   * @return array The sequence of strings.
   */
  function closing() {
    $lines = array();

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
   */
  function _check($from_lines, $to_lines) {
    if (serialize($from_lines) != serialize($this->orig())) {
      trigger_error("Reconstructed original doesn't match", E_USER_ERROR);
    }
    if (serialize($to_lines) != serialize($this->closing())) {
      trigger_error("Reconstructed closing doesn't match", E_USER_ERROR);
    }

    $rev = $this->reverse();
    if (serialize($to_lines) != serialize($rev->orig())) {
      trigger_error("Reversed original doesn't match", E_USER_ERROR);
    }
    if (serialize($from_lines) != serialize($rev->closing())) {
      trigger_error("Reversed closing doesn't match", E_USER_ERROR);
    }


    $prevtype = 'none';
    foreach ($this->edits as $edit) {
      if ( $prevtype == $edit->type ) {
        trigger_error("Edit sequence is non-optimal", E_USER_ERROR);
      }
      $prevtype = $edit->type;
    }

    $lcs = $this->lcs();
    trigger_error('Diff okay: LCS = ' . $lcs, E_USER_NOTICE);
  }
}

/**
 * FIXME: bad name.
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
   * case-insensitve diffs, or diffs which ignore
   * changes in white-space.
   *
   * @param $from_lines array An array of strings.
   *  (Typically these are lines from a file.)
   *
   * @param $to_lines array An array of strings.
   *
   * @param $mapped_from_lines array This array should
   *  have the same size number of elements as $from_lines.
   *  The elements in $mapped_from_lines and
   *  $mapped_to_lines are what is actually compared
   *  when computing the diff.
   *
   * @param $mapped_to_lines array This array should
   *  have the same number of elements as $to_lines.
   */
  function MappedDiff($from_lines, $to_lines, $mapped_from_lines, $mapped_to_lines) {

    assert(sizeof($from_lines) == sizeof($mapped_from_lines));
    assert(sizeof($to_lines) == sizeof($mapped_to_lines));

    $this->Diff($mapped_from_lines, $mapped_to_lines);

    $xi = $yi = 0;
    for ($i = 0; $i < sizeof($this->edits); $i++) {
      $orig = &$this->edits[$i]->orig;
      if (is_array($orig)) {
        $orig = array_slice($from_lines, $xi, sizeof($orig));
        $xi += sizeof($orig);
      }

      $closing = &$this->edits[$i]->closing;
      if (is_array($closing)) {
        $closing = array_slice($to_lines, $yi, sizeof($closing));
        $yi += sizeof($closing);
      }
    }
  }
}

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
  var $show_header = TRUE;

  /**
   * Number of leading context "lines" to preserve.
   *
   * This should be left at zero for this class, but subclasses
   * may want to set this to other values.
   */
  var $leading_context_lines = 0;

  /**
   * Number of trailing context "lines" to preserve.
   *
   * This should be left at zero for this class, but subclasses
   * may want to set this to other values.
   */
  var $trailing_context_lines = 0;

  /**
   * Format a diff.
   *
   * @param $diff object A Diff object.
   * @return string The formatted output.
   */
  function format($diff) {
    $xi = $yi = 1;
    $block = FALSE;
    $context = array();

    $nlead = $this->leading_context_lines;
    $ntrail = $this->trailing_context_lines;

    $this->_start_diff();

    foreach ($diff->edits as $edit) {
      if ($edit->type == 'copy') {
        if (is_array($block)) {
          if (sizeof($edit->orig) <= $nlead + $ntrail) {
            $block[] = $edit;
          }
          else {
            if ($ntrail) {
              $context = array_slice($edit->orig, 0, $ntrail);
              $block[] = new _DiffOp_Copy($context);
            }
            $this->_block($x0, $ntrail + $xi - $x0, $y0, $ntrail + $yi - $y0, $block);
            $block = FALSE;
          }
        }
        $context = $edit->orig;
      }
      else {
        if (! is_array($block)) {
          $context = array_slice($context, sizeof($context) - $nlead);
          $x0 = $xi - sizeof($context);
          $y0 = $yi - sizeof($context);
          $block = array();
          if ($context) {
            $block[] = new _DiffOp_Copy($context);
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

  function _block($xbeg, $xlen, $ybeg, $ylen, &$edits) {
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

  function _start_diff() {
    ob_start();
  }

  function _end_diff() {
    $val = ob_get_contents();
    ob_end_clean();
    return $val;
  }

  function _block_header($xbeg, $xlen, $ybeg, $ylen) {
    if ($xlen > 1) {
      $xbeg .= "," . ($xbeg + $xlen - 1);
    }
    if ($ylen > 1) {
      $ybeg .= "," . ($ybeg + $ylen - 1);
    }

    return $xbeg . ($xlen ? ($ylen ? 'c' : 'd') : 'a') . $ybeg;
  }

  function _start_block($header) {
    if ($this->show_header) {
      echo $header . "\n";
    }
  }

  function _end_block() {
  }

  function _lines($lines, $prefix = ' ') {
    foreach ($lines as $line) {
      echo "$prefix $line\n";
    }
  }

  function _context($lines) {
    $this->_lines($lines);
  }

  function _added($lines) {
    $this->_lines($lines, '>');
  }
  function _deleted($lines) {
    $this->_lines($lines, '<');
  }

  function _changed($orig, $closing) {
    $this->_deleted($orig);
    echo "---\n";
    $this->_added($closing);
  }
}


/**
 *  Additions by Axel Boldt follow, partly taken from diff.php, phpwiki-1.3.3
 *
 */

define('NBSP', '&#160;');      // iso-8859-x non-breaking space.

/**
 * @todo document
 * @private
 * @subpackage DifferenceEngine
 */
class _HWLDF_WordAccumulator {
  function _HWLDF_WordAccumulator() {
    $this->_lines = array();
    $this->_line = '';
    $this->_group = '';
    $this->_tag = '';
  }

  function _flushGroup($new_tag) {
    if ($this->_group !== '') {
      if ($this->_tag == 'mark') {
        $this->_line .= '<span class="diffchange">' . check_plain($this->_group) . '</span>';
      }
      else {
        $this->_line .= check_plain($this->_group);
      }
    }
    $this->_group = '';
    $this->_tag = $new_tag;
  }

  function _flushLine($new_tag) {
    $this->_flushGroup($new_tag);
    if ($this->_line != '') {
      array_push($this->_lines, $this->_line);
    }
    else {
      // make empty lines visible by inserting an NBSP
      array_push($this->_lines, NBSP);
    }
    $this->_line = '';
  }

  function addWords($words, $tag = '') {
    if ($tag != $this->_tag) {
      $this->_flushGroup($tag);
    }
    foreach ($words as $word) {
      // new-line should only come as first char of word.
      if ($word == '') {
        continue;
      }
      if ($word[0] == "\n") {
        $this->_flushLine($tag);
        $word = drupal_substr($word, 1);
      }
      assert(!strstr($word, "\n"));
      $this->_group .= $word;
    }
  }

  function getLines() {
    $this->_flushLine('~done');
    return $this->_lines;
  }
}

/**
 * @todo document
 * @private
 * @subpackage DifferenceEngine
 */
class WordLevelDiff extends MappedDiff {
  function MAX_LINE_LENGTH() {
    return 10000;
  }

  function WordLevelDiff($orig_lines, $closing_lines) {
    list($orig_words, $orig_stripped) = $this->_split($orig_lines);
    list($closing_words, $closing_stripped) = $this->_split($closing_lines);

    $this->MappedDiff($orig_words, $closing_words, $orig_stripped, $closing_stripped);
  }

  function _split($lines) {
    $words = array();
    $stripped = array();
    $first = TRUE;
    foreach ($lines as $line) {
      // If the line is too long, just pretend the entire line is one big word
      // This prevents resource exhaustion problems
      if ( $first ) {
        $first = FALSE;
      }
      else {
        $words[] = "\n";
        $stripped[] = "\n";
      }
      if ( drupal_strlen( $line ) > $this->MAX_LINE_LENGTH() ) {
        $words[] = $line;
        $stripped[] = $line;
      }
      else {
        if (preg_match_all('/ ( [^\S\n]+ | [0-9_A-Za-z\x80-\xff]+ | . ) (?: (?!< \n) [^\S\n])? /xs', $line, $m)) {
          $words = array_merge($words, $m[0]);
          $stripped = array_merge($stripped, $m[1]);
        }
      }
    }
    return array($words, $stripped);
  }

  function orig() {
    $orig = new _HWLDF_WordAccumulator;

    foreach ($this->edits as $edit) {
      if ($edit->type == 'copy') {
        $orig->addWords($edit->orig);
      }
      elseif ($edit->orig) {
        $orig->addWords($edit->orig, 'mark');
      }
    }
    $lines = $orig->getLines();
    return $lines;
  }

  function closing() {
    $closing = new _HWLDF_WordAccumulator;

    foreach ($this->edits as $edit) {
      if ($edit->type == 'copy') {
        $closing->addWords($edit->closing);
      }
      elseif ($edit->closing) {
        $closing->addWords($edit->closing, 'mark');
      }
    }
    $lines = $closing->getLines();
    return $lines;
  }
}

/**
 * Diff formatter which uses Drupal theme functions.
 * @private
 * @subpackage DifferenceEngine
 */
class DrupalDiffFormatter extends DiffFormatter {

  var $rows;
  var $line_stats = array(
    'counter' => array('x' => 0, 'y' => 0),
    'offset' => array('x' => 0, 'y' => 0),
  );

  function DrupalDiffFormatter() {
    $this->leading_context_lines = variable_get('diff_context_lines_leading', 2);
    $this->trailing_context_lines = variable_get('diff_context_lines_trailing', 2);
  }

  function _start_diff() {
    $this->rows = array();
  }

  function _end_diff() {
    return $this->rows;
  }

  function _block_header($xbeg, $xlen, $ybeg, $ylen) {
    return array(
      array(
        'data' => theme('diff_header_line', array('lineno' => $xbeg + $this->line_stats['offset']['x'])),
        'colspan' => 2,
      ),
      array(
        'data' => theme('diff_header_line', array('lineno' => $ybeg + $this->line_stats['offset']['y'])),
        'colspan' => 2,
      )
    );
  }

  function _start_block($header) {
    if ($this->show_header) {
      $this->rows[] = $header;
    }
  }

  function _end_block() {
  }

  function _lines($lines, $prefix=' ', $color='white') {
  }

  /**
   * Note: you should HTML-escape parameter before calling this.
   */
  function addedLine($line) {
    return array(
      array(
        'data' => '+',
        'class' => 'diff-marker',
      ),
      array(
        'data' => theme('diff_content_line', array('line' => $line)),
        'class' => 'diff-context diff-addedline',
      )
    );
  }

  /**
   * Note: you should HTML-escape parameter before calling this.
   */
  function deletedLine($line) {
    return array(
      array(
        'data' => '-',
        'class' => 'diff-marker',
      ),
      array(
        'data' => theme('diff_content_line', array('line' => $line)),
        'class' => 'diff-context diff-deletedline',
      )
    );
  }

  /**
   * Note: you should HTML-escape parameter before calling this.
   */
  function contextLine($line) {
    return array(
      '&nbsp;',
      array(
        'data' => theme('diff_content_line', array('line' => $line)),
        'class' => 'diff-context',
      )
    );
  }

  function emptyLine() {
    return array(
      '&nbsp;',
      theme('diff_empty_line', array('line' => '&nbsp;')),
    );
  }

  function _added($lines) {
    foreach ($lines as $line) {
      $this->rows[] = array_merge($this->emptyLine(), $this->addedLine(check_plain($line)));
    }
  }

  function _deleted($lines) {
    foreach ($lines as $line) {
      $this->rows[] = array_merge($this->deletedLine(check_plain($line)), $this->emptyLine());
    }
  }

  function _context($lines) {
    foreach ($lines as $line) {
      $this->rows[] = array_merge($this->contextLine(check_plain($line)), $this->contextLine(check_plain($line)));
    }
  }

  function _changed($orig, $closing) {
    $diff = new WordLevelDiff($orig, $closing);
    $del = $diff->orig();
    $add = $diff->closing();

    // Notice that WordLevelDiff returns HTML-escaped output.
    // Hence, we will be calling addedLine/deletedLine without HTML-escaping.

    while ($line = array_shift($del)) {
      $aline = array_shift( $add );
      $this->rows[] = array_merge($this->deletedLine($line), isset($aline) ? $this->addedLine($aline) : $this->emptyLine());
    }
    foreach ($add as $line) {  // If any leftovers
      $this->rows[] = array_merge($this->emptyLine(), $this->addedLine($line));
    }
  }
}

/**
 * Drupal inline Diff formatter.
 * @private
 * @subpackage DifferenceEngine
 */
class DrupalDiffInline {
  var $a;
  var $b;

  /**
   * Constructor.
   */
  function __construct($a, $b) {
    $this->a = $a;
    $this->b = $b;
  }

  /**
   * Render differences inline using HTML markup.
   */
  function render() {
    $a = preg_split('/(<[^>]+?>| )/', $this->a, -1, PREG_SPLIT_DELIM_CAPTURE);
    $b = preg_split('/(<[^>]+?>| )/', $this->b, -1, PREG_SPLIT_DELIM_CAPTURE);
    $diff = new Diff($a, $b);
    $diff->edits = $this->process_edits($diff->edits);

    // Assemble highlighted output
    $output = '';
    foreach ($diff->edits as $chunk) {
      switch ($chunk->type) {
        case 'copy':
          $output .= implode('', $chunk->closing);
          break;
        case 'delete':
          foreach ($chunk->orig as $i => $piece) {
            if (strpos($piece, '<') === 0 && drupal_substr($piece, drupal_strlen($piece) - 1) === '>') {
              $output .= $piece;
            }
            else {
              $output .= theme('diff_inline_chunk', array('text' => $piece, 'type' => $chunk->type));
            }
          }
          break;
        default:
          $chunk->closing = $this->process_chunk($chunk->closing);
          foreach ($chunk->closing as $i => $piece) {
            if ($piece === ' ' || (strpos($piece, '<') === 0 && drupal_substr($piece, drupal_strlen($piece) - 1) === '>' && drupal_strtolower(drupal_substr($piece, 1, 3)) != 'img')) {
              $output .= $piece;
            }
            else {
              $output .= theme('diff_inline_chunk', array('text' => $piece, 'type' => $chunk->type));
            }
          }
          break;
      }
    }
    return $output;
  }

  /**
   * Merge chunk segments between tag delimiters.
   */
  function process_chunk($chunk) {
    $processed = array();
    $j = 0;
    foreach ($chunk as $i => $piece) {
      $next = isset($chunk[$i+1]) ? $chunk[$i+1] : NULL;
      if (!isset($processed[$j])) {
        $processed[$j] = '';
      }
      if (strpos($piece, '<') === 0 && drupal_substr($piece, drupal_strlen($piece) - 1) === '>') {
        $processed[$j] = $piece;
        $j++;
      }
      elseif (isset($next) && strpos($next, '<') === 0 && drupal_substr($next, drupal_strlen($next) - 1) === '>') {
        $processed[$j] .= $piece;
        $j++;
      }
      else {
        $processed[$j] .= $piece;
      }
    }
    return $processed;
  }

  /**
   * Merge copy and equivalent edits into intelligible chunks.
   */
  function process_edits($edits) {
    $processed = array();
    $current = array_shift($edits);

    // Make two passes -- first merge space delimiter copies back into their originals.
    while ($chunk = array_shift($edits)) {
      if ($chunk->type == 'copy' && $chunk->orig === array(' ')) {
        $current->orig = array_merge((array) $current->orig, (array) $chunk->orig);
        $current->closing = array_merge((array) $current->closing, (array) $chunk->closing);
      }
      else {
        $processed[] = $current;
        $current = $chunk;
      }
    }
    $processed[] = $current;

    // Initial setup
    $edits = $processed;
    $processed = array();
    $current = array_shift($edits);

    // Second, merge equivalent chunks into each other.
    while ($chunk = array_shift($edits)) {
      if ($current->type == $chunk->type) {
        $current->orig = array_merge((array) $current->orig, (array) $chunk->orig);
        $current->closing = array_merge((array) $current->closing, (array) $chunk->closing);
      }
      else {
        $processed[] = $current;
        $current = $chunk;
      }
    }
    $processed[] = $current;

    return $processed;
  }
}

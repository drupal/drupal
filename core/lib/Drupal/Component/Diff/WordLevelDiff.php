<?php

namespace Drupal\Component\Diff;

use Drupal\Component\Diff\Engine\HWLDFWordAccumulator;
use Drupal\Component\Utility\Unicode;

/**
 * @todo document
 * @private
 * @subpackage DifferenceEngine
 */
class WordLevelDiff extends MappedDiff {

  const MAX_LINE_LENGTH = 10000;

  public function __construct($orig_lines, $closing_lines) {
    list($orig_words, $orig_stripped) = $this->_split($orig_lines);
    list($closing_words, $closing_stripped) = $this->_split($closing_lines);

    parent::__construct($orig_words, $closing_words, $orig_stripped, $closing_stripped);
  }

  protected function _split($lines) {
    $words = [];
    $stripped = [];
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
      if (Unicode::strlen($line) > $this::MAX_LINE_LENGTH) {
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
    return [$words, $stripped];
  }

  public function orig() {
    $orig = new HWLDFWordAccumulator();

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

  public function closing() {
    $closing = new HWLDFWordAccumulator();

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

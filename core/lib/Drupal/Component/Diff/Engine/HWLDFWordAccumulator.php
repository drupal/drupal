<?php

namespace Drupal\Component\Diff\Engine;

/**
 * Additions by Axel Boldt follow, partly taken from diff.php, phpwiki-1.3.3
 */

/**
 * @todo document
 * @private
 * @subpackage DifferenceEngine
 */
class HWLDFWordAccumulator {

  /**
   * An iso-8859-x non-breaking space.
   */
  const NBSP = '&#160;';

  protected $lines = [];

  protected $line = '';

  protected $group = '';

  protected $tag = '';

  protected function _flushGroup($new_tag) {
    if ($this->group !== '') {
      if ($this->tag == 'mark') {
        $this->line = $this->line . '<span class="diffchange">' . $this->group . '</span>';
      }
      else {
        $this->line = $this->line . $this->group;
      }
    }
    $this->group = '';
    $this->tag = $new_tag;
  }

  protected function _flushLine($new_tag) {
    $this->_flushGroup($new_tag);
    if ($this->line != '') {
      array_push($this->lines, $this->line);
    }
    else {
      // make empty lines visible by inserting an NBSP
      array_push($this->lines, $this::NBSP);
    }
    $this->line = '';
  }

  public function addWords($words, $tag = '') {
    if ($tag != $this->tag) {
      $this->_flushGroup($tag);
    }
    foreach ($words as $word) {
      // new-line should only come as first char of word.
      if ($word == '') {
        continue;
      }
      if ($word[0] == "\n") {
        $this->_flushLine($tag);
        $word = mb_substr($word, 1);
      }
      assert(!str_contains($word, "\n"));
      $this->group .= $word;
    }
  }

  public function getLines() {
    $this->_flushLine('~done');
    return $this->lines;
  }

}

<?php

/**
 * @file
 * Contains \Drupal\Core\Diff\DiffFormatter.
 */

namespace Drupal\Core\Diff;

use Drupal\Component\Diff\DiffFormatter as DiffFormatterBase;
use Drupal\Component\Diff\WordLevelDiff;
use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Diff formatter which uses returns output that can be rendered to a table.
 */
class DiffFormatter extends DiffFormatterBase {

  /**
   * The diff represented as an array of rows.
   *
   * @var array
   */
  protected $rows = array();

  /**
   * The line stats.
   *
   * @var array
   */
  protected $line_stats = array(
    'counter' => array('x' => 0, 'y' => 0),
    'offset' => array('x' => 0, 'y' => 0),
  );

  /**
   * Creates a DiffFormatter to render diffs in a table.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $config = $config_factory->get('system.diff');
    $this->leading_context_lines = $config->get('context.lines_leading');
    $this->trailing_context_lines = $config->get('context.lines_trailing');
  }

  /**
   * {@inheritdoc}
   */
  protected function _start_diff() {
    $this->rows = array();
  }

  /**
   * {@inheritdoc}
   */
  protected function _end_diff() {
    return $this->rows;
  }

  /**
   * {@inheritdoc}
   */
  protected function _block_header($xbeg, $xlen, $ybeg, $ylen) {
    return array(
      array(
        'data' => $xbeg + $this->line_stats['offset']['x'],
        'colspan' => 2,
      ),
      array(
        'data' => $ybeg + $this->line_stats['offset']['y'],
        'colspan' => 2,
      )
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function _start_block($header) {
    if ($this->show_header) {
      $this->rows[] = $header;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function _lines($lines, $prefix=' ', $color='white') {
  }

  /**
   * Creates an added line.
   *
   * @param string $line
   *   An HTML-escaped line.
   *
   * @return array
   *   An array representing a table row.
   */
  protected function addedLine($line) {
    return array(
      array(
        'data' => '+',
        'class' => 'diff-marker',
      ),
      array(
        'data' => ['#markup' => $line],
        'class' => 'diff-context diff-addedline',
      )
    );
  }

  /**
   * Creates a deleted line.
   *
   * @param string $line
   *   An HTML-escaped line.
   *
   * @return array
   *   An array representing a table row.
   */
  protected function deletedLine($line) {
    return array(
      array(
        'data' => '-',
        'class' => 'diff-marker',
      ),
      array(
        'data' => ['#markup' => $line],
        'class' => 'diff-context diff-deletedline',
      )
    );
  }

  /**
   * Creates a context line.
   *
   * @param string $line
   *   An HTML-escaped line.
   *
   * @return array
   *   An array representing a table row.
   */
  protected function contextLine($line) {
    return array(
      ' ',
      array(
        'data' => ['#markup' => $line],
        'class' => 'diff-context',
      )
    );
  }

  /**
   * Creates an empty line.
   *
   * @return array
   *   An array representing a table row.
   */
  protected function emptyLine() {
    return array(
      ' ',
      ' ',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function _added($lines) {
    foreach ($lines as $line) {
      $this->rows[] = array_merge($this->emptyLine(), $this->addedLine(Html::escape($line)));
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function _deleted($lines) {
    foreach ($lines as $line) {
      $this->rows[] = array_merge($this->deletedLine(Html::escape($line)), $this->emptyLine());
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function _context($lines) {
    foreach ($lines as $line) {
      $this->rows[] = array_merge($this->contextLine(Html::escape($line)), $this->contextLine(Html::escape($line)));
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function _changed($orig, $closing) {
    $orig = array_map('\Drupal\Component\Utility\Html::escape', $orig);
    $closing = array_map('\Drupal\Component\Utility\Html::escape', $closing);
    $diff = new WordLevelDiff($orig, $closing);
    $del = $diff->orig();
    $add = $diff->closing();

    // Notice that WordLevelDiff returns HTML-escaped output. Hence, we will be
    // calling addedLine/deletedLine without HTML-escaping.
    while ($line = array_shift($del)) {
      $aline = array_shift( $add );
      $this->rows[] = array_merge($this->deletedLine($line), isset($aline) ? $this->addedLine($aline) : $this->emptyLine());
    }

    // If any leftovers.
    foreach ($add as $line) {
      $this->rows[] = array_merge($this->emptyLine(), $this->addedLine($line));
    }
  }
}

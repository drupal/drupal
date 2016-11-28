<?php

namespace Drupal\Tests\Listeners;

use Drupal\Component\Utility\Html;

/**
 * Defines a class for providing html output links in the Simpletest UI.
 */
class SimpletestUiPrinter extends HtmlOutputPrinter {

  /**
   * {@inheritdoc}
   */
  public function write($buffer) {
    $buffer = Html::escape($buffer);
    // Turn HTML output URLs into clickable link <a> tags.
    $url_pattern = '@https?://[^\s]+@';
    $buffer = preg_replace($url_pattern, '<a href="$0" target="_blank" title="$0">$0</a>', $buffer);
    // Make the output readable in HTML by breaking up lines properly.
    $buffer = nl2br($buffer);

    print $buffer;
  }

}

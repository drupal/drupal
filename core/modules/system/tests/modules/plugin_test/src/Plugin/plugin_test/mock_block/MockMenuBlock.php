<?php

declare(strict_types=1);

namespace Drupal\plugin_test\Plugin\plugin_test\mock_block;

/**
 * Mock implementation of a menu block plugin used by Plugin API unit tests.
 *
 * @see \Drupal\plugin_test\Plugin\MockBlockManager
 */
class MockMenuBlock {

  /**
   * The title to display when rendering this block instance.
   *
   * @var string
   */
  protected $title;

  /**
   * The number of menu levels deep to render.
   *
   * @var int
   */
  protected $depth;

  public function __construct($title = '', $depth = 0) {
    $this->title = $title;
    $this->depth = $depth;
  }

  /**
   * Returns the content to display.
   */
  public function getContent() {
    // Since this is a mock object, we just return some HTML of the desired
    // nesting level. For depth=2, this returns:
    // '<ul><li>1<ul><li>1.1</li></ul></li></ul>'.
    $content = '';
    for ($i = 0; $i < $this->depth; $i++) {
      $content .= '<ul><li>' . implode('.', array_fill(0, $i + 1, '1'));
    }
    for ($i = 0; $i < $this->depth; $i++) {
      $content .= '</li></ul>';
    }
    return $content;
  }

}

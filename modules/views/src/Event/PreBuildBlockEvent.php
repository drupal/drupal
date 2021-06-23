<?php

namespace Drupal\views\Event;

use Drupal\Component\EventDispatcher\Event;

/**
 * Event fired during \Drupal\views\Plugin\views\display\Block::preBuildBlock().
 *
 * Subscribers to this event can prepare
 *
 * @package Drupal\views\Event
 */
class PreBuildBlockEvent extends Event {

  /**
   * The views block instance.
   *
   * @var \Drupal\views\Plugin\Block\ViewsBlock
   */
  protected $block;

  /**
   * The views block display handler.
   *
   * @var \Drupal\views\Plugin\views\display\Block
   */
  protected $display;

  /**
   * Constructs a new PreBuildBlockEvent.
   *
   * @param $block
   * @param $display
   */
  public function __construct($block, $display) {
    $this->block = $block;
    $this->display = $display;
  }

  /**
   * Gets the view block.
   *
   * @return \Drupal\views\Plugin\Block\ViewsBlock
   */
  public function getBlock() {
    return $this->block;
  }

  /**
   * Gets the views display.
   *
   * @return \Drupal\views\Plugin\views\display\Block
   */
  public function getDisplay() {
    return $this->display;
  }
}

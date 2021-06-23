<?php

namespace Drupal\views;

final class ViewsEvents {

  /**
   * Name of the event fired when a views block display preBuildBlock happens.
   *
   * @Event
   *
   * @var string
   */
  const DISPLAY_BLOCK_PRE_BUILD_BLOCK = 'display.block.pre_build_block';
}

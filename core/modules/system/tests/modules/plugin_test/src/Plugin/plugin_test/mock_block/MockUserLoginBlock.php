<?php

declare(strict_types=1);

namespace Drupal\plugin_test\Plugin\plugin_test\mock_block;

use Drupal\Component\Plugin\PluginBase;

/**
 * Mock implementation of a login block plugin used by Plugin API unit tests.
 *
 * @see \Drupal\plugin_test\Plugin\MockBlockManager
 */
class MockUserLoginBlock extends PluginBase {

  /**
   * The title to display when rendering this block instance.
   *
   * @var string
   */
  protected $title;

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->title = $configuration['title'] ?? '';
  }

  /**
   * Returns the title of the block.
   */
  public function getTitle() {
    return $this->title;
  }

}

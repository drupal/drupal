<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Sets up page and article content types.
 */
abstract class NodeTestBase extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'datetime'];

  /**
   * The node access control handler.
   *
   * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  protected $accessHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create Basic page and Article node types.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType([
        'type' => 'page',
        'name' => 'Basic page',
        'display_submitted' => FALSE,
      ]);
      $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    }
    $this->accessHandler = \Drupal::entityTypeManager()->getAccessControlHandler('node');
  }

}

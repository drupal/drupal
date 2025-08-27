<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Unit\Plugin\area;

use Drupal\Tests\UnitTestCase;
use Drupal\views\Plugin\views\area\Messages;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Drupal\views\Plugin\views\area\Messages.
 */
#[CoversClass(Messages::class)]
#[Group('views')]
class MessagesTest extends UnitTestCase {

  /**
   * The view executable object.
   *
   * @var \Drupal\views\ViewExecutable
   */
  protected $view;

  /**
   * The message handler.
   *
   * @var \Drupal\views\Plugin\views\area\Messages
   */
  protected $messagesHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->messagesHandler = new Messages([], 'result', []);
  }

  /**
   * Tests the render method.
   *
   * @legacy-covers ::defineOptions
   * @legacy-covers ::render
   */
  public function testRender(): void {
    // The handler is configured to show with empty views by default, so should
    // appear.
    $this->assertSame(['#type' => 'status_messages'], $this->messagesHandler->render());

    // Turn empty off, and make sure it isn't rendered.
    $this->messagesHandler->options['empty'] = FALSE;
    // $empty parameter passed to render will still be FALSE, so should still
    // appear.
    $this->assertSame(['#type' => 'status_messages'], $this->messagesHandler->render());
    // Should now be empty as both the empty option and parameter are empty.
    $this->assertSame([], $this->messagesHandler->render(TRUE));
  }

}

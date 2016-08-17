<?php

namespace Drupal\Tests\outside_in\Unit\Ajax;

use Drupal\outside_in\Ajax\OpenOffCanvasDialogCommand;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\outside_in\Ajax\OpenOffCanvasDialogCommand
 * @group outside_in
 */
class OpenOffCanvasDialogCommandTest extends UnitTestCase {

  /**
   * @covers ::render
   */
  public function testRender() {
    $command = new OpenOffCanvasDialogCommand('Title', '<p>Text!</p>', ['url' => 'example']);

    $expected = [
      'command' => 'openOffCanvas',
      'selector' => '#drupal-offcanvas',
      'settings' => NULL,
      'data' => '<p>Text!</p>',
      'dialogOptions' => [
        'url' => 'example',
        'title' => 'Title',
        'modal' => FALSE,
      ],
    ];
    $this->assertEquals($expected, $command->render());
  }

}

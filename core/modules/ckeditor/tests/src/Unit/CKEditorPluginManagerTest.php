<?php

namespace Drupal\Tests\ckeditor\Unit;

use Drupal\ckeditor\CKEditorPluginManager;
use Drupal\editor\Entity\Editor;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\ckeditor\CKEditorPluginManager
 *
 * @group ckeditor
 */
class CKEditorPluginManagerTest extends UnitTestCase {

  /**
   * Provides a list of configs to test.
   */
  public function providerGetEnabledButtons() {
    return [
      'empty' => [
        [],
        []
      ],
      '1 row, 1 group' => [
        [
          // Row 1.
          [
            // Group 1.
            ['name' => 'Formatting', 'items' => ['Bold', 'Italic']],
          ]
        ],
        ['Bold', 'Italic']
      ],
      '1 row, >1 groups' => [
        [
          // Row 1.
          [
            // Group 1.
            ['name' => 'Formatting', 'items' => ['Bold', 'Italic']],
            // Group 2.
            ['name' => 'Linking', 'items' => ['Link']],
          ],
        ],
        ['Bold', 'Italic', 'Link']
      ],
      '2 rows, 1 group each' => [
        [
          // Row 1.
          [
            // Group 1.
            ['name' => 'Formatting', 'items' => ['Bold', 'Italic']],
          ],
          // Row 2.
          [
            // Group 1.
            ['name' => 'Tools', 'items' => ['Source']],
          ],
        ],
        ['Bold', 'Italic', 'Source'],
      ],
      '2 rows, >1 groups each' => [
        [
          // Row 1.
          [
            // Group 1.
            ['name' => 'Formatting', 'items' => ['Bold', 'Italic']],
            // Group 2.
            ['name' => 'Linking', 'items' => ['Link']],
        ],
          // Row 2.
          [
            // Group 1.
            ['name' => 'Tools', 'items' => ['Source']],
            // Group 2.
            ['name' => 'Advanced', 'items' => ['Llama']],
          ],
        ],
        ['Bold', 'Italic', 'Link', 'Source', 'Llama']
      ],
    ];
  }

  /**
   * @covers ::getEnabledButtons
   * @dataProvider providerGetEnabledButtons
   */
  public function testGetEnabledButtons(array $toolbar_rows, array $expected_buttons) {
    $editor= $this->prophesize(Editor::class);
    $editor->getSettings()
      ->willReturn(['toolbar' => ['rows' => $toolbar_rows]]);

    $enabled_buttons = CKEditorPluginManager::getEnabledButtons($editor->reveal());
    $this->assertEquals($expected_buttons, $enabled_buttons);
  }

}

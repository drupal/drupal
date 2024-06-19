<?php

declare(strict_types=1);

namespace Drupal\Tests\editor\Kernel;

use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests integration with filter module.
 *
 * @group editor
 */
class EditorFilterIntegrationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['filter', 'editor', 'editor_test'];

  /**
   * Tests text format removal or disabling.
   */
  public function testTextFormatIntegration(): void {
    // Create an arbitrary text format.
    $format = FilterFormat::create([
      'format' => $this->randomMachineName(),
      'name' => $this->randomString(),
    ]);
    $format->save();

    // Create a paired editor.
    Editor::create([
      'format' => $format->id(),
      'editor' => 'unicorn',
      'image_upload' => [
        'status' => FALSE,
      ],
    ])->save();

    // Disable the text format.
    $format->disable()->save();

    // The paired editor should be disabled too.
    $this->assertFalse(Editor::load($format->id())->status());

    // Re-enable the text format.
    $format->enable()->save();

    // The paired editor should be enabled too.
    $this->assertTrue(Editor::load($format->id())->status());

    // Completely remove the text format. Usually this cannot occur via UI, but
    // can be triggered from API.
    $format->delete();

    // The paired editor should be removed.
    $this->assertNull(Editor::load($format->id()));
  }

}

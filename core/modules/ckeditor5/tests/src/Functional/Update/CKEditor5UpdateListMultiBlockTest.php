<?php

declare(strict_types=1);

namespace Drupal\Tests\ckeditor5\Functional\Update;

use Drupal\editor\Entity\Editor;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

// cspell:ignore multiblock

/**
 * @covers ckeditor5_post_update_list_multiblock
 * @group Update
 * @group ckeditor5
 */
class CKEditor5UpdateListMultiBlockTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-9.4.0.filled.standard.php.gz',
    ];
  }

  /**
   * Tests that sites get the new `multiBlock` setting added.
   */
  public function test(): void {
    $before = Editor::loadMultiple();
    $this->assertSame([
      'basic_html',
      'full_html',
      'test_text_format',
    ], array_keys($before));

    // Basic HTML before: settings exist for `ckeditor5_list`.
    $settings = $before['basic_html']->getSettings();
    $this->assertSame(['reversed', 'startIndex'], array_keys($settings['plugins']['ckeditor5_list']));

    // test_text_format before: not using the List plugin.
    $settings = $before['test_text_format']->getSettings();
    $this->assertArrayNotHasKey('ckeditor5_list', $settings['plugins']);

    $this->runUpdates();

    $after = Editor::loadMultiple();

    // Basic HTML after: existing settings moved under a new "properties" key,
    // and the new "multiBlock" key is set to TRUE.
    $this->assertNotSame($before['basic_html']->getSettings(), $after['basic_html']->getSettings());
    $settings = $after['basic_html']->getSettings();
    $this->assertSame(['properties', 'multiBlock'], array_keys($settings['plugins']['ckeditor5_list']));
    $this->assertSame($before['basic_html']->getSettings()['plugins']['ckeditor5_list'], $settings['plugins']['ckeditor5_list']['properties']);
    $this->assertTrue($settings['plugins']['ckeditor5_list']['multiBlock']);

    // test_text_format after: no changes.
    $this->assertSame($before['test_text_format']->getSettings(), $after['test_text_format']->getSettings());
  }

}

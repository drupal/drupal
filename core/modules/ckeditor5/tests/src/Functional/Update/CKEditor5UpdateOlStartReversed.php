<?php

declare(strict_types=1);

namespace Drupal\Tests\ckeditor5\Functional\Update;

use Drupal\ckeditor5\HTMLRestrictions;
use Drupal\editor\Entity\Editor;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * @covers ckeditor5_post_update_list_start_reversed
 * @group Update
 * @group ckeditor5
 */
class CKEditor5UpdateOlStartReversed extends UpdatePathTestBase {

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
      __DIR__ . '/../../../fixtures/update/ckeditor5-3396628.php',
    ];
  }

  /**
   * Test that sites with <ol start> or <ol reversed> opt in to the expanded UI.
   */
  public function testUpdate(): void {
    $before = Editor::loadMultiple();
    $this->assertSame([
      'basic_html',
      'full_html',
      'test_format_list_ol_start',
      'test_format_list_ol_start_post_3261599',
      'test_text_format',
    ], array_keys($before));

    // Basic HTML before: only <ol type> editable via Source Editing … but just
    // like a real site, this update path was added too late, and many sites
    // have in the meantime edited their text editor configuration through the
    // UI, in which case they may already have set it. That is also the case for
    // the test fixture used by update path tests.
    $settings = $before['basic_html']->getSettings();
    $this->assertArrayHasKey('ckeditor5_list', $settings['plugins']);
    $this->assertSame(['reversed' => FALSE, 'startIndex' => TRUE], $settings['plugins']['ckeditor5_list']);
    $source_editable = HTMLRestrictions::fromString(implode(' ', $settings['plugins']['ckeditor5_sourceEditing']['allowed_tags']));
    $this->assertSame(['type' => TRUE], $source_editable->getAllowedElements()['ol']);

    // Full HTML before: nothing listed for Source Editing.
    $settings = $before['full_html']->getSettings();
    $this->assertArrayHasKey('ckeditor5_list', $settings['plugins']);
    $this->assertSame([], $settings['plugins']['ckeditor5_sourceEditing']['allowed_tags']);

    // test_format_list_ol_start before: <ol start foo> using Source Editing.
    $settings = $before['test_format_list_ol_start']->getSettings();
    $this->assertArrayNotHasKey('ckeditor5_list', $settings['plugins']);
    $this->assertSame(['<ol start foo>'], $settings['plugins']['ckeditor5_sourceEditing']['allowed_tags']);

    // test_format_list_ol_start_post_3261599 before: <ol foo> for Source
    // Editing.
    $settings = $before['test_format_list_ol_start_post_3261599']->getSettings();
    $this->assertSame(['reversed' => FALSE, 'startIndex' => TRUE], $settings['plugins']['ckeditor5_list']['properties']);
    $this->assertSame(['<ol foo>'], $settings['plugins']['ckeditor5_sourceEditing']['allowed_tags']);

    // test_text_format before: not using the List plugin.
    $settings = $before['test_text_format']->getSettings();
    $this->assertArrayNotHasKey('ckeditor5_list', $settings['plugins']);

    $this->runUpdates();

    $after = Editor::loadMultiple();

    // Basic HTML after: reversed=FALSE, startIndex=FALSE, Source Editing
    // configuration unchanged.
    $settings = $after['basic_html']->getSettings();
    $this->assertSame(['reversed' => FALSE, 'startIndex' => TRUE], $settings['plugins']['ckeditor5_list']['properties']);
    $source_editable = HTMLRestrictions::fromString(implode(' ', $settings['plugins']['ckeditor5_sourceEditing']['allowed_tags']));
    $this->assertSame(['type' => TRUE], $source_editable->getAllowedElements()['ol']);

    // Full HTML after: reversed=TRUE, startIndex=TRUE, and Source Editing
    // configuration is unchanged.
    $settings = $after['full_html']->getSettings();
    $this->assertNotSame($before['full_html']->getSettings(), $after['full_html']->getSettings());
    $this->assertSame(['reversed' => TRUE, 'startIndex' => TRUE], $settings['plugins']['ckeditor5_list']['properties']);
    $this->assertSame([], $settings['plugins']['ckeditor5_sourceEditing']['allowed_tags']);

    // test_format_list_ol_start after: reversed=FALSE, startIndex=TRUE, and
    // Source Editing configuration has been updated to only <ol foo>.
    // Unlike the basic_html editor, this one was not yet modified by the user
    // on the site, so it does not yet have `settings.plugins.ckeditor5_list`.
    // Hence the missing update path is applied.
    $this->assertNotSame($before['test_format_list_ol_start']->getSettings(), $after['test_format_list_ol_start']->getSettings());
    $settings = $after['test_format_list_ol_start']->getSettings();
    $this->assertSame(['reversed' => FALSE, 'startIndex' => TRUE], $settings['plugins']['ckeditor5_list']['properties']);
    $this->assertSame(['<ol foo>'], $settings['plugins']['ckeditor5_sourceEditing']['allowed_tags']);

    // test_format_list_ol_start_post_3261599 after: no changes, because it was
    // updated from CKEditor 4 post-#3261599, which made this update a no-op.
    $this->assertSame($before['test_format_list_ol_start_post_3261599']->getSettings(), $after['test_format_list_ol_start_post_3261599']->getSettings());

    // test_text_format after: no changes.
    $this->assertSame($before['test_text_format']->getSettings(), $after['test_text_format']->getSettings());
  }

}

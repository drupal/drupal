<?php

namespace Drupal\Tests\media_library\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\media\Entity\Media;

/**
 * Contains Media library integration tests.
 *
 * @group media_library
 */
class MediaLibraryTest extends JavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'media_library_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a few example media items for use in selection.
    $media = [
      'type_one' => [
        'media_1',
        'media_2',
      ],
      'type_two' => [
        'media_3',
        'media_4',
      ],
    ];

    foreach ($media as $type => $names) {
      foreach ($names as $name) {
        $entity = Media::create(['name' => $name, 'bundle' => $type]);
        $source_field = $type === 'type_one' ? 'field_media_test' : 'field_media_test_1';
        $entity->set($source_field, $this->randomString());
        $entity->save();
      }
    }

    // Create a user who can use the Media library.
    $user = $this->drupalCreateUser([
      'access administration pages',
      'access media overview',
      'create media',
      'delete any media',
      'view media',
    ]);
    $this->drupalLogin($user);
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('local_actions_block');
  }

  /**
   * Tests that the Media library's administration page works as expected.
   */
  public function testAdministrationPage() {
    $assert_session = $this->assertSession();

    // Visit the administration page.
    $this->drupalGet('admin/content/media');

    // Verify that the "Add media" link is present.
    $assert_session->linkExists('Add media');

    // Verify that media from two separate types is present.
    $assert_session->pageTextContains('media_1');
    $assert_session->pageTextContains('media_3');

    // Test that users can filter by type.
    $this->getSession()->getPage()->selectFieldOption('Media type', 'Type One');
    $this->getSession()->getPage()->pressButton('Apply Filters');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextContains('media_2');
    $assert_session->pageTextNotContains('media_4');
    $this->getSession()->getPage()->selectFieldOption('Media type', 'Type Two');
    $this->getSession()->getPage()->pressButton('Apply Filters');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->pageTextNotContains('media_2');
    $assert_session->pageTextContains('media_4');

    // Test that selecting elements as a part of bulk operations works.
    $this->getSession()->getPage()->selectFieldOption('Media type', '- Any -');
    $this->getSession()->getPage()->pressButton('Apply Filters');
    $assert_session->assertWaitOnAjaxRequest();
    // This tests that anchor tags clicked inside the preview are suppressed.
    $this->getSession()->executeScript('jQuery(".js-click-to-select__trigger a")[0].click()');
    $this->submitForm([], 'Apply to selected items');
    $assert_session->pageTextContains('media_1');
    $assert_session->pageTextNotContains('media_2');
    $this->submitForm([], 'Delete');
    $assert_session->pageTextNotContains('media_1');
    $assert_session->pageTextContains('media_2');
  }

}

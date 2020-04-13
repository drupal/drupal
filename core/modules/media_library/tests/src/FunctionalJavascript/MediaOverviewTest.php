<?php

namespace Drupal\Tests\media_library\FunctionalJavascript;

/**
 * Tests the grid-style media overview page.
 *
 * @group media_library
 */
class MediaOverviewTest extends MediaLibraryTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a few example media items for use in selection.
    $this->createMediaItems([
      'type_one' => [
        'Horse',
        'Bear',
        'Cat',
        'Dog',
      ],
      'type_two' => [
        'Crocodile',
        'Lizard',
        'Snake',
        'Turtle',
      ],
    ]);

    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('local_actions_block');

    $user = $this->drupalCreateUser([
      'access media overview',
      'create media',
      'delete any media',
      'update any media',
    ]);
    $this->drupalLogin($user);
  }

  /**
   * Tests that the Media Library's administration page works as expected.
   */
  public function testAdministrationPage() {
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    // Visit the administration page.
    $this->drupalGet('admin/content/media');

    // There should be links to both the grid and table displays.
    $assert_session->linkExists('Grid');
    $assert_session->linkExists('Table');

    // We should see the table view and a link to add media.
    $assert_session->elementExists('css', '.view-media .views-table');
    $assert_session->linkExists('Add media');

    // Go to the grid display for the rest of the test.
    $page->clickLink('Grid');
    $assert_session->addressEquals('admin/content/media-grid');

    // Verify that the "Add media" link is present.
    $assert_session->linkExists('Add media');

    // Verify that media from two separate types is present.
    $assert_session->pageTextContains('Dog');
    $assert_session->pageTextContains('Turtle');

    // Verify that the media name does not contain a link.
    $assert_session->elementNotExists('css', '.media-library-item__name a');
    // Verify that there are links to edit and delete media items.
    $assert_session->linkExists('Edit Dog');
    $assert_session->linkExists('Delete Turtle');

    // Test that users can filter by type.
    $page->selectFieldOption('Media type', 'Type One');
    $page->pressButton('Apply filters');
    $this->waitForNoText('Turtle');
    $assert_session->pageTextContains('Dog');
    $page->selectFieldOption('Media type', 'Type Two');
    $page->pressButton('Apply filters');
    $this->waitForText('Turtle');
    $assert_session->pageTextNotContains('Dog');

    // Test that selecting elements as a part of bulk operations works.
    $page->selectFieldOption('Media type', '- Any -');
    $assert_session->elementExists('css', '#views-exposed-form-media-library-page')->submit();
    $this->waitForText('Dog');

    // This tests that anchor tags clicked inside the preview are suppressed.
    $this->getSession()->executeScript('jQuery(".js-click-to-select-trigger a")[4].click()');
    $this->submitForm([], 'Apply to selected items');
    $assert_session->pageTextContains('Dog');
    $assert_session->pageTextNotContains('Cat');
    // For reasons that are not clear, deleting media items by pressing the
    // "Delete" button can fail (the button is found, but never actually pressed
    // by the Mink driver). This workaround allows the delete form to be
    // submitted.
    $assert_session->elementExists('css', 'form')->submit();
    $assert_session->pageTextNotContains('Dog');
    $assert_session->pageTextContains('Cat');

    // Test the 'Select all media' checkbox and assert that it makes the
    // expected announcements.
    $select_all = $this->waitForFieldExists('Select all media');
    $select_all->check();
    $this->waitForText('All 7 items selected');
    $select_all->uncheck();
    $this->waitForText('Zero items selected');
    $select_all->check();
    $page->selectFieldOption('Action', 'media_delete_action');
    $this->submitForm([], 'Apply to selected items');
    // For reasons that are not clear, deleting media items by pressing the
    // "Delete" button can fail (the button is found, but never actually pressed
    // by the Mink driver). This workaround allows the delete form to be
    // submitted.
    $assert_session->elementExists('css', 'form')->submit();

    $assert_session->pageTextNotContains('Cat');
    $assert_session->pageTextNotContains('Turtle');
    $assert_session->pageTextNotContains('Snake');

    // Test empty text.
    $assert_session->pageTextContains('No media available.');
  }

}

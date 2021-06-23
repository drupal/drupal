<?php

namespace Drupal\Tests\media_library\FunctionalJavascript;

use Drupal\media\Entity\Media;

/**
 * Tests that extending in the Media library's widget works as expected.
 *
 * This test exists to protect contributed modules from getting broken.
 * That is, it helps assure backwards compatibility.
 *
 * @group media_library
 */
class WidgetExtendsAddFormTest extends MediaLibraryTestBase {

  /**
   * Tests that extended AddFormBase works.
   */
  public function testExtendedAddForm() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $driver = $this->getSession()->getDriver();

    // Create a user that can only add media of type four.
    $user = $this->drupalCreateUser([
      'access administration pages',
      'access content',
      'create basic_page content',
      'create type_seven media',
      'view media',
    ]);
    $this->drupalLogin($user);

    // Visit a node create page and open the media library.
    $this->drupalGet('node/add/basic_page');
    $this->openMediaLibraryForField('field_null_types_media');
    $this->switchToMediaType('Seven');
    $this->waitForText('Add Test Media');
    $assert_session->fieldExists('Add Test Media');
    $page->fillField('Add Test Media', 'This is not a valid name.');
    $page->pressButton('Add');
    $this->waitForText('Text is not appropriate.');
    $valid_name = 'I love Drupal and Drupal loves me.';
    $page->fillField('Add Test Media', 'I love Drupal and Drupal loves me.');
    $page->pressButton('Add');
    $assert_session->assertWaitOnAjaxRequest();
    $this->waitForText('The media item has been created but has not yet been saved.');
    $assert_session->fieldValueEquals('field_media_test', $valid_name);
    $this->pressSaveButton();
    $this->waitForText('Add Test Media');
    // Load the created media item.
    $media_items = Media::loadMultiple();
    $added_media = array_pop($media_items);

    // Ensure the media item was saved to the library and automatically
    // selected. The added media items should be in the first position of the
    // add form.
    $assert_session->pageTextContains('Add or select media');
    $assert_session->pageTextContains($valid_name);
    $assert_session->fieldValueEquals('media_library_select_form[0]', $added_media->id());
    $assert_session->checkboxChecked('media_library_select_form[0]');
  }

}

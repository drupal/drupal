<?php

namespace Drupal\Tests\media_library\FunctionalJavascript;

use Drupal\media\Entity\Media;
use Drupal\media_test_oembed\Controller\ResourceController;
use Drupal\Tests\media\Traits\OEmbedTestTrait;

/**
 * Tests that oEmbed media can be added in the Media library's widget.
 *
 * @group media_library
 */
class WidgetOEmbedTest extends MediaLibraryTestBase {

  use OEmbedTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['media_test_oembed'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->lockHttpClientToFixtures();
    $this->hijackProviderEndpoints();

    // Create a user who can use the Media library.
    $user = $this->drupalCreateUser([
      'access content',
      'create basic_page content',
      'view media',
      'create media',
      'administer media',
    ]);
    $this->drupalLogin($user);
  }

  /**
   * Tests that oEmbed media can be added in the Media library's widget.
   */
  public function testWidgetOEmbed() {
    $this->hijackProviderEndpoints();
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $youtube_title = "Everyday I'm Drupalin' Drupal Rap (Rick Ross - Hustlin)";
    $youtube_url = 'https://www.youtube.com/watch?v=PWjcqE3QKBg';
    $vimeo_title = "Drupal Rap Video - Schipulcon09";
    $vimeo_url = 'https://vimeo.com/7073899';
    ResourceController::setResourceUrl($youtube_url, $this->getFixturesDirectory() . '/video_youtube.json');
    ResourceController::setResourceUrl($vimeo_url, $this->getFixturesDirectory() . '/video_vimeo.json');
    ResourceController::setResource404('https://www.youtube.com/watch?v=PWjcqE3QKBg1');

    // Visit a node create page.
    $this->drupalGet('node/add/basic_page');

    // Add to the unlimited media field.
    $this->openMediaLibraryForField('field_unlimited_media');

    // Assert the default tab for media type one does not have an oEmbed form.
    $assert_session->fieldNotExists('Add Type Five via URL');

    // Assert other media types don't have the oEmbed form fields.
    $this->switchToMediaType('Three');
    $assert_session->fieldNotExists('Add Type Five via URL');

    // Assert we can add an oEmbed video to media type five.
    $this->switchToMediaType('Five');
    $page->fillField('Add Type Five via URL', $youtube_url);
    $assert_session->pageTextContains('Allowed providers: YouTube, Vimeo.');
    $page->pressButton('Add');
    // assertWaitOnAjaxRequest() required for input "id" attributes to
    // consistently match their label's "for" attribute.
    $assert_session->assertWaitOnAjaxRequest();
    $this->waitForText('The media item has been created but has not yet been saved.');
    // Assert the name field contains the remote video title.
    $assert_session->fieldValueEquals('Name', $youtube_title);
    $this->pressSaveButton();
    $this->waitForText('Add Type Five via URL');
    // Load the created media item.
    $media_items = Media::loadMultiple();
    $added_media = array_pop($media_items);

    // Ensure the media item was saved to the library and automatically
    // selected. The added media items should be in the first position of the
    // add form.
    $assert_session->pageTextContains('Add or select media');
    $assert_session->pageTextContains($youtube_title);
    $assert_session->fieldValueEquals('media_library_select_form[0]', $added_media->id());
    $assert_session->checkboxChecked('media_library_select_form[0]');

    // Assert the created oEmbed video is correctly added to the widget.
    $this->pressInsertSelected('Added one media item.');
    $this->waitForText($youtube_title);

    // Open the media library again for the unlimited field and go to the tab
    // for media type five.
    $this->openMediaLibraryForField('field_unlimited_media');
    $this->switchToMediaType('Five');
    // Assert the video is available on the tab.
    $assert_session->pageTextContains($youtube_title);

    // Assert we can only add supported URLs.
    $page->fillField('Add Type Five via URL', 'https://www.youtube.com/');
    $page->pressButton('Add');
    // assertWaitOnAjaxRequest() required for input "id" attributes to
    // consistently match their label's "for" attribute.
    $assert_session->assertWaitOnAjaxRequest();
    $this->waitForText('No matching provider found.');
    // Assert we can not add a video ID that doesn't exist. We need to use a
    // video ID that will not be filtered by the regex, because otherwise the
    // message 'No matching provider found.' will be returned.
    $page->fillField('Add Type Five via URL', 'https://www.youtube.com/watch?v=PWjcqE3QKBg1');
    $page->pressButton('Add');
    // assertWaitOnAjaxRequest() required for input "id" attributes to
    // consistently match their label's "for" attribute.
    $assert_session->assertWaitOnAjaxRequest();
    $this->waitForText('Could not retrieve the oEmbed resource.');

    // Select a media item to check if the selection is persisted when adding
    // new items.
    $checkbox = $page->findField("Select $youtube_title");
    $selected_item_id = $checkbox->getAttribute('value');
    $checkbox->click();
    $assert_session->pageTextContains('1 item selected');
    $assert_session->hiddenFieldValueEquals('current_selection', $selected_item_id);

    // Assert we can add a oEmbed video with a custom name.
    $page->fillField('Add Type Five via URL', $youtube_url);
    $page->pressButton('Add');
    // assertWaitOnAjaxRequest() required for input "id" attributes to
    // consistently match their label's "for" attribute.
    $assert_session->assertWaitOnAjaxRequest();
    $this->waitForText('The media item has been created but has not yet been saved.');
    $page->fillField('Name', 'Custom video title');
    $assert_session->elementNotExists('css', '.media-library-add-form__selected-media');
    $this->pressSaveButton();

    // Load the created media item.
    $media_items = Media::loadMultiple();
    $added_media = array_pop($media_items);
    // Ensure the media item was saved to the library and automatically
    // selected. The added media items should be in the first position of the
    // add form.
    $assert_session->pageTextContains('Add or select media');
    $assert_session->pageTextContains('Custom video title');
    $assert_session->fieldValueEquals('media_library_select_form[0]', $added_media->id());
    $assert_session->checkboxChecked('media_library_select_form[0]');
    // Assert the item that was selected before uploading the file is still
    // selected.
    $assert_session->pageTextContains('2 items selected');
    $assert_session->checkboxChecked("Select Custom video title");
    $assert_session->checkboxChecked("Select $youtube_title");
    $assert_session->hiddenFieldValueEquals('current_selection', implode(',', [$selected_item_id, $added_media->id()]));
    $selected_checkboxes = [];
    foreach ($this->getCheckboxes() as $checkbox) {
      if ($checkbox->isChecked()) {
        $selected_checkboxes[] = $checkbox->getAttribute('value');
      }
    }
    $this->assertCount(2, $selected_checkboxes);
    // Ensure the created item is added in the widget.
    $this->pressInsertSelected('Added 2 media items.');
    $this->waitForText('Custom video title');

    // Assert we can directly insert added oEmbed media in the widget.
    $this->openMediaLibraryForField('field_unlimited_media');
    $this->switchToMediaType('Five');
    $page->fillField('Add Type Five via URL', $vimeo_url);
    $page->pressButton('Add');
    $this->waitForText('The media item has been created but has not yet been saved.');
    $this->pressSaveButton();
    $this->waitForText('Add or select media');
    $this->pressInsertSelected();
    $this->waitForText($vimeo_title);

    // Assert we can remove selected items from the selection area in the oEmbed
    // form.
    $this->openMediaLibraryForField('field_unlimited_media');
    $this->switchToMediaType('Five');
    $checkbox = $page->findField("Select $vimeo_title");
    $selected_item_id = $checkbox->getAttribute('value');
    $checkbox->click();
    $assert_session->hiddenFieldValueEquals('current_selection', $selected_item_id);
    $page->fillField('Add Type Five via URL', $youtube_url);
    $page->pressButton('Add');
    // assertWaitOnAjaxRequest() required for input "id" attributes to
    // consistently match their label's "for" attribute.
    $assert_session->assertWaitOnAjaxRequest();
    $this->waitForText('The media item has been created but has not yet been saved');
    $page->fillField('Name', 'Another video');
    $this->pressSaveButton();
    $page->uncheckField('media_library_select_form[1]');
    $this->waitForText('1 item selected');
    $this->pressInsertSelected('Added one media item.');
    $this->waitForText('Another video');

    // Assert removing an added oEmbed media item before save works as expected.
    $this->openMediaLibraryForField('field_unlimited_media');
    $this->switchToMediaType('Five');
    $page->fillField('Add Type Five via URL', $youtube_url);
    $page->pressButton('Add');
    // Assert the media item fields are shown and the vertical tabs are no
    // longer shown.
    $this->assertMediaAdded();
    // Press the 'Remove button' and assert the user is sent back to the media
    // library.
    $page->pressButton('media-0-remove-button');
    // Assert the remove message is shown.
    $this->waitForText("The media item $youtube_title has been removed.");
    $this->assertNoMediaAdded();
  }

  /**
   * Tests that oEmbed media can be added in the widget's advanced UI.
   *
   * @todo Merge this with testWidgetOEmbed() in
   *   https://www.drupal.org/project/drupal/issues/3087227
   */
  public function testWidgetOEmbedAdvancedUi() {
    $this->config('media_library.settings')->set('advanced_ui', TRUE)->save();

    $this->hijackProviderEndpoints();
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $youtube_title = "Everyday I'm Drupalin' Drupal Rap (Rick Ross - Hustlin)";
    $youtube_url = 'https://www.youtube.com/watch?v=PWjcqE3QKBg';
    $vimeo_title = "Drupal Rap Video - Schipulcon09";
    $vimeo_url = 'https://vimeo.com/7073899';
    ResourceController::setResourceUrl($youtube_url, $this->getFixturesDirectory() . '/video_youtube.json');
    ResourceController::setResourceUrl($vimeo_url, $this->getFixturesDirectory() . '/video_vimeo.json');
    ResourceController::setResource404('https://www.youtube.com/watch?v=PWjcqE3QKBg1');

    // Visit a node create page.
    $this->drupalGet('node/add/basic_page');

    // Add to the unlimited media field.
    $this->openMediaLibraryForField('field_unlimited_media');

    // Assert the default tab for media type one does not have an oEmbed form.
    $assert_session->fieldNotExists('Add Type Five via URL');

    // Assert other media types don't have the oEmbed form fields.
    $this->switchToMediaType('Three');
    $assert_session->fieldNotExists('Add Type Five via URL');

    // Assert we can add an oEmbed video to media type five.
    $this->switchToMediaType('Five');
    $page->fillField('Add Type Five via URL', $youtube_url);
    $assert_session->pageTextContains('Allowed providers: YouTube, Vimeo.');
    $page->pressButton('Add');
    // assertWaitOnAjaxRequest() required for input "id" attributes to
    // consistently match their label's "for" attribute.
    $assert_session->assertWaitOnAjaxRequest();
    $this->waitForText('The media item has been created but has not yet been saved.');
    // Assert the name field contains the remote video title.
    $assert_session->fieldValueEquals('Name', $youtube_title);
    $this->saveAnd('select');
    $this->waitForText('Add Type Five via URL');
    // Load the created media item.
    $media_items = Media::loadMultiple();
    $added_media = array_pop($media_items);

    // Ensure the media item was saved to the library and automatically
    // selected. The added media items should be in the first position of the
    // add form.
    $assert_session->pageTextContains('Add or select media');
    $assert_session->pageTextContains($youtube_title);
    $assert_session->fieldValueEquals('media_library_select_form[0]', $added_media->id());
    $assert_session->checkboxChecked('media_library_select_form[0]');

    // Assert the created oEmbed video is correctly added to the widget.
    $this->pressInsertSelected('Added one media item.');
    $this->waitForText($youtube_title);

    // Open the media library again for the unlimited field and go to the tab
    // for media type five.
    $this->openMediaLibraryForField('field_unlimited_media');
    $this->switchToMediaType('Five');
    // Assert the video is available on the tab.
    $assert_session->pageTextContains($youtube_title);

    // Assert we can only add supported URLs.
    $page->fillField('Add Type Five via URL', 'https://www.youtube.com/');
    $page->pressButton('Add');
    // assertWaitOnAjaxRequest() required for input "id" attributes to
    // consistently match their label's "for" attribute.
    $assert_session->assertWaitOnAjaxRequest();
    $this->waitForText('No matching provider found.');
    // Assert we can not add a video ID that doesn't exist. We need to use a
    // video ID that will not be filtered by the regex, because otherwise the
    // message 'No matching provider found.' will be returned.
    $page->fillField('Add Type Five via URL', 'https://www.youtube.com/watch?v=PWjcqE3QKBg1');
    $page->pressButton('Add');
    // assertWaitOnAjaxRequest() required for input "id" attributes to
    // consistently match their label's "for" attribute.
    $assert_session->assertWaitOnAjaxRequest();
    $this->waitForText('Could not retrieve the oEmbed resource.');

    // Select a media item to check if the selection is persisted when adding
    // new items.
    $checkbox = $page->findField("Select $youtube_title");
    $selected_item_id = $checkbox->getAttribute('value');
    $checkbox->click();
    $assert_session->pageTextContains('1 item selected');
    $assert_session->hiddenFieldValueEquals('current_selection', $selected_item_id);

    // Assert we can add a oEmbed video with a custom name.
    $page->fillField('Add Type Five via URL', $youtube_url);
    $page->pressButton('Add');
    // assertWaitOnAjaxRequest() required for input "id" attributes to
    // consistently match their label's "for" attribute.
    $assert_session->assertWaitOnAjaxRequest();
    $this->waitForText('The media item has been created but has not yet been saved.');
    $page->fillField('Name', 'Custom video title');
    $assert_session->checkboxChecked("Select $youtube_title", $this->getSelectionArea());
    $this->saveAnd('select');
    $this->waitForNoText('Save and select');

    // Load the created media item.
    $media_items = Media::loadMultiple();
    $added_media = array_pop($media_items);
    // Ensure the media item was saved to the library and automatically
    // selected. The added media items should be in the first position of the
    // add form.
    $assert_session->pageTextContains('Add or select media');
    $assert_session->pageTextContains('Custom video title');
    $assert_session->fieldValueEquals('media_library_select_form[0]', $added_media->id());
    $assert_session->checkboxChecked('media_library_select_form[0]');
    // Assert the item that was selected before uploading the file is still
    // selected.
    $assert_session->pageTextContains('2 items selected');
    $assert_session->checkboxChecked("Select Custom video title");
    $assert_session->checkboxChecked("Select $youtube_title");
    $assert_session->hiddenFieldValueEquals('current_selection', implode(',', [$selected_item_id, $added_media->id()]));
    $selected_checkboxes = [];
    foreach ($this->getCheckboxes() as $checkbox) {
      if ($checkbox->isChecked()) {
        $selected_checkboxes[] = $checkbox->getAttribute('value');
      }
    }
    $this->assertCount(2, $selected_checkboxes);
    // Ensure the created item is added in the widget.
    $this->pressInsertSelected('Added 2 media items.');
    $this->waitForText('Custom video title');

    // Assert we can directly insert added oEmbed media in the widget.
    $this->openMediaLibraryForField('field_unlimited_media');
    $this->switchToMediaType('Five');
    $page->fillField('Add Type Five via URL', $vimeo_url);
    $page->pressButton('Add');
    $this->waitForText('The media item has been created but has not yet been saved.');

    $this->saveAnd('insert');
    $this->waitForText('Added one media item.');
    $this->waitForNoText('Add or select media');
    $this->waitForText($vimeo_title);

    // Assert we can remove selected items from the selection area in the oEmbed
    // form.
    $this->openMediaLibraryForField('field_unlimited_media');
    $this->switchToMediaType('Five');
    $checkbox = $page->findField("Select $vimeo_title");
    $selected_item_id = $checkbox->getAttribute('value');
    $checkbox->click();
    $assert_session->hiddenFieldValueEquals('current_selection', $selected_item_id);
    $page->fillField('Add Type Five via URL', $youtube_url);
    $page->pressButton('Add');
    // assertWaitOnAjaxRequest() required for input "id" attributes to
    // consistently match their label's "for" attribute.
    $assert_session->assertWaitOnAjaxRequest();
    $this->waitForText('The media item has been created but has not yet been saved');
    $page->fillField('Name', 'Another video');
    $selection_area = $this->getSelectionArea();
    $assert_session->checkboxChecked("Select $vimeo_title", $selection_area);
    $page->uncheckField("Select $vimeo_title");
    $assert_session->hiddenFieldValueEquals('current_selection', '');
    // Close the details element so that clicking the Save and select works.
    // @todo Fix dialog or test so this is not necessary to prevent random
    //   fails. https://www.drupal.org/project/drupal/issues/3055648
    $selection_area->find('css', 'summary')->click();
    $this->saveAnd('select');

    $media_items = Media::loadMultiple();
    $added_media = array_pop($media_items);
    $this->waitForText('1 item selected');
    $assert_session->checkboxChecked('Select Another video');
    $assert_session->checkboxNotChecked("Select $vimeo_title");
    $assert_session->hiddenFieldValueEquals('current_selection', $added_media->id());
    $this->pressInsertSelected('Added one media item.');
    $this->waitForText('Another video');

    // Assert removing an added oEmbed media item before save works as expected.
    $this->openMediaLibraryForField('field_unlimited_media');
    $this->switchToMediaType('Five');
    $page->fillField('Add Type Five via URL', $youtube_url);
    $page->pressButton('Add');
    // Assert the media item fields are shown and the vertical tabs are no
    // longer shown.
    $this->assertMediaAdded();
    // Press the 'Remove button' and assert the user is sent back to the media
    // library.
    $page->pressButton('media-0-remove-button');
    // Assert the remove message is shown.
    $this->waitForText("The media item $youtube_title has been removed.");
    $this->assertNoMediaAdded();
  }

}

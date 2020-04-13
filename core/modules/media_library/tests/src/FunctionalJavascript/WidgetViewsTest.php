<?php

namespace Drupal\Tests\media_library\FunctionalJavascript;

/**
 * Tests the views in the media library widget.
 *
 * @group media_library
 */
class WidgetViewsTest extends MediaLibraryTestBase {

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
        'Goat',
        'Sheep',
        'Pig',
        'Cow',
        'Chicken',
        'Duck',
        'Donkey',
        'Llama',
        'Mouse',
        'Goldfish',
        'Rabbit',
        'Turkey',
        'Dove',
        'Giraffe',
        'Tiger',
        'Hamster',
        'Parrot',
        'Monkey',
        'Koala',
        'Panda',
        'Kangaroo',
      ],
      'type_two' => [
        'Crocodile',
        'Lizard',
        'Snake',
        'Turtle',
      ],
    ]);

    // Create a user who can use the Media library.
    $user = $this->drupalCreateUser([
      'access content',
      'create basic_page content',
      'view media',
      'create media',
    ]);
    $this->drupalLogin($user);
  }

  /**
   * Tests that the views in the Media library's widget work as expected.
   */
  public function testWidgetViews() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalGet('node/add/basic_page');

    $this->openMediaLibraryForField('field_unlimited_media');

    // Assert the 'Apply filter' button is not moved to the button pane.
    $button_pane = $assert_session->elementExists('css', '.ui-dialog-buttonpane');
    $assert_session->buttonExists('Insert selected', $button_pane);
    $assert_session->buttonNotExists('Apply filters', $button_pane);

    // Assert the pager works as expected.
    $assert_session->elementTextContains('css', '.js-media-library-view .pager__item.is-active', 'Page 1');
    $this->assertCount(24, $this->getCheckboxes());
    $page->clickLink('Next page');
    $this->waitForElementTextContains('.js-media-library-view .pager__item.is-active', 'Page 2');
    $this->assertCount(1, $this->getCheckboxes());
    $page->clickLink('Previous page');
    $this->waitForElementTextContains('.js-media-library-view .pager__item.is-active', 'Page 1');
    $this->assertCount(24, $this->getCheckboxes());

    $this->switchToMediaLibraryTable();

    // Assert the 'Apply filter' button is not moved to the button pane.
    $assert_session->buttonExists('Insert selected', $button_pane);
    $assert_session->buttonNotExists('Apply filters', $button_pane);
    $assert_session->pageTextContains('Dog');
    $assert_session->pageTextContains('Bear');
    $assert_session->pageTextNotContains('Turtle');

    // Assert the exposed filters can be applied.
    $page->fillField('Name', 'Dog');
    $page->pressButton('Apply filters');
    $this->waitForText('Dog');
    $this->waitForNoText('Bear');
    $assert_session->pageTextNotContains('Turtle');
    $page->checkField('Select Dog');
    $assert_session->linkExists('Table');
    $this->switchToMediaLibraryGrid();

    // Assert the exposed filters are persisted when changing display.
    $this->assertSame('Dog', $page->findField('Name')->getValue());
    $assert_session->pageTextContains('Dog');
    $assert_session->pageTextNotContains('Bear');
    $assert_session->pageTextNotContains('Turtle');
    $assert_session->linkExists('Grid');
    $this->switchToMediaLibraryTable();

    // Select the item.
    $this->pressInsertSelected('Added one media item.');
    // Ensure that the selection completed successfully.
    $assert_session->pageTextContains('Dog');
    $assert_session->pageTextNotContains('Bear');
    $assert_session->pageTextNotContains('Turtle');
  }

}

<?php

declare(strict_types=1);

namespace Drupal\Tests\media_library\FunctionalJavascript;

/**
 * Tests Media Library's integration with Views UI.
 *
 * @group media_library
 */
class ViewsUiIntegrationTest extends MediaLibraryTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['views_ui'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

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

    $account = $this->drupalCreateUser(['administer views']);
    $this->drupalLogin($account);
  }

  /**
   * Tests that the integration with Views works correctly.
   */
  public function testViewsAdmin(): void {
    $page = $this->getSession()->getPage();

    // Assert that the widget can be seen and that there are 8 items.
    $this->drupalGet('/admin/structure/views/view/media_library/edit/widget');
    $this->waitForElementsCount('css', '.js-media-library-item', 8);

    // Assert that filtering works in live preview.
    $page->find('css', '.js-media-library-view')->fillField('name', 'snake');
    $page->find('css', '.js-media-library-view')->pressButton('Apply filters');
    $this->waitForElementsCount('css', '.js-media-library-item', 1);

    // Test the same routine but in the view for the table widget.
    $this->drupalGet('/admin/structure/views/view/media_library/edit/widget_table');
    $this->waitForElementsCount('css', '.js-media-library-item', 8);

    // Assert that filtering works in live preview.
    $page->find('css', '.js-media-library-view')->fillField('name', 'snake');
    $page->find('css', '.js-media-library-view')->pressButton('Apply filters');
    $this->waitForElementsCount('css', '.js-media-library-item', 1);

    // We cannot test clicking the 'Insert selected' button in either view
    // because we expect an AJAX error, which would always throw an exception
    // on ::tearDown even if we try to catch it here. If there is an API for
    // marking certain elements 'unsuitable for previewing', we could test that
    // here.
    // @see https://www.drupal.org/project/drupal/issues/3060852
  }

}

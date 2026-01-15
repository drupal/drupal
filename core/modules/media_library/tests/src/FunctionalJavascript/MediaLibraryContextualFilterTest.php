<?php

declare(strict_types=1);

namespace Drupal\Tests\media_library\FunctionalJavascript;

use Drupal\media\Entity\Media;
use Drupal\media_library\MediaLibraryUiBuilder;
use Drupal\views\Entity\View;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the media library view with contextual filters.
 */
#[Group('media_library')]
#[RunTestsInSeparateProcesses]
#[CoversMethod(MediaLibraryUiBuilder::class, 'buildMediaLibraryView')]
class MediaLibraryContextualFilterTest extends MediaLibraryTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Add media_field_data.uid = current_user as contextual filter to
    // media library widget displays.
    $view = View::load('media_library');
    $executable = $view->getExecutable();
    foreach (['widget', 'widget_table'] as $display_id) {
      $executable->addHandler($display_id, 'argument', 'media_field_data', 'uid', [
        'default_argument_type' => 'current_user',
        'default_argument_options' => [],
        'default_action' => 'default',
      ]);
    }
    $executable->save();
  }

  /**
   * Test contextual filters in the media library.
   */
  public function testMediaLibraryContextualFilter(): void {
    // Create users for adding media and using the media library.
    $permissions = [
      'create basic_page content',
    ];
    $user1 = $this->createUser($permissions, 'user 1');
    $user2 = $this->createUser($permissions, 'user 2');

    // Create media items with user 1.
    Media::create([
      'name' => 'Mosquito',
      'bundle' => 'type_one',
      'field_media_test' => 'Mosquito',
      'status' => TRUE,
      'uid' => $user1->id(),
    ])->save();
    Media::create([
      'name' => 'Ant',
      'bundle' => 'type_one',
      'field_media_test' => 'Ant',
      'status' => TRUE,
      'uid' => $user1->id(),
    ])->save();

    // Create media items with user 2.
    Media::create([
      'name' => 'Bear',
      'bundle' => 'type_one',
      'field_media_test' => 'Bear',
      'status' => TRUE,
      'uid' => $user2->id(),
    ])->save();
    Media::create([
      'name' => 'Horse',
      'bundle' => 'type_one',
      'field_media_test' => 'Horse',
      'status' => TRUE,
      'uid' => $user2->id(),
    ])->save();

    $this->drupalLogin($user2);
    // Visit a node create page with user 2.
    $this->drupalGet('node/add/basic_page');
    $this->openMediaLibraryForField('field_unlimited_media');
    $this->assertElementExistsAfterWait('css', '.js-media-library-item');
    // Verify number of items on initial load of the media library widget.
    $this->waitForElementsCount('css', '#media-library-view .js-media-library-item', 2);

    // Switch to the table widget.
    $this->switchToMediaLibraryTable();
    // Verify number of items again.
    $this->waitForElementsCount('css', '#media-library-view .js-media-library-item', 2);

    // Switch back to the grid display.
    $this->switchToMediaLibraryGrid();
    // Verify number of items again.
    $this->waitForElementsCount('css', '#media-library-view .js-media-library-item', 2);

    // Submit exposed views filters.
    $this->getSession()->getPage()->find('css', '#media-library-view')->pressButton('Apply filters');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->waitForElementsCount('css', '#media-library-view .js-media-library-item', 2);

    // Select and submit items.
    $this->selectMediaItem(0);
    $this->selectMediaItem(1);
    $this->pressInsertSelected();
  }

}

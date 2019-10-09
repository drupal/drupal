<?php

namespace Drupal\Tests\media_library\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\views\Views;

/**
 * Tests the media library module updates views checkbox classes.
 *
 * @group media_library
 * @group legacy
 */
class MediaLibraryUpdateCheckboxClassesTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  public function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.4.0.bare.standard.php.gz',
      __DIR__ . '/../../../../../media/tests/fixtures/update/drupal-8.4.0-media_installed.php',
      __DIR__ . '/../../../fixtures/update/drupal-8.8.x-media_library-update-views-classnames-3049943.php',
    ];
  }

  /**
   * Tests that non js prefixes are added to checkboxes in the media view.
   *
   * @see media_library_post_update_update_8001_checkbox_classes()
   */
  public function testAddNonPrefixedClasses() {
    $view = Views::getView('media_library');

    $display_items = [
      [
        'display_id' => 'default',
        'option' => 'element_class',
        'field' => 'media_bulk_form',
      ],
      [
        'display_id' => 'page',
        'option' => 'element_class',
        'field' => 'media_bulk_form',
      ],
      [
        'display_id' => 'widget',
        'option' => 'element_wrapper_class',
        'field' => 'media_library_select_form',
      ],
      [
        'display_id' => 'widget_table',
        'option' => 'element_wrapper_class',
        'field' => 'media_library_select_form',
      ],
    ];
    foreach ($display_items as $item) {
      $display_id = $item['display_id'];
      $option = $item['option'];
      $field = $item['field'];
      $display = $view->storage->getDisplay($display_id);
      $classes_string = $display['display_options']['fields'][$field][$option];
      $classes = preg_split('/\s+/', $classes_string);
      $this->assertContains('js-click-to-select-checkbox', $classes);
      $this->assertNotContains('media-library-item__click-to-select-checkbox', $classes);
    }

    $this->runUpdates();
    $view = Views::getView('media_library');

    foreach ($display_items as $item) {
      $display_id = $item['display_id'];
      $option = $item['option'];
      $field = $item['field'];
      $display = $view->storage->getDisplay($display_id);
      $classes_string = $display['display_options']['fields'][$field][$option];
      $classes = preg_split('/\s+/', $classes_string);
      $this->assertContains('js-click-to-select-checkbox', $classes);
      $this->assertContains('media-library-item__click-to-select-checkbox', $classes, "Class 'media-library-item__click-to-select-checkbox' not found in display: $display_id");
    }
  }

}

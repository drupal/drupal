<?php

/**
 * @file
 * Contains \Drupal\views_ui\tests\StorageTest.
 */

namespace Drupal\views_ui\Tests;

use Drupal\Core\Language\Language;

/**
 * Tests the UI of storage properties of views.
 */
class StorageTest extends UITestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view');

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('views_ui', 'language');

  public static function getInfo() {
    return array(
      'name' => 'Storage properties',
      'description' => 'Tests the UI of storage properties of views.',
      'group' => 'Views UI',
    );
  }

  /**
   * Tests changing label, description and tag.
   *
   * @see views_ui_edit_details_form
   */
  public function testDetails() {
    $view_name = 'test_view';

    $language = new Language(array('name' => 'French', 'id' => 'fr'));
    language_save($language);

    $edit = array(
      'label' => $this->randomName(),
      'tag' => $this->randomName(),
      'description' => $this->randomName(30),
      'langcode' => 'fr',
    );

    $this->drupalPost("admin/structure/views/nojs/edit-details/$view_name/default", $edit, t('Apply'));
    $this->drupalPost(NULL, array(), t('Save'));

    $view = views_get_view($view_name);

    foreach (array('label', 'tag', 'description', 'langcode') as $property) {
      $this->assertEqual($view->storage->get($property), $edit[$property], format_string('Make sure the property @property got probably saved.', array('@property' => $property)));
    }
  }

}

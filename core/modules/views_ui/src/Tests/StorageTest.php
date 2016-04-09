<?php

namespace Drupal\views_ui\Tests;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\views\Views;

/**
 * Tests the UI of storage properties of views.
 *
 * @group views_ui
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

  /**
   * Tests changing label, description and tag.
   *
   * @see views_ui_edit_details_form
   */
  public function testDetails() {
    $view_name = 'test_view';

    ConfigurableLanguage::createFromLangcode('fr')->save();

    $edit = array(
      'label' => $this->randomMachineName(),
      'tag' => $this->randomMachineName(),
      'description' => $this->randomMachineName(30),
      'langcode' => 'fr',
    );

    $this->drupalPostForm("admin/structure/views/nojs/edit-details/$view_name/default", $edit, t('Apply'));
    $this->drupalPostForm(NULL, array(), t('Save'));

    $view = Views::getView($view_name);

    foreach (array('label', 'tag', 'description', 'langcode') as $property) {
      $this->assertEqual($view->storage->get($property), $edit[$property], format_string('Make sure the property @property got probably saved.', array('@property' => $property)));
    }
  }

}

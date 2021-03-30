<?php

namespace Drupal\Tests\views_ui\Functional;

use Drupal\Component\Render\FormattableMarkup;
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
  public static $testViews = ['test_view'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['views_ui', 'language'];

  /**
   * Tests changing label, description and tag.
   *
   * @see views_ui_edit_details_form
   */
  public function testDetails() {
    $view_name = 'test_view';

    ConfigurableLanguage::createFromLangcode('fr')->save();

    $edit = [
      'label' => $this->randomMachineName(),
      'tag' => $this->randomMachineName(),
      'description' => $this->randomMachineName(30),
      'langcode' => 'fr',
    ];

    $this->drupalPostForm("admin/structure/views/nojs/edit-details/$view_name/default", $edit, 'Apply');
    $this->submitForm([], 'Save');

    $view = Views::getView($view_name);

    foreach (['label', 'tag', 'description', 'langcode'] as $property) {
      $this->assertEqual($edit[$property], $view->storage->get($property), new FormattableMarkup('Make sure the property @property got probably saved.', ['@property' => $property]));
    }
  }

}

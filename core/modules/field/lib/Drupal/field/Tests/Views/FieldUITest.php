<?php

/**
 * @file
 * Contains \Drupal\field\Tests\Views\FieldUITest.
 */

namespace Drupal\field\Tests\Views;

/**
 * Tests the UI of the field field handler.
 *
 * @see \Drupal\field\Plugin\views\field\Field
 */
class FieldUITest extends FieldTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view_fieldapi');

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('views_ui');

  public static function getInfo() {
    return array(
      'name' => 'Field: Field handler UI',
      'description' => 'Tests the UI of the field field handler.',
      'group' => 'Views UI'
    );
  }

  /**
   * Tests basic field handler settings in the UI.
   */
  public function testHandlerUI() {
    $account = $this->drupalCreateUser(array('administer views'));
    $this->drupalLogin($account);

    $this->setUpFields();
    $this->setUpInstances();

    $url = "admin/structure/views/nojs/config-item/test_view_fieldapi/default/field/field_name_0";
    $this->drupalGet($url);

    // Tests the available formatter options.
    $result = $this->xpath('//select[@id=:id]/option', array(':id' => 'edit-options-type'));
    $options = array_map(function($item) {
      return (string) $item->attributes()->value[0];
    }, $result);
    // @todo Replace this sort by assertArray once it's in.
    sort($options, SORT_STRING);
    $this->assertEqual($options, array('text_default', 'text_plain', 'text_trimmed'), 'The text formatters for a simple text field appear as expected.');

    $this->drupalPostForm(NULL, array('options[type]' => 'text_trimmed'), t('Apply'));

    $this->drupalGet($url);
    $this->assertOptionSelected('edit-options-type', 'text_trimmed');

    $random_number = rand(100, 400);
    $this->drupalPostForm(NULL, array('options[settings][trim_length]' => $random_number), t('Apply'));
    $this->drupalGet($url);
    $this->assertFieldByName('options[settings][trim_length]', $random_number, 'The formatter setting got saved.');

    // Save the view and test whether the settings are saved.
    $this->drupalPostForm('admin/structure/views/view/test_view_fieldapi', array(), t('Save'));
    $view = views_get_view('test_view_fieldapi');
    $view->initHandlers();
    $this->assertEqual($view->field['field_name_0']->options['type'], 'text_trimmed');
    $this->assertEqual($view->field['field_name_0']->options['settings']['trim_length'], $random_number);
  }

}

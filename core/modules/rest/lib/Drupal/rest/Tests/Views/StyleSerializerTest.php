<?php

/**
 * @file
 * Contains \Drupal\rest\Tests\Views\StyleSerializerTest.
 */

namespace Drupal\rest\Tests\Views;

use Drupal\views\Tests\Plugin\PluginTestBase;
use Drupal\views\Tests\ViewTestData;

/**
 * Tests the serializer style plugin.
 *
 * @see \Drupal\rest\Plugin\views\display\RestExport
 * @see \Drupal\rest\Plugin\views\style\Serializer
 * @see \Drupal\rest\Plugin\views\row\DataEntityRow
 * @see \Drupal\rest\Plugin\views\row\DataFieldRow
 */
class StyleSerializerTest extends PluginTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('views_ui', 'entity_test', 'hal', 'rest_test_views');

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_serializer_display_field', 'test_serializer_display_entity');

  /**
   * A user with administrative privileges to look at test entity and configure views.
   */
  protected $adminUser;

  public static function getInfo() {
    return array(
      'name' => 'Style: Serializer plugin',
      'description' => 'Tests the serializer style plugin.',
      'group' => 'Views Plugins',
    );
  }

  protected function setUp() {
    parent::setUp();

    ViewTestData::importTestViews(get_class($this), array('rest_test_views'));

    $this->adminUser = $this->drupalCreateUser(array('administer views', 'administer entity_test content', 'access user profiles', 'view test entity'));

    // Save some entity_test entities.
    for ($i = 1; $i <= 10; $i++) {
      entity_create('entity_test', array('name' => 'test_' . $i, 'user_id' => $this->adminUser->id()))->save();
    }

    $this->enableViewsTestModule();
  }

  /**
   * Checks the behavior of the Serializer callback paths and row plugins.
   */
  public function testSerializerResponses() {
    // Test the serialize callback.
    $view = views_get_view('test_serializer_display_field');
    $view->initDisplay();
    $this->executeView($view);

    $actual_json = $this->drupalGet('test/serialize/field', array(), array('Accept: application/json'));
    $this->assertResponse(200);

    // Test the http Content-type.
    $headers = $this->drupalGetHeaders();
    $this->assertEqual($headers['content-type'], 'application/json', 'The header Content-type is correct.');

    $expected = array();
    foreach ($view->result as $row) {
      $expected_row = array();
      foreach ($view->field as $id => $field) {
        if ($field->field_alias == 'unknown') {
          $expected_row[$id] = $field->render($row);
        }
        else {
          $expected_row[$id] = $row->{$field->field_alias};
        }
      }
      $expected[] = $expected_row;
    }

    $this->assertIdentical($actual_json, json_encode($expected), 'The expected JSON output was found.');


    // Test that the rendered output and the preview output are the same.
    $view->destroy();
    $view->setDisplay('rest_export_1');
    // Mock the request content type by setting it on the display handler.
    $view->display_handler->setContentType('json');
    $output = $view->preview();
    $this->assertIdentical($actual_json, drupal_render($output), 'The expected JSON preview output was found.');

    // Test a 403 callback.
    $this->drupalGet('test/serialize/denied');
    $this->assertResponse(403);

    // Test the entity rows.

    $view = views_get_view('test_serializer_display_entity');
    $view->initDisplay();
    $this->executeView($view);

    // Get the serializer service.
    $serializer = drupal_container()->get('serializer');

    $entities = array();
    foreach ($view->result as $row) {
      $entities[] = $row->_entity;
    }

    $expected = $serializer->serialize($entities, 'json');

    $actual_json = $this->drupalGet('test/serialize/entity', array(), array('Accept: application/json'));
    $this->assertResponse(200);

    $this->assertIdentical($actual_json, $expected, 'The expected JSON output was found.');

    $expected = $serializer->serialize($entities, 'hal_json');
    $actual_json = $this->drupalGet('test/serialize/entity', array(), array('Accept: application/hal+json'));
    $this->assertIdentical($actual_json, $expected, 'The expected HAL output was found.');
  }

  /**
   * Test the field ID alias functionality of the DataFieldRow plugin.
   */
  public function testUIFieldAlias() {
    $this->drupalLogin($this->adminUser);

    // Test the UI settings for adding field ID aliases.
    $this->drupalGet('admin/structure/views/view/test_serializer_display_field/edit/rest_export_1');
    $row_options = 'admin/structure/views/nojs/display/test_serializer_display_field/rest_export_1/row_options';
    $this->assertLinkByHref($row_options);

    // Test an empty string for an alias, this should not be used. This also
    // tests that the form can be submitted with no aliases.
    $this->drupalPost($row_options, array('row_options[aliases][name]' => ''), t('Apply'));
    $this->drupalPost(NULL, array(), t('Save'));

    $view = views_get_view('test_serializer_display_field');
    $view->setDisplay('rest_export_1');
    $this->executeView($view);

    $expected = array();
    foreach ($view->result as $row) {
      $expected_row = array();
      foreach ($view->field as $id => $field) {
        // Original field key is expected.
        if ($field->field_alias == 'unknown') {
          $expected_row[$id] = $field->render($row);
        }
        else {
          $expected_row[$id] = $row->{$field->field_alias};
        }
      }
      $expected[] = $expected_row;
    }

    // Use an AJAX call, as this will return decoded JSON data.
    $this->assertIdentical($this->drupalGetAJAX('test/serialize/field'), $expected);

    // Test a random aliases for fields, they should be replaced.
    $random_name = $this->randomName();
    // Use # to produce an invalid character for the validation.
    $invalid_random_name = '#' . $this->randomName();
    $edit = array('row_options[aliases][name]' => $random_name, 'row_options[aliases][nothing]' => $invalid_random_name);
    $this->drupalPost($row_options, $edit, t('Apply'));
    $this->assertText(t('The machine-readable name must contain only letters, numbers, dashes and underscores.'));

    $random_name_custom = $this->randomName();
    $edit = array('row_options[aliases][name]' => $random_name, 'row_options[aliases][nothing]' => $random_name_custom);
    $this->drupalPost($row_options, $edit, t('Apply'));

    $this->drupalPost(NULL, array(), t('Save'));

    $view = views_get_view('test_serializer_display_field');
    $view->setDisplay('ws_endpoint_1');
    $this->executeView($view);

    $expected = array();
    foreach ($view->result as $row) {
      $expected_row = array();
      foreach ($view->field as $id => $field) {
        // This will be the custom field.
        if ($field->field_alias == 'unknown') {
          $expected_row[$random_name_custom] = $field->render($row);
        }
        // This will be the name field.
        else {
          $expected_row[$random_name] = $row->{$field->field_alias};
        }
      }
      $expected[] = $expected_row;
    }

    $this->assertIdentical($this->drupalGetAJAX('test/serialize/field'), $expected);
  }

}

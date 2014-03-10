<?php

/**
 * @file
 * Contains \Drupal\file\Tests\Views\ExtensionViewsFieldTest.
 */

namespace Drupal\file\Tests\Views;

use Drupal\views\Views;
use Drupal\views\Tests\ViewUnitTestBase;
use Drupal\views\Tests\ViewTestData;

/**
 * Tests the core Drupal\file\Plugin\views\field\Extension handler.
 */
class ExtensionViewsFieldTest extends ViewUnitTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('file', 'file_test_views', 'user');

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('file_extension_view');

  public static function getInfo() {
    return array(
      'name' => 'Field: File extension',
      'description' => 'Test the core Drupal\file\Plugin\views\field\Extension handler.',
      'group' => 'Views Handlers',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    ViewTestData::createTestViews(get_class($this), array('file_test_views'));
  }

  /**
   * {@inheritdoc}
   */
  protected function dataSet() {
    $data = parent::dataSet();
    $data[0]['name'] = 'file.png';
    $data[1]['name'] = 'file.tar';
    $data[2]['name'] = 'file.tar.gz';
    $data[3]['name'] = 'file';

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  protected function viewsData() {
    $data = parent::viewsData();
    $data['views_test_data']['name']['field']['id'] = 'file_extension';
    $data['views_test_data']['name']['real field'] = 'name';

    return $data;
  }

  /**
   * Tests file extension views field handler extension_detect_tar option.
   */
  public function testFileExtensionTarOption() {
    $view = Views::getView('file_extension_view');
    $view->setDisplay();

    $this->executeView($view);

    // Test without the tar option.
    $this->assertEqual($view->field['name']->advancedRender($view->result[0]), 'png');
    $this->assertEqual($view->field['name']->advancedRender($view->result[1]), 'tar');
    $this->assertEqual($view->field['name']->advancedRender($view->result[2]), 'gz');
    $this->assertEqual($view->field['name']->advancedRender($view->result[3]), '');
    // Test with the tar option.
    $view->field['name']->options['extension_detect_tar'] = TRUE;
    $this->assertEqual($view->field['name']->advancedRender($view->result[0]), 'png');
    $this->assertEqual($view->field['name']->advancedRender($view->result[1]), 'tar');
    $this->assertEqual($view->field['name']->advancedRender($view->result[2]), 'tar.gz');
    $this->assertEqual($view->field['name']->advancedRender($view->result[3]), '');
  }

}

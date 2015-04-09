<?php

/**
 * @file
 * Contains \Drupal\file\Tests\Views\ExtensionViewsFieldTest.
 */

namespace Drupal\file\Tests\Views;

use Drupal\file\Entity\File;
use Drupal\views\Views;
use Drupal\views\Tests\ViewUnitTestBase;
use Drupal\views\Tests\ViewTestData;

/**
 * Tests the core Drupal\file\Plugin\views\field\Extension handler.
 *
 * @group file
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

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    ViewTestData::createTestViews(get_class($this), array('file_test_views'));

    $this->installEntitySchema('file');

    file_put_contents('public://file.png', '');
    File::create([
      'uri' => 'public://file.png',
      'filename' => 'file.png',
    ])->save();

    file_put_contents('public://file.tar', '');
    File::create([
      'uri' => 'public://file.tar',
      'filename' => 'file.tar',
    ])->save();

    file_put_contents('public://file.tar.gz', '');
    File::create([
      'uri' => 'public://file.tar.gz',
      'filename' => 'file.tar.gz',
    ])->save();

    file_put_contents('public://file', '');
    File::create([
      'uri' => 'public://file',
      'filename' => 'file',
    ])->save();
  }

  /**
   * Tests file extension views field handler extension_detect_tar option.
   */
  public function testFileExtensionTarOption() {
    $view = Views::getView('file_extension_view');
    $view->setDisplay();
    $this->executeView($view);

    // Test without the tar option.
    $this->assertEqual($view->field['extension']->advancedRender($view->result[0]), 'png');
    $this->assertEqual($view->field['extension']->advancedRender($view->result[1]), 'tar');
    $this->assertEqual($view->field['extension']->advancedRender($view->result[2]), 'gz');
    $this->assertEqual($view->field['extension']->advancedRender($view->result[3]), '');
    // Test with the tar option.

    $view = Views::getView('file_extension_view');
    $view->setDisplay();
    $view->initHandlers();

    $view->field['extension']->options['settings']['extension_detect_tar'] = TRUE;
    $this->executeView($view);

    $this->assertEqual($view->field['extension']->advancedRender($view->result[0]), 'png');
    $this->assertEqual($view->field['extension']->advancedRender($view->result[1]), 'tar');
    $this->assertEqual($view->field['extension']->advancedRender($view->result[2]), 'tar.gz');
    $this->assertEqual($view->field['extension']->advancedRender($view->result[3]), '');
  }

}

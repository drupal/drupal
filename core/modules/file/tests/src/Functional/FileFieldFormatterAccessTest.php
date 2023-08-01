<?php

namespace Drupal\Tests\file\Functional;

/**
 * Tests file formatter access.
 * @group file
 */
class FileFieldFormatterAccessTest extends FileFieldTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node', 'file', 'field_ui', 'file_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the custom access handler is invoked.
   */
  public function testFileAccessHandler() {
    $type_name = 'article';
    $field_name = $this->randomMachineName();
    $this->createFileField($field_name, 'node', $type_name);
    \Drupal::state()->set('file_test_alternate_access_handler', TRUE);
    \Drupal::entityTypeManager()->clearCachedDefinitions();
    $test_file = $this->getTestFile('text');
    $nid = $this->uploadNodeFile($test_file, $field_name, $type_name);
    $this->drupalGet('node/' . $nid);
    $this->assertTrue(\Drupal::state()->get('file_access_formatter_check', FALSE));
  }

}

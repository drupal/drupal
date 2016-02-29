<?php

/**
 * @file
 * Contains \Drupal\node\Tests\Migrate\d7\NodeBuilderTest.
 */

namespace Drupal\node\Tests\Migrate\d7;

use Drupal\migrate\Entity\Migration;
use Drupal\migrate_drupal\Tests\d7\MigrateDrupal7TestBase;

/**
 * Tests the d7_node builder.
 *
 * @group node
 */
class NodeBuilderTest extends MigrateDrupal7TestBase {

  public static $modules = ['node'];

  public function testBuilder() {
    $process = Migration::load('d7_node__test_content_type')->getProcess();
    $this->assertIdentical('field_boolean', $process['field_boolean'][0]['source']);
    $this->assertIdentical('field_email', $process['field_email'][0]['source']);
    $this->assertIdentical('field_phone', $process['field_phone'][0]['source']);
    $this->assertIdentical('field_date', $process['field_date'][0]['source']);
    $this->assertIdentical('field_date_with_end_time', $process['field_date_with_end_time'][0]['source']);
    $this->assertIdentical('field_file', $process['field_file'][0]['source']);
    $this->assertIdentical('field_float', $process['field_float'][0]['source']);
    $this->assertIdentical('field_images', $process['field_images'][0]['source']);
    $this->assertIdentical('field_integer', $process['field_integer'][0]['source']);
    $this->assertIdentical('field_link', $process['field_link'][0]['source']);
    $this->assertIdentical('field_text_list', $process['field_text_list'][0]['source']);
    $this->assertIdentical('field_integer_list', $process['field_integer_list'][0]['source']);
    $this->assertIdentical('field_long_text', $process['field_long_text'][0]['source']);
    $this->assertIdentical('field_term_reference', $process['field_term_reference'][0]['source']);
    $this->assertIdentical('field_text', $process['field_text'][0]['source']);
  }

}

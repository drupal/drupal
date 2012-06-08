<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Common\AttributesUnitTest.
 */

namespace Drupal\system\Tests\Common;

use Drupal\simpletest\UnitTestBase;

/**
 * Tests the drupal_attributes() functionality.
 */
class AttributesUnitTest extends UnitTestBase {
  public static function getInfo() {
    return array(
      'name' => 'HTML Attributes',
      'description' => 'Tests the drupal_attributes() functionality.',
      'group' => 'Common',
    );
  }

  /**
   * Tests that drupal_html_class() cleans the class name properly.
   */
  function testDrupalAttributes() {
    // Verify that special characters are HTML encoded.
    $this->assertIdentical(drupal_attributes(array('title' => '&"\'<>')), ' title="&amp;&quot;&#039;&lt;&gt;"', t('HTML encode attribute values.'));

    // Verify multi-value attributes are concatenated with spaces.
    $attributes = array('class' => array('first', 'last'));
    $this->assertIdentical(drupal_attributes(array('class' => array('first', 'last'))), ' class="first last"', t('Concatenate multi-value attributes.'));

    // Verify empty attribute values are rendered.
    $this->assertIdentical(drupal_attributes(array('alt' => '')), ' alt=""', t('Empty attribute value #1.'));
    $this->assertIdentical(drupal_attributes(array('alt' => NULL)), ' alt=""', t('Empty attribute value #2.'));

    // Verify multiple attributes are rendered.
    $attributes = array(
      'id' => 'id-test',
      'class' => array('first', 'last'),
      'alt' => 'Alternate',
    );
    $this->assertIdentical(drupal_attributes($attributes), ' id="id-test" class="first last" alt="Alternate"', t('Multiple attributes.'));

    // Verify empty attributes array is rendered.
    $this->assertIdentical(drupal_attributes(array()), '', t('Empty attributes array.'));
  }
}

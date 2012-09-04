<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Common\AttributesUnitTest.
 */

namespace Drupal\system\Tests\Common;

use Drupal\Core\Template\Attribute;
use Drupal\simpletest\UnitTestBase;

/**
 * Tests the Drupal\Core\Template\Attribute functionality.
 */
class AttributesUnitTest extends UnitTestBase {
  public static function getInfo() {
    return array(
      'name' => 'HTML Attributes',
      'description' => 'Tests the Drupal\Core\Template\Attribute functionality.',
      'group' => 'Common',
    );
  }

  /**
   * Tests that drupal_html_class() cleans the class name properly.
   */
  function testDrupalAttributes() {
    // Verify that special characters are HTML encoded.
    $this->assertIdentical((string) new Attribute(array('title' => '&"\'<>')), ' title="&amp;&quot;&#039;&lt;&gt;"', t('HTML encode attribute values.'));

    // Verify multi-value attributes are concatenated with spaces.
    $attributes = array('class' => array('first', 'last'));
    $this->assertIdentical((string) new Attribute(array('class' => array('first', 'last'))), ' class="first last"', t('Concatenate multi-value attributes.'));

    // Verify empty attribute values are rendered.
    $this->assertIdentical((string) new Attribute(array('alt' => '')), ' alt=""', t('Empty attribute value #1.'));
    $this->assertIdentical((string) new Attribute(array('alt' => NULL)), ' alt=""', t('Empty attribute value #2.'));

    // Verify multiple attributes are rendered.
    $attributes = array(
      'id' => 'id-test',
      'class' => array('first', 'last'),
      'alt' => 'Alternate',
    );
    $this->assertIdentical((string) new Attribute($attributes), ' id="id-test" class="first last" alt="Alternate"', t('Multiple attributes.'));

    // Verify empty attributes array is rendered.
    $this->assertIdentical((string) new Attribute(array()), '', t('Empty attributes array.'));
  }
}

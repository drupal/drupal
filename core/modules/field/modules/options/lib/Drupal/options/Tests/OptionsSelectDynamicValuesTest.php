<?php

/**
 * @file
 * Definition of Drupal\options\OptionsWidgetsTest.
 */

namespace Drupal\options\Tests;

use ListDynamicValuesTestCase;

/**
 * Test an options select on a list field with a dynamic allowed values function.
 */
class OptionsSelectDynamicValuesTest extends ListDynamicValuesTestCase {
  public static function getInfo() {
    return array(
      'name' => 'Options select dynamic values',
      'description' => 'Test an options select on a list field with a dynamic allowed values function.',
      'group' => 'Field types',
    );
  }

  /**
   * Tests the 'options_select' widget (single select).
   */
  function testSelectListDynamic() {
    // Create an entity.
    $this->entity->is_new = TRUE;
    field_test_entity_save($this->entity);
    // Create a web user.
    $web_user = $this->drupalCreateUser(array('access field_test content', 'administer field_test content'));
    $this->drupalLogin($web_user);

    // Display form.
    $this->drupalGet('test-entity/manage/' . $this->entity->ftid . '/edit');
    $options = $this->xpath('//select[@id="edit-test-list-und"]/option');
    $this->assertEqual(count($options), count($this->test) + 1);
    foreach ($options as $option) {
      $value = (string) $option['value'];
      if ($value != '_none') {
        $this->assertTrue(array_search($value, $this->test));
      }
    }
  }
}

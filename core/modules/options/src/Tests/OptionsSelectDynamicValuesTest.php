<?php

/**
 * @file
 * Contains \Drupal\options\Tests\OptionsSelectDynamicValuesTest.
 */

namespace Drupal\options\Tests;

/**
 * Tests an options select with a dynamic allowed values function.
 *
 * @group options
 */
class OptionsSelectDynamicValuesTest extends OptionsDynamicValuesTestBase {
  /**
   * Tests the 'options_select' widget (single select).
   */
  function testSelectListDynamic() {
    // Create an entity.
    $this->entity->save();

    // Create a web user.
    $web_user = $this->drupalCreateUser(array('view test entity', 'administer entity_test content'));
    $this->drupalLogin($web_user);

    // Display form.
    $this->drupalGet('entity_test_rev/manage/' . $this->entity->id());
    $options = $this->xpath('//select[@id="edit-test-options"]/option');
    $this->assertEqual(count($options), count($this->test) + 1);
    foreach ($options as $option) {
      $value = (string) $option['value'];
      if ($value != '_none') {
        $this->assertTrue(array_search($value, $this->test));
      }
    }
  }
}

<?php

namespace Drupal\Tests\options\Functional;

/**
 * Tests an options select with a dynamic allowed values function.
 *
 * @group options
 */
class OptionsSelectDynamicValuesTest extends OptionsDynamicValuesTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the 'options_select' widget (single select).
   */
  public function testSelectListDynamic() {
    // Create an entity.
    $this->entity->save();

    // Create a web user.
    $web_user = $this->drupalCreateUser([
      'view test entity',
      'administer entity_test content',
    ]);
    $this->drupalLogin($web_user);

    // Display form.
    $this->drupalGet('entity_test_rev/manage/' . $this->entity->id() . '/edit');
    $options = $this->xpath('//select[@id="edit-test-options"]/option');
    $options_expected_count = count($this->test) + 1;
    $this->assertCount($options_expected_count, $options);
    foreach ($options as $option) {
      $value = $option->getValue();
      if ($value != '_none') {
        $this->assertContains($value, $this->test);
      }
    }
  }

}

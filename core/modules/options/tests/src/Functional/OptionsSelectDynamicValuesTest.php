<?php

declare(strict_types=1);

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
  public function testSelectListDynamic(): void {
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
    $options = $this->assertSession()->selectExists('edit-test-options')->findAll('css', 'option');
    $this->assertCount(count($this->test) + 1, $options);
    foreach ($options as $option) {
      $value = $option->getValue();
      if ($value != '_none') {
        $this->assertContains($value, $this->test);
      }
    }
  }

}

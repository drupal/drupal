<?php

namespace Drupal\Tests\system\Functional\Form;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests forms using #config_target.
 *
 * @group Form
 */
class ConfigTargetTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['form_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests #config_target where #tree is set to TRUE.
   */
  public function testTree(): void {
    $this->drupalGet('/form-test/tree-config-target');
    $page = $this->getSession()->getPage();
    $page->fillField('Favorite', '');
    $page->pressButton('Save configuration');
    $assert_session = $this->assertSession();
    $assert_session->statusMessageContains('This value should not be blank.', 'error');
    $assert_session->elementAttributeExists('named', ['field', 'Favorite'], 'aria-invalid');
    $assert_session->elementAttributeNotExists('named', ['field', 'Nemesis'], 'aria-invalid');
  }

  /**
   * Tests #config_target with an incorrect key.
   */
  public function testIncorrectKey(): void {
    $this->drupalGet('/form-test/incorrect-config-target');
    $page = $this->getSession()->getPage();
    $page->pressButton('Save configuration');
    $assert_session = $this->assertSession();
    $assert_session->statusMessageContains('\'does_not_exist\' is not a supported key.', 'error');
    $assert_session->elementAttributeExists('named', ['field', 'Missing key'], 'aria-invalid');
  }

  /**
   * Tests #config_target where there is not a 1:1 property to element.
   */
  public function testNested(): void {
    $most_favorite_fruit = 'Apple';
    $second_favorite_fruit = 'Kiwi';
    $nemesis_vegetable = 'Cauliflower';

    $this->drupalGet('/form-test/nested-config-target');
    $page = $this->getSession()->getPage();

    // Both invalid.
    $page->fillField('First choice', '');
    $page->fillField('Second choice', '');
    $page->pressButton('Save configuration');
    $assert_session = $this->assertSession();
    $assert_session->statusMessageContains('This value should not be blank.', 'error');
    $assert_session->elementAttributeNotExists('css', '#edit-favorites', 'aria-invalid');
    $assert_session->elementAttributeExists('named', ['field', 'First choice'], 'aria-invalid');
    $assert_session->elementAttributeExists('named', ['field', 'Second choice'], 'aria-invalid');

    // Second invalid.
    $page->fillField('First choice', $most_favorite_fruit);
    $page->fillField('Second choice', '');
    $page->pressButton('Save configuration');
    $assert_session = $this->assertSession();
    $assert_session->statusMessageContains('This value should not be blank.', 'error');
    $assert_session->elementAttributeNotExists('css', '#edit-favorites', 'aria-invalid');
    $assert_session->elementAttributeNotExists('named', ['field', 'First choice'], 'aria-invalid');
    $assert_session->elementAttributeExists('named', ['field', 'Second choice'], 'aria-invalid');

    // First invalid.
    $page->fillField('First choice', '');
    $page->fillField('Second choice', $second_favorite_fruit);
    $page->pressButton('Save configuration');
    $assert_session = $this->assertSession();
    $assert_session->statusMessageContains('This value should not be blank.', 'error');
    $assert_session->elementAttributeNotExists('css', '#edit-favorites', 'aria-invalid');
    $assert_session->elementAttributeExists('named', ['field', 'First choice'], 'aria-invalid');
    $assert_session->elementAttributeNotExists('named', ['field', 'Second choice'], 'aria-invalid');

    // Both valid.
    $page->fillField('First choice', $most_favorite_fruit);
    $page->fillField('Second choice', $second_favorite_fruit);
    $page->pressButton('Save configuration');
    $assert_session->statusMessageContains('The configuration options have been saved.', 'status');

    $this->assertSame([
      'favorite_fruits' => [
        $most_favorite_fruit,
        $second_favorite_fruit,
      ],
      'favorite_vegetable' => 'Potato',
      'nemesis_vegetable' => '',
    ], $this->config('form_test.object')->getRawData());

    // Now enter a nemesis vegetable too.
    $this->drupalGet('/form-test/nested-config-target');
    $page->fillField('First choice', $most_favorite_fruit);
    $page->fillField('Second choice', $second_favorite_fruit);
    $page->fillField('Nemesis', $nemesis_vegetable);
    $page->pressButton('Save configuration');

    // A new validation error has appeared, on the conditionally displayed
    // "I could not live without" form field — this field must be filled out if
    // all others are.
    $assert_session->statusMessageContains('The value you selected is not a valid choice.', 'error');
    $assert_session->elementAttributeExists('named', ['field', 'I could not live without'], 'aria-invalid');
    $page->fillField('First choice', $most_favorite_fruit);
    $page->fillField('Second choice', $second_favorite_fruit);
    $page->fillField('Nemesis', $nemesis_vegetable);
    $page->fillField('I could not live without', 'fruits');
    $page->pressButton('Save configuration');
    $assert_session->statusMessageContains('The configuration options have been saved.', 'status');

    $this->assertSame([
      'favorite_fruits' => [
        $most_favorite_fruit,
        $second_favorite_fruit,
      ],
      'favorite_vegetable' => 'Potato',
      'nemesis_vegetable' => $nemesis_vegetable,
      'could_not_live_without' => 'fruits',
    ], $this->config('form_test.object')->getRawData());

    // Remove the nemesis vegetable; this should cause the deletion of the
    // `could_not_live_without` property path in the Config object.
    $this->drupalGet('/form-test/nested-config-target');
    $page->fillField('First choice', $most_favorite_fruit);
    $page->fillField('Second choice', $second_favorite_fruit);
    $page->fillField('Nemesis', '');
    $page->pressButton('Save configuration');
    $assert_session->statusMessageContains('The configuration options have been saved.', 'status');

    $this->assertSame([
      'favorite_fruits' => [
        $most_favorite_fruit,
        $second_favorite_fruit,
      ],
      'favorite_vegetable' => 'Potato',
      'nemesis_vegetable' => '',
    ], $this->config('form_test.object')->getRawData());
  }

}

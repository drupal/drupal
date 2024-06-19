<?php

declare(strict_types=1);

namespace Drupal\Tests\layout_builder\Functional;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityFormMode;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests Layout Builder forms.
 *
 * @group layout_builder
 */
class LayoutBuilderFormModeTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'entity_test',
    'layout_builder',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Set up a field with a validation constraint.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'foo',
      'entity_type' => 'entity_test',
      'type' => 'string',
    ]);
    $field_storage->save();

    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'entity_test',
      // Expecting required value.
      'required' => TRUE,
    ])->save();

    // Enable layout builder custom layouts.
    LayoutBuilderEntityViewDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
      'status' => TRUE,
    ])
      ->enable()
      ->enableLayoutBuilder()
      ->setOverridable()
      ->save();

    // Add the form mode and show the field with a constraint.
    EntityFormMode::create([
      'id' => 'entity_test.layout_builder',
      'label' => 'Layout Builder',
      'targetEntityType' => 'entity_test',
    ])->save();
    EntityFormDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'layout_builder',
      'status' => TRUE,
    ])
      ->setComponent('foo', [
        'type' => 'string_textfield',
      ])
      ->save();

    $this->drupalLogin($this->drupalCreateUser([
      'view test entity',
      'configure any layout',
      'configure all entity_test entity_test layout overrides',
    ]));

    EntityTest::create()->setName($this->randomMachineName())->save();
  }

  /**
   * Tests that the 'Discard changes' button skips validation and ignores input.
   */
  public function testDiscardValidation(): void {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    // When submitting the form normally, a validation error should be shown.
    $this->drupalGet('entity_test/1/layout');
    $assert_session->fieldExists('foo[0][value]');
    $assert_session->elementAttributeContains('named', ['field', 'foo[0][value]'], 'required', 'required');
    $page->pressButton('Save layout');
    $assert_session->pageTextContains('foo field is required.');

    // When Discarding changes, a validation error will not be shown.
    // Reload the form for fresh state.
    $this->drupalGet('entity_test/1/layout');
    $page->pressButton('Discard changes');
    $assert_session->pageTextNotContains('foo field is required.');
    $assert_session->addressEquals('entity_test/1/layout/discard-changes');

    // Submit the form to ensure no invalid form state retained.
    $page->pressButton('Confirm');
    $assert_session->pageTextContains('The changes to the layout have been discarded.');
  }

}

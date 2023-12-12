<?php

declare(strict_types=1);

namespace Drupal\FunctionalJavascriptTests\EntityReference;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\field\Traits\EntityReferenceFieldCreationTrait;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Tests the output of entity reference autocomplete widgets.
 *
 * @group entity_reference
 */
class EntityReferenceAutocompleteWidgetTest extends WebDriverTestBase {

  use ContentTypeCreationTrait;
  use EntityReferenceFieldCreationTrait;
  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'field_ui',
    'entity_test',
    'entity_reference_test',
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

    // Create a Content type and two test nodes.
    $this->createContentType(['type' => 'page']);
    $this->createNode(['title' => 'Test page']);
    $this->createNode(['title' => 'Page test']);

    $user = $this->drupalCreateUser([
      'access content',
      'create page content',
    ]);
    $this->drupalLogin($user);
  }

  /**
   * Tests that the default autocomplete widget return the correct results.
   */
  public function testEntityReferenceAutocompleteWidget() {
    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');

    // Create an entity reference field and use the default 'CONTAINS' match
    // operator.
    $field_name = 'field_test';
    $this->createEntityReferenceField('node', 'page', $field_name, $field_name, 'node', 'default', ['target_bundles' => ['page'], 'sort' => ['field' => 'title', 'direction' => 'DESC']]);
    $form_display = $display_repository->getFormDisplay('node', 'page');
    $form_display->setComponent($field_name, [
      'type' => 'entity_reference_autocomplete',
      'settings' => [
        'match_operator' => 'CONTAINS',
      ],
    ]);
    // To satisfy config schema, the size setting must be an integer, not just
    // a numeric value. See https://www.drupal.org/node/2885441.
    $this->assertIsInt($form_display->getComponent($field_name)['settings']['size']);
    $form_display->save();
    $this->assertIsInt($form_display->getComponent($field_name)['settings']['size']);

    // Visit the node add page.
    $this->drupalGet('node/add/page');
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $autocomplete_field = $assert_session->waitForElement('css', '[name="' . $field_name . '[0][target_id]"].ui-autocomplete-input');
    $autocomplete_field->setValue('Test');
    $this->getSession()->getDriver()->keyDown($autocomplete_field->getXpath(), ' ');
    $assert_session->waitOnAutocomplete();

    $results = $page->findAll('css', '.ui-autocomplete li');

    $this->assertCount(2, $results);
    $assert_session->pageTextContains('Test page');
    $assert_session->pageTextContains('Page test');

    // Now switch the autocomplete widget to the 'STARTS_WITH' match operator.
    $display_repository->getFormDisplay('node', 'page')
      ->setComponent($field_name, [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'STARTS_WITH',
        ],
      ])
      ->save();

    $this->drupalGet('node/add/page');

    $this->doAutocomplete($field_name);

    $results = $page->findAll('css', '.ui-autocomplete li');

    $this->assertCount(1, $results);
    $assert_session->pageTextContains('Test page');
    $assert_session->pageTextNotContains('Page test');

    // Change the size of the result set.
    $display_repository->getFormDisplay('node', 'page')
      ->setComponent($field_name, [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_limit' => 1,
        ],
      ])
      ->save();

    $this->drupalGet('node/add/page');

    $this->doAutocomplete($field_name);
    $results = $page->findAll('css', '.ui-autocomplete li');

    $this->assertCount(1, $results);
    $assert_session->pageTextContains('Test page');
    $assert_session->pageTextNotContains('Page test');

    // Change the size of the result set via the UI.
    $this->drupalLogin($this->createUser([
      'access content',
      'administer content types',
      'administer node fields',
      'administer node form display',
      'create page content',
    ]));
    $this->drupalGet('/admin/structure/types/manage/page/form-display');
    $assert_session->pageTextContains('Autocomplete suggestion list size: 1');
    // Click on the widget settings button to open the widget settings form.
    $this->submitForm([], $field_name . "_settings_edit");
    $this->assertSession()->waitForElement('css', sprintf('[name="fields[%s][settings_edit_form][settings][match_limit]"]', $field_name));
    $page->fillField('Number of results', 2);
    $page->pressButton('Save');
    $assert_session->pageTextContains('Your settings have been saved.');
    $assert_session->pageTextContains('Autocomplete suggestion list size: 2');

    $this->drupalGet('node/add/page');

    $this->doAutocomplete($field_name);
    $this->assertCount(2, $page->findAll('css', '.ui-autocomplete li'));
  }

  /**
   * Tests that the autocomplete widget knows about the entity its attached to.
   *
   * Ensures that the entity the autocomplete widget stores the entity it is
   * rendered on, and is available in the autocomplete results' AJAX request.
   */
  public function testEntityReferenceAutocompleteWidgetAttachedEntity() {
    $user = $this->drupalCreateUser([
      'administer entity_test content',
    ]);
    $this->drupalLogin($user);

    $field_name = 'field_test';
    $this->createEntityReferenceField('entity_test', 'entity_test', $field_name, $field_name, 'entity_test', 'entity_test_all_except_host', ['target_bundles' => ['entity_test']]);
    $form_display = EntityFormDisplay::load('entity_test.entity_test.default');
    $form_display->setComponent($field_name, [
      'type' => 'entity_reference_autocomplete',
      'settings' => [
        'match_operator' => 'CONTAINS',
      ],
    ]);
    $form_display->save();

    $host = EntityTest::create(['name' => 'dark green']);
    $host->save();
    EntityTest::create(['name' => 'dark blue'])->save();

    $this->drupalGet($host->toUrl('edit-form'));

    // Trigger the autocomplete.
    $page = $this->getSession()->getPage();
    $autocomplete_field = $page->findField($field_name . '[0][target_id]');
    $autocomplete_field->setValue('dark');
    $this->getSession()->getDriver()->keyDown($autocomplete_field->getXpath(), ' ');
    $this->assertSession()->waitOnAutocomplete();

    // Check the autocomplete results.
    $results = $page->findAll('css', '.ui-autocomplete li');
    $this->assertCount(1, $results);
    $this->assertSession()->elementTextNotContains('css', '.ui-autocomplete li', 'dark green');
    $this->assertSession()->elementTextContains('css', '.ui-autocomplete li', 'dark blue');
  }

  /**
   * Executes an autocomplete on a given field and waits for it to finish.
   *
   * @param string $field_name
   *   The field name.
   */
  protected function doAutocomplete($field_name) {
    $autocomplete_field = $this->getSession()->getPage()->findField($field_name . '[0][target_id]');
    $autocomplete_field->setValue('Test');
    $this->getSession()->getDriver()->keyDown($autocomplete_field->getXpath(), ' ');
    $this->assertSession()->waitOnAutocomplete();
  }

}

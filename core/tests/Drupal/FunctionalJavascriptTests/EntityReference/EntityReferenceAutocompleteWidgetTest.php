<?php

namespace Drupal\FunctionalJavascriptTests\EntityReference;

use Behat\Mink\Element\NodeElement;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;

/**
 * Tests the output of entity reference autocomplete widgets.
 *
 * @group entity_reference
 */
class EntityReferenceAutocompleteWidgetTest extends WebDriverTestBase {

  use ContentTypeCreationTrait;
  use EntityReferenceTestTrait;
  use NodeCreationTrait;
  use TaxonomyTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'taxonomy', 'field_ui', 'drupal_autocomplete_test'];

  /**
   * The test vocabulary.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $vocabulary;

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
    $this->createNode(['title' => 'Guess me']);

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
    $selection_handler_settings = [
      'target_bundles' => ['page'],
      'sort' => [
        'field' => 'title',
        'direction' => 'DESC',
      ],
    ];
    $this->createEntityReferenceField('node', 'page', $field_name, $field_name, 'node', 'default', $selection_handler_settings);
    $form_display = $display_repository->getFormDisplay('node', 'page');
    $form_display->setComponent($field_name, [
      'type' => 'entity_reference_autocomplete',
      'settings' => [
        'match_operator' => 'CONTAINS',
      ],
    ]);

    $display_repository->getViewDisplay('node', 'page')
      ->setComponent($field_name, [
          'type' => 'entity_reference_label',
          'weight' => 10,
        ])
      ->save();

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

    // Test that an entity reference is saved by providing just the title,
    // without the addition of the entity ID in parentheses.
    $this->drupalGet('node/add/page');
    $page->fillField('Title', 'Testing that the autocomplete field does not require the entity id');
    $autocomplete_field = $assert_session->waitForElement('css', '[name="' . $field_name . '[0][target_id]"].ui-autocomplete-input');
    $autocomplete_field->setValue('Guess me');
    $page->pressButton('Save');
    $assert_session->elementExists('css', '[href$="/node/3"]:contains("Guess me")');
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

  /**
   * Test that spaces and commas work properly with autocomplete fields.
   */
  public function testSeparators() {
    $this->createTagsFieldOnPage();

    $term_commas = 'I,love,commas';
    $term_spaces = 'Just a fan of spaces';
    $term_commas_spaces = 'I dig both commas and spaces, apparently';

    $this->createTerm($this->vocabulary, ['name' => $term_commas]);
    $this->createTerm($this->vocabulary, ['name' => $term_spaces]);
    $this->createTerm($this->vocabulary, ['name' => $term_commas_spaces]);

    $this->drupalGet('node/add/page');
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $autocomplete_field = $assert_session->waitForElement('css', '[name="taxonomy_reference[target_id]"]');
    $autocomplete_field->setValue('a');
    $this->getSession()->getDriver()->keyDown($autocomplete_field->getXpath(), ' ');
    $assert_session->waitOnAutocomplete();

    $results = $page->findAll('css', '.ui-autocomplete li');
    $this->assertCount(3, $results);

    $assert_session->elementExists('css', '.ui-autocomplete li:contains("' . $term_commas_spaces . '")')->click();
    $assert_session->pageTextNotContains($term_commas);
    $assert_session->pageTextNotContains($term_spaces);
    $current_value = $autocomplete_field->getValue();
    $this->assertStringContainsString($term_commas_spaces, $current_value);
  }

  /**
   * Create a tags field on the Page content type.
   */
  public function createTagsFieldOnPage() {
    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');

    $field_name = 'taxonomy_reference';
    $vocabulary = $this->createVocabulary();
    $this->vocabulary = $vocabulary;

    $handler_settings = [
      'target_bundles' => [
        $vocabulary->id() => $vocabulary->id(),
      ],
      'auto_create' => TRUE,
    ];
    $this->createEntityReferenceField('node', 'page', $field_name, 'Tags', 'taxonomy_term', 'default', $handler_settings, FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);
    $display_repository->getFormDisplay('node', 'page')
      ->setComponent($field_name, [
        'type' => 'entity_reference_autocomplete_tags',
        'settings' => [
          'match_operator' => 'CONTAINS',
        ],
      ])
      ->save();
    $display_repository->getViewDisplay('node', 'page')
      ->setComponent($field_name, [
        'type' => 'entity_reference_label',
        'weight' => 10,
      ])
      ->save();
  }

  /**
   * Tests aria configuration and screenreader behavior.
   */
  public function testScreenreaderAndAria() {
    $this->createTagsFieldOnPage();
    $this->createTerm($this->vocabulary, ['name' => 'First']);
    $this->createTerm($this->vocabulary, ['name' => 'Second']);
    $this->createTerm($this->vocabulary, ['name' => 'Third']);
    $this->createTerm($this->vocabulary, ['name' => 'Fourth']);
    $this->createTerm($this->vocabulary, ['name' => 'Fifth']);
    $this->createTerm($this->vocabulary, ['name' => 'Sixth']);
    $this->createTerm($this->vocabulary, ['name' => 'Seventh']);
    $this->createTerm($this->vocabulary, ['name' => 'Eighth']);
    $this->createTerm($this->vocabulary, ['name' => 'Ninth']);
    $this->createTerm($this->vocabulary, ['name' => 'Tenth']);
    $this->createTerm($this->vocabulary, ['name' => 'Eleventh']);
    $this->createTerm($this->vocabulary, ['name' => 'Twelfth']);
    $this->createTerm($this->vocabulary, ['name' => 'Thirteenth']);
    $this->createTerm($this->vocabulary, ['name' => 'Fourteenth']);
    $this->createTerm($this->vocabulary, ['name' => 'Fifteenth']);
    $this->createTerm($this->vocabulary, ['name' => 'Sixteenth']);
    $this->createTerm($this->vocabulary, ['name' => 'Seventeenth']);
    $this->createTerm($this->vocabulary, ['name' => 'Eighteenth']);
    $this->createTerm($this->vocabulary, ['name' => 'Nineteenth']);
    $this->createTerm($this->vocabulary, ['name' => 'Twentieth']);
    $this->createTerm($this->vocabulary, ['name' => 'Fancy']);
    $this->createTerm($this->vocabulary, ['name' => 'Fun']);
    $this->createTerm($this->vocabulary, ['name' => 'Freaky']);
    $this->createTerm($this->vocabulary, ['name' => 'Forgettable']);
    $this->createTerm($this->vocabulary, ['name' => 'Fast']);
    $this->createTerm($this->vocabulary, ['name' => 'Furious']);
    $this->createTerm($this->vocabulary, ['name' => 'Falafel']);
    $this->createTerm($this->vocabulary, ['name' => 'Feral']);

    $this->drupalGet('node/add/page');
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $autocomplete_field = $assert_session->waitForElement('css', '[name="taxonomy_reference[target_id]"]');
    $this->assertEquals('list', $autocomplete_field->getAttribute('aria-autocomplete'));
    $this->assertEquals('off', $autocomplete_field->getAttribute('autocomplete'));
    $aria_owns = $autocomplete_field->getAttribute('aria-owns');
    $this->assertNotNull($aria_owns);
    $this->assertNotNull($page->find('css', "[data-drupal-autocomplete-list]#$aria_owns"));
    $hint = $this->getDescription($autocomplete_field)->getText();
    $expected_hint = 'When autocomplete results are available use up and down arrows to review and enter to select. Touch device users, explore by touch or with swipe gestures.';
    $this->assertEquals($expected_hint, $hint);
    $autocomplete_field->setValue('F');
    $assert_session->waitOnAutocomplete();
    $this->assertCount(10, $page->findAll('css', '[data-drupal-autocomplete-list] li'));
    $this->assertCount(10, $page->findAll('css', '[data-drupal-autocomplete-list] li[aria-selected="false"]'));
    $this->assertScreenreader('There are at least 10 results available. Type additional characters to refine your search.');
    $autocomplete_field->setValue('Fo');
    $this->assertScreenreader('There are 3 results available.');

    $autocomplete_field->keyDown(40);

    $this->assertScreenreader('Forgettable (24) 1 of 3 is highlighted');
    $this->assertFalse($autocomplete_field->hasAttribute('aria-describedby'));
    $this->assertCount(3, $page->findAll('css', '[data-drupal-autocomplete-list] li'));
    $this->assertCount(2, $page->findAll('css', '[data-drupal-autocomplete-list] li[aria-selected="false"]'));
    $this->assertCount(1, $page->findAll('css', '[data-drupal-autocomplete-list] li[aria-selected="true"]'));
    $active_item = $page->find('css', 'li:contains("Forgettable (24)")');
    $this->assertEquals('true', $active_item->getAttribute('aria-selected'));
    $active_item->keyDown(40);

    $this->assertScreenreader('Fourteenth (14) 2 of 3 is highlighted');
    $this->assertCount(3, $page->findAll('css', '[data-drupal-autocomplete-list] li'));
    $this->assertCount(2, $page->findAll('css', '[data-drupal-autocomplete-list] li[aria-selected="false"]'));
    $this->assertCount(1, $page->findAll('css', '[data-drupal-autocomplete-list] li[aria-selected="true"]'));
    $active_item = $page->find('css', 'li:contains("Fourteenth (14)")');
    $this->assertEquals('true', $active_item->getAttribute('aria-selected'));
  }

  /**
   * Tests the configurable options of the A11yAutocomplete class.
   *
   *  Options can be set directly on an element in two ways:
   *  - Using a data-autocomplete-(dash separated option name) attribute.
   *    ex: data-autocomplete-min-chars="2"
   *  - The data-autocomplete attribute has a JSON string with all custom
   *    options. The option properties are camel cased.
   *    ex: data-autocomplete="{"minChars": 2}"
   * Every option tested is done on an input that uses the
   * data-autocomplete={option: value}  approach, and separate input using the
   * data-autocomplete-(dash separated option name) approach.
   */
  public function testAutocompleteOptions() {
    $this->drupalGet('drupal_autocomplete/test-form');
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    // Test the minChar: option.
    /* cspell:disable */
    foreach ([
      'edit-two-minchar-data-autocomplete',
      'edit-two-minchar-separate-data-attributes',
    ] as $id) {
      /* cspell:enable */
      $input = $page->findById($id);
      $list = $this->getList($input);
      $description = $this->getDescription($input);

      // The minChar option provides additional content to screenreaders on
      // initial focus, so test for that here.
      // If an input already has a description associated
      // with it. That means the screenreader instructions must be added to the
      // description in a visually-hidden container.
      $inserted_screenreader_only_description = $description->find('css', '[data-drupal-autocomplete-assistive-hint]');
      /* cspell:disable-next-line */
      if ($id === 'edit-two-minchar-data-autocomplete') {
        // This input has a pre-existing description, so check for the visually
        // hidden supplement to the description.
        $this->assertNotNull($inserted_screenreader_only_description);
        $expected_description = 'This also tests appending minChar screenreader hints to descriptions Type 2 or more characters for results. When autocomplete results are available use up and down arrows to review and enter to select. Touch device users, explore by touch or with swipe gestures.';
        $expected_screenreader_only_description = 'Type 2 or more characters for results. When autocomplete results are available use up and down arrows to review and enter to select. Touch device users, explore by touch or with swipe gestures.';
        $this->assertEquals($expected_description, $description->getText());
        $this->assertEquals($expected_screenreader_only_description, $inserted_screenreader_only_description->getText());
      }
      /* cspell:disable-next-line */
      if ($id === 'edit-two-minchar-separate-data-attributes') {
        // This input does not have a pre-existing description, so a visually
        // hidden one is added solely for assistive tech.
        $this->assertNull($inserted_screenreader_only_description);
        $this->assertTrue($description->hasClass('visually-hidden'));
        $expected_description = 'Type 2 or more characters for results. When autocomplete results are available use up and down arrows to review and enter to select. Touch device users, explore by touch or with swipe gestures.';
        $this->assertEquals($expected_description, $description->getText());
      }

      $this->setAutocompleteValue($input, $id, 'U', FALSE);
      $this->assertCount(0, $list->findAll('css', 'li'));
      $this->setAutocompleteValue($input, $id, 'Un');
      $this->assertCount(3, $list->findAll('css', 'li'), $id);

      /* cspell:disable-next-line */
      if ($id === 'edit-two-minchar-data-autocomplete') {
        $inserted_screenreader_only_description = $description->find('css', '[data-drupal-autocomplete-assistive-hint]');
        $this->assertNull($inserted_screenreader_only_description);
        $expected_description = 'This also tests appending minChar screenreader hints to descriptions';
        $this->assertEquals($expected_description, $description->getText());
      }

      /* cspell:disable-next-line */
      if ($id === 'edit-two-minchar-separate-data-attributes') {
        $this->assertFalse($input->hasAttribute('aria-describedby'));
      }

      // Reset value to ensure the next input isn't obscured.
      $this->setAutocompleteValue($input, $id, ' ', FALSE);
    }

    // Test the firstCharDenylist option.
    foreach ([
      'edit-denylist-data-autocomplete',
      'edit-denylist-separate-data-attributes',
    ] as $id) {
      $input = $page->findById($id);
      $list = $this->getList($input);
      $this->setAutocompleteValue($input, $id, 'u', FALSE);
      $this->assertCount(0, $list->findAll('css', 'li'));
      $this->setAutocompleteValue($input, $id, 'z');
      $this->assertCount(3, $list->findAll('css', 'li'));

      // Reset value to ensure the next input isn't obscured.
      $this->setAutocompleteValue($input, $id, ' ', FALSE);
    }

    // Test setting custom classes via options.
    foreach ([
      'edit-custom-classes-data-autocomplete',
      'edit-custom-classes-separate-data-attributes',
    ] as $id) {
      $input = $page->findById($id);
      $list = $this->getList($input);
      $this->assertTrue($input->hasClass('class-added-to-input'));
      $this->assertTrue($input->hasClass('another-class-added-to-input'));
      $this->assertTrue($list->hasClass('class-added-to-ul'));
      $this->assertTrue($list->hasClass('another-class-added-to-ul'));
      $this->setAutocompleteValue($input, $id, 'z');
      $this->assertCount(3, $list->findAll('css', 'li'));
      $this->assertCount(3, $list->findAll('css', 'li.class-added-to-item'));
      $this->assertCount(3, $list->findAll('css', 'li.another-class-added-to-item'));

      // Reset value to ensure the next input isn't obscured.
      $this->setAutocompleteValue($input, $id, ' ', FALSE);
    }

    // Test cardinality: and separatorChar: options.
    foreach ([
      'edit-cardinality-separator-data-autocomplete',
      'edit-cardinality-separator-separate-data-attributes',
    ] as $id) {
      $input = $page->findById($id);
      $list = $this->getList($input);
      $this->setAutocompleteValue($input, $id, 'z');
      $this->assertCount(3, $list->findAll('css', 'li'));
      $this->setAutocompleteValue($input, $id, 'South Africa (ZA),z', FALSE);
      $this->assertCount(0, $list->findAll('css', 'li'), $id);
      $this->setAutocompleteValue($input, $id, 'South Africa (ZA)|z');
      $this->assertCount(2, $list->findAll('css', 'li'), $id);
      $this->setAutocompleteValue($input, $id, 'South Africa (ZA)|Zambia (ZM)|z', FALSE);

      // No results are available despite there being another "z" item available
      // (Zimbabwe), because cardinality is set to 2 items.
      $this->assertCount(0, $list->findAll('css', 'li'));

      // Confirm that a search for 'a' only provides 19 results, to confirm that
      // the next test is accurate.
      $this->setAutocompleteValue($input, $id, 'a');
      $this->assertCount(19, $list->findAll('css', 'li'));

      // Reset value to ensure the next input isn't obscured.
      $this->setAutocompleteValue($input, $id, ' ', FALSE);
    }

    /* cspell:disable */
    // Test the maxitems: option.
    foreach ([
      'edit-maxitems-data-autocomplete',
      'edit-maxitems-separate-data-attributes',
    ] as $id) {
      /* cspell:enable */
      $input = $page->findById($id);
      $list = $this->getList($input);
      $this->setAutocompleteValue($input, $id, 'a');
      $this->assertCount(10, $list->findAll('css', 'li'));

      // Reset value to ensure the next input isn't obscured.
      $this->setAutocompleteValue($input, $id, ' ', FALSE);
    }

    // Test the list: option, which provides a predefined list instead of a
    // a dynamic request.
    foreach ([
      'edit-preset-list-separate-data-attributes',
      'edit-preset-list-data-autocomplete',
    ] as $id) {
      $input = $page->findById($id);
      $list = $this->getList($input);
      $this->setAutocompleteValue($input, $id, 'a');

      $expected = [
        'Zebra Value',
        'Rhino Value',
        'Cheetah Value',
        'Meerkat Value',
      ];
      $list_contents = $list->findAll('css', 'li');
      $this->assertCount(4, $list_contents);
      foreach ($list_contents as $index => $list_item) {
        $this->assertEquals($expected[$index], $list_item->find('css', 'a')->getText(), $id);
      }

      $this->setAutocompleteValue($input, $id, 'h');
      $expected = [
        'Rhino Value',
        'Cheetah Value',
      ];
      $list_id = $list->getAttribute('id');

      // Wait for the new results to populate.
      $assert_session->assertNoElementAfterWait('css', "#$list_id li:nth-child(4)");
      $list_contents = $list->findAll('css', 'li');
      $this->assertCount(2, $list_contents);
      foreach ($list_contents as $index => $list_item) {
        $this->assertEquals($expected[$index], $list_item->getText());
      }
      // Reset value to ensure the next input isn't obscured.
      $this->setAutocompleteValue($input, $id, ' ', FALSE);
    }

    // Test the sort: option.
    foreach ([
      'edit-sort-data-autocomplete',
      'edit-sort-separate-data-attributes',
    ] as $id) {
      $input = $page->findById($id);
      $list = $this->getList($input);
      $this->setAutocompleteValue($input, $id, 'a');

      $expected = [
        'Cheetah Value',
        'Meerkat Value',
        'Rhino Value',
        'Zebra Value',
      ];
      $list_contents = $list->findAll('css', 'li');
      $this->assertCount(4, $list_contents);
      foreach ($list_contents as $index => $list_item) {
        $this->assertEquals($expected[$index], $list_item->find('css', 'a')->getText(), $id);
      }
      $this->setAutocompleteValue($input, $id, 'h');
      $expected = [
        'Cheetah Value',
        'Rhino Value',
      ];
      $list_id = $list->getAttribute('id');

      // Wait for the new results to populate.
      $assert_session->assertNoElementAfterWait('css', "#$list_id li:nth-child(4)");
      $list_contents = $list->findAll('css', 'li');
      $this->assertCount(2, $list_contents);
      foreach ($list_contents as $index => $list_item) {
        $this->assertEquals($expected[$index], $list_item->getText());
      }

      // Reset value to ensure the next input isn't obscured.
      $this->setAutocompleteValue($input, $id, ' ', FALSE);
    }

    // Test the displayLabels: option.
    foreach ([
      'edit-display-labels-data-autocomplete',
      'edit-display-labels-data-attributes',
    ] as $id) {
      $input = $page->findById($id);
      $list = $this->getList($input);
      $this->setAutocompleteValue($input, $id, 'a');

      $expected = [
        'Zebra Label',
        'Rhino Label',
        'Cheetah Label',
        'Meerkat Label',
      ];
      $list_contents = $list->findAll('css', 'li');
      $this->assertCount(4, $list_contents, $list->getHtml());
      foreach ($list_contents as $index => $list_item) {
        $this->assertEquals($expected[$index], $list_item->find('css', 'a')->getText(), $id);
      }
      $this->setAutocompleteValue($input, $id, 'h');
      $expected = [
        'Rhino Label',
        'Cheetah Label',
      ];
      $list_id = $list->getAttribute('id');

      // Wait for the new results to populate.
      $assert_session->assertNoElementAfterWait('css', "#$list_id li:nth-child(4)");
      $list_contents = $list->findAll('css', 'li');
      $this->assertCount(2, $list_contents, $list->getHtml());
      foreach ($list_contents as $index => $list_item) {
        $this->assertEquals($expected[$index], $list_item->getText());
      }
      // Reset value to ensure the next input isn't obscured.
      $this->setAutocompleteValue($input, $id, ' ', FALSE);
    }
  }

  /**
   * Confirms the expected message is in drupal-live-announce.
   *
   * @param string $message
   *   The message expected to be in #drupal-live-announce.
   */
  public function assertScreenreader($message) {
    // Use assertJsCondition() instead of a standard DOM assertion in order to
    // leverage wait(). With A11yAutocomplete, the updating of
    // #drupal-live-announce is intentionally delayed to prevent collision with
    // browser default screenreader announcements.
    $this->assertJsCondition('document.getElementById("drupal-live-announce").innerText.includes("' . $message . '")', 10000, "Live region did not include: $message");
  }

  /**
   * Sets the value of an autocomplete input.
   *
   * @param \Behat\Mink\Element\NodeElement $input
   *   The autocomplete input element.
   * @param string $id
   *   The id of the input element.
   * @param string $value
   *   The value to set.
   * @param bool $should_open
   *   If the autocomplete is expected to open.
   */
  public function setAutocompleteValue(NodeElement $input, $id, $value, $should_open = TRUE) {
    // Before setting a value, the the autocomplete instance must set the
    // preventCloseOnBlur property to false. This is due to setValue() blurring
    // the element to force triggering of the change event. Without
    // preventCloseOnBlur set to true, that blur event will close the suggestion
    // list moments after it is opened.
    // @see \Behat\Mink\Driver\Selenium2Driver::setValue
    $this->getSession()->executeScript('Drupal.Autocomplete.instances["' . $id . '"].preventCloseOnBlur = true');
    $input->setValue($value);
    if ($should_open) {
      $this->assertSession()->waitOnAutocomplete();
    }
    else {
      // If the autocomplete is not expected to open, wait slightly longer than
      // its delay to confirm it remained closed.
      usleep(500000);
    }
    $this->getSession()->executeScript('Drupal.Autocomplete.instances["' . $id . '"].preventCloseOnBlur = false');
  }

  /**
   * Test a form that includes shimmed and non-shimmed autocomplete inputs.
   */
  public function testPartialShimUse() {
    $this->drupalGet('drupal_autocomplete/selective-shim-form');
    $page = $this->getSession()->getPage();

    $shimmed_id = 'edit-shimmed';
    $not_shimmed_id = 'edit-not-shimmed';
    $shimmed_input = $page->findById($shimmed_id);
    $not_shimmed_input = $page->findById($not_shimmed_id);

    // Confirm shimmed input is structured as expected.
    $this->setAutocompleteValue($shimmed_input, $shimmed_id, 'c');
    $shimmed_list = $this->getList($shimmed_input);
    $shimmed_list_parent = $shimmed_list->getParent();
    $this->assertEquals('body', $shimmed_list_parent->getTagName());
    $this->assertFalse($shimmed_list_parent->hasAttribute('data-drupal-autocomplete-wrapper'));
    /* cspell:disable-next-line */
    $this->assertEquals('Cambodia (KH) Cameroon (CM) Canada (CA) Canary Islands (IC) Cape Verde (CV) Caribbean Netherlands (BQ) Cayman Islands (KY) Central African Republic (CF) Ceuta & Melilla (EA) Chad (TD) Chile (CL) China (CN) Christmas Island (CX) Clipperton Island (CP) Cocos (Keeling) Islands (CC) Colombia (CO) Comoros (KM) Congo - Brazzaville (CG) Congo - Kinshasa (CD) Cook Islands (CK)', $shimmed_list->getText());
    $this->setAutocompleteValue($shimmed_input, $shimmed_id, ' ', FALSE);

    // Confirm non-shimmed input is structured as expected.
    $this->setAutocompleteValue($not_shimmed_input, $not_shimmed_id, 'f');
    $not_shimmed_list = $this->getList($not_shimmed_input);
    $not_shimmed_list_parent = $not_shimmed_list->getParent();
    $this->assertEquals('div', $not_shimmed_list_parent->getTagName());
    $this->assertTrue($not_shimmed_list_parent->hasAttribute('data-drupal-autocomplete-wrapper'));
    $this->assertEquals('Falkland Islands (FK) Faroe Islands (FO) Fiji (FJ) Finland (FI) France (FR) French Guiana (GF) French Polynesia (PF) French Southern Territories (TF) Micronesia (FM)', $not_shimmed_list->getText());
  }

  /**
   * Gets the suggestion list associated with an input.
   *
   * @param \Behat\Mink\Element\NodeElement $input
   *   The autocomplete input element.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The suggestion list
   */
  public function getList(NodeElement $input) {
    $list_id = $input->getAttribute('aria-owns');
    $list = $this->getSession()->getPage()->findById($list_id);
    $this->assertNotNull($list);
    return $list;
  }

  /**
   * Gets the description associated with an input.
   *
   * @param \Behat\Mink\Element\NodeElement $input
   *   The autocomplete input element.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The element containing the description.
   */
  public function getDescription(NodeElement $input) {
    $aria_describedby = $input->getAttribute('aria-describedby');
    $description = $this->getSession()->getPage()->findById($aria_describedby);
    $this->assertNotNull($description);
    return $description;
  }

}

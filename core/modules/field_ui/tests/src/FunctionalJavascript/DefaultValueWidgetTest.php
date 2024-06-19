<?php

declare(strict_types=1);

namespace Drupal\Tests\field_ui\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\field_ui\Traits\FieldUiJSTestTrait;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;

/**
 * Tests the default value widget in Field UI.
 *
 * @group field_ui
 */
class DefaultValueWidgetTest extends WebDriverTestBase {

  use TaxonomyTestTrait;
  use FieldUiJSTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'field_ui',
    'taxonomy',
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
    $this->createContentType(['type' => 'test_content']);

    $user = $this->drupalCreateUser([
      'access content',
      'administer content types',
      'administer node fields',
    ]);
    $this->drupalLogin($user);
  }

  /**
   * Tests default value options on field config change.
   */
  public function testDefaultValueOptionsForChangingBundles(): void {
    $vocab_1 = $this->createVocabulary(['name' => 'Colors']);
    $this->createTerm($vocab_1, ['name' => 'red']);
    $this->createTerm($vocab_1, ['name' => 'green']);

    $vocab_2 = $this->createVocabulary(['name' => 'Tags']);
    $this->createTerm($vocab_2, ['name' => 'random tag 1']);
    $this->createTerm($vocab_2, ['name' => 'random tag 2']);

    $field_name = 'test_field';
    $this->fieldUIAddNewFieldJS('admin/structure/types/manage/test_content', $field_name, $field_name, 'entity_reference', FALSE);
    $page = $this->getSession()->getPage();
    $page->findField('field_storage[subform][settings][target_type]')->selectOption('taxonomy_term');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $page->findField('settings[handler_settings][target_bundles][' . $vocab_1->id() . ']')->check();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $page->findField('set_default_value')->check();

    $default_value_field = $page->findField('default_value_input[field_' . $field_name . '][0][target_id]');
    $default_value_field->setValue('r');
    $this->getSession()->getDriver()->keyDown($default_value_field->getXpath(), ' ');
    $this->assertSession()->waitOnAutocomplete();

    // Check the autocomplete results.
    $results = $page->findAll('css', '.ui-autocomplete li');
    $this->assertCount(2, $results);
    $this->assertSession()->elementTextNotContains('css', '.ui-autocomplete li', 'random tag 1');
    $this->assertSession()->elementTextContains('css', '.ui-autocomplete li', 'green');
  }

}

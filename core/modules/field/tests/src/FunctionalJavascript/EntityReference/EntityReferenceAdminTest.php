<?php

namespace Drupal\Tests\field\FunctionalJavascript\EntityReference;

use Drupal\Core\Url;
use Behat\Mink\Element\NodeElement;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\field_ui\Traits\FieldUiTestTrait;

/**
 * Tests for the administrative UI.
 *
 * @group entity_reference
 */
class EntityReferenceAdminTest extends WebDriverTestBase {

  use FieldUiTestTrait;

  /**
   * Modules to install.
   *
   * Enable path module to ensure that the selection handler does not fail for
   * entities with a path field.
   *
   * @var array
   */
  public static $modules = ['node', 'field_ui', 'path', 'taxonomy', 'block', 'views_ui'];

  /**
   * The name of the content type created for testing purposes.
   *
   * @var string
   */
  protected $type;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalPlaceBlock('system_breadcrumb_block');

    // Create a content type, with underscores.
    $type_name = strtolower($this->randomMachineName(8)) . '_test';
    $type = $this->drupalCreateContentType(['name' => $type_name, 'type' => $type_name]);
    $this->type = $type->id();

    // Create test user.
    $admin_user = $this->drupalCreateUser([
      'access content',
      'administer node fields',
      'administer node display',
      'administer views',
      'create ' . $type_name . ' content',
      'edit own ' . $type_name . ' content',
    ]);
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests the Entity Reference Admin UI.
   */
  public function testFieldAdminHandler() {
    $bundle_path = 'admin/structure/types/manage/' . $this->type;

    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    // First step: 'Add new field' on the 'Manage fields' page.
    $this->drupalGet($bundle_path . '/fields/add-field');

    // Check if the commonly referenced entity types appear in the list.
    $this->assertOption('edit-new-storage-type', 'field_ui:entity_reference:node');
    $this->assertOption('edit-new-storage-type', 'field_ui:entity_reference:user');

    $page->findField('new_storage_type')->setValue('entity_reference');
    $assert_session->waitForField('label')->setValue('Test');
    $machine_name = $assert_session->waitForElement('xpath', '//*[@id="edit-label-machine-name-suffix"]/span[2]/span[contains(text(), "field_test")]');
    $this->assertNotEmpty($machine_name);
    $page->pressButton('Save and continue');

    // Node should be selected by default.
    $this->assertFieldByName('settings[target_type]', 'node');

    // Check that all entity types can be referenced.
    $this->assertFieldSelectOptions('settings[target_type]', array_keys(\Drupal::entityManager()->getDefinitions()));

    // Second step: 'Field settings' form.
    $this->drupalPostForm(NULL, [], t('Save field settings'));

    // The base handler should be selected by default.
    $this->assertFieldByName('settings[handler]', 'default:node');

    // The base handler settings should be displayed.
    $entity_type_id = 'node';
    // Check that the type label is correctly displayed.
    $assert_session->pageTextContains('Content type');
    $bundles = $this->container->get('entity_type.bundle.info')->getBundleInfo($entity_type_id);
    foreach ($bundles as $bundle_name => $bundle_info) {
      $this->assertFieldByName('settings[handler_settings][target_bundles][' . $bundle_name . ']');
    }

    reset($bundles);

    // Test the sort settings.
    // Option 0: no sort.
    $this->assertFieldByName('settings[handler_settings][sort][field]', '_none');
    $this->assertNoFieldByName('settings[handler_settings][sort][direction]');
    // Option 1: sort by field.
    $page->findField('settings[handler_settings][sort][field]')->setValue('nid');
    $assert_session->waitForField('settings[handler_settings][sort][direction]');
    $this->assertFieldByName('settings[handler_settings][sort][direction]', 'ASC');

    // Test that a non-translatable base field is a sort option.
    $this->assertFieldByXPath("//select[@name='settings[handler_settings][sort][field]']/option[@value='nid']");
    // Test that a translatable base field is a sort option.
    $this->assertFieldByXPath("//select[@name='settings[handler_settings][sort][field]']/option[@value='title']");
    // Test that a configurable field is a sort option.
    $this->assertFieldByXPath("//select[@name='settings[handler_settings][sort][field]']/option[@value='body.value']");

    // Set back to no sort.
    $page->findField('settings[handler_settings][sort][field]')->setValue('_none');
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertNoFieldByName('settings[handler_settings][sort][direction]');

    // Third step: confirm.
    $page->findField('settings[handler_settings][target_bundles][' . key($bundles) . ']')->setValue(key($bundles));
    $assert_session->assertWaitOnAjaxRequest();
    $this->drupalPostForm(NULL, [
      'required' => '1',
    ], t('Save settings'));

    // Check that the field appears in the overview form.
    $this->assertFieldByXPath('//table[@id="field-overview"]//tr[@id="field-test"]/td[1]', 'Test', 'Field was created and appears in the overview page.');

    // Check that the field settings form can be submitted again, even when the
    // field is required.
    // The first 'Edit' link is for the Body field.
    $this->clickLink(t('Edit'), 1);
    $this->drupalPostForm(NULL, [], t('Save settings'));

    // Switch the target type to 'taxonomy_term' and check that the settings
    // specific to its selection handler are displayed.
    $field_name = 'node.' . $this->type . '.field_test';
    $edit = [
      'settings[target_type]' => 'taxonomy_term',
    ];
    $this->drupalPostForm($bundle_path . '/fields/' . $field_name . '/storage', $edit, t('Save field settings'));
    $this->drupalGet($bundle_path . '/fields/' . $field_name);
    $this->assertFieldByName('settings[handler_settings][auto_create]');

    // Switch the target type to 'user' and check that the settings specific to
    // its selection handler are displayed.
    $field_name = 'node.' . $this->type . '.field_test';
    $edit = [
      'settings[target_type]' => 'user',
    ];
    $this->drupalPostForm($bundle_path . '/fields/' . $field_name . '/storage', $edit, t('Save field settings'));
    $this->drupalGet($bundle_path . '/fields/' . $field_name);
    $this->assertFieldByName('settings[handler_settings][filter][type]', '_none');

    // Switch the target type to 'node'.
    $field_name = 'node.' . $this->type . '.field_test';
    $edit = [
      'settings[target_type]' => 'node',
    ];
    $this->drupalPostForm($bundle_path . '/fields/' . $field_name . '/storage', $edit, t('Save field settings'));

    // Try to select the views handler.
    $this->drupalGet($bundle_path . '/fields/' . $field_name);
    $page->findField('settings[handler]')->setValue('views');
    $views_text = (string) new FormattableMarkup('No eligible views were found. <a href=":create">Create a view</a> with an <em>Entity Reference</em> display, or add such a display to an <a href=":existing">existing view</a>.', [
      ':create' => Url::fromRoute('views_ui.add')->toString(),
      ':existing' => Url::fromRoute('entity.view.collection')->toString(),
    ]);
    $assert_session->waitForElement('xpath', '//a[contains(text(), "Create a view")]');
    $assert_session->responseContains($views_text);

    $this->drupalPostForm(NULL, [], t('Save settings'));
    // If no eligible view is available we should see a message.
    $assert_session->pageTextContains('The views entity selection mode requires a view.');

    // Enable the entity_reference_test module which creates an eligible view.
    $this->container->get('module_installer')
      ->install(['entity_reference_test']);
    $this->resetAll();
    $this->drupalGet($bundle_path . '/fields/' . $field_name);
    $page->findField('settings[handler]')->setValue('views');
    $assert_session
      ->waitForField('settings[handler_settings][view][view_and_display]')
      ->setValue('test_entity_reference:entity_reference_1');
    $this->drupalPostForm(NULL, [], t('Save settings'));
    $assert_session->pageTextContains('Saved Test configuration.');

    // Switch the target type to 'entity_test'.
    $edit = [
      'settings[target_type]' => 'entity_test',
    ];
    $this->drupalPostForm($bundle_path . '/fields/' . $field_name . '/storage', $edit, t('Save field settings'));
    $this->drupalGet($bundle_path . '/fields/' . $field_name);
    $page->findField('settings[handler]')->setValue('views');
    $assert_session
      ->waitForField('settings[handler_settings][view][view_and_display]')
      ->setValue('test_entity_reference_entity_test:entity_reference_1');
    $edit = [
      'required' => FALSE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save settings'));
    $assert_session->pageTextContains('Saved Test configuration.');
  }

  /**
   * Checks if a select element contains the specified options.
   *
   * @param string $name
   *   The field name.
   * @param array $expected_options
   *   An array of expected options.
   */
  protected function assertFieldSelectOptions($name, array $expected_options) {
    $xpath = $this->buildXPathQuery('//select[@name=:name]', [':name' => $name]);
    $fields = $this->xpath($xpath);
    if ($fields) {
      $field = $fields[0];
      $options = $field->findAll('xpath', 'option');
      $optgroups = $field->findAll('xpath', 'optgroup');
      foreach ($optgroups as $optgroup) {
        $options = array_merge($options, $optgroup->findAll('xpath', 'option'));
      }
      array_walk($options, function (NodeElement &$option) {
        $option = $option->getAttribute('value');
      });

      sort($options);
      sort($expected_options);

      $this->assertIdentical($options, $expected_options);
    }
    else {
      $this->fail('Unable to find field ' . $name);
    }
  }

}

<?php

declare(strict_types=1);

namespace Drupal\Tests\views_ui\FunctionalJavascript;

use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\views_ui\Traits\FilterEntityReferenceTrait;

/**
 * Tests views creation wizard.
 *
 * @group views_ui
 * @see \Drupal\views\Plugin\views\filter\EntityReference
 */
class FilterEntityReferenceTest extends WebDriverTestBase {

  use FilterEntityReferenceTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'views',
    'views_ui',
    'views_test_entity_reference',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_entity_reference'];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $admin_user = $this->drupalCreateUser([
      'administer views',
    ]);
    $this->drupalLogin($admin_user);

    $this->setUpEntityTypes();
  }

  /**
   * Tests end to end creation of a Content Entity Reference filter.
   */
  public function testAddEntityReferenceFieldWithDefaultSelectionHandler(): void {
    $this->drupalGet('admin/structure/views/view/content');
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Open the dialog.
    $page->clickLink('views-add-filter');

    // Wait for the popup to open and the search field to be available.
    $assert->waitForField('override[controls][options_search]');

    // Test that the both entity_reference and numeric options are visible.
    $this->assertTrue($page->findField('name[node__field_test.field_test_target_id]')
      ->isVisible());
    $this->assertTrue($page->findField('name[node__field_test.field_test_target_id]')
      ->isVisible());
    $page->findField('name[node__field_test.field_test_target_id]')
      ->click();
    $this->assertTrue($page->find('css', 'button.button.button--primary.form-submit.ui-button')
      ->isVisible());
    $page->find('css', 'button.button.button--primary.form-submit.ui-button')
      ->click();

    // Wait for the selection handler to show up.
    $assert->waitForField('options[sub_handler]');
    $page->selectFieldOption('options[sub_handler]', 'default:node');

    // Check that that default handler target bundles are available.
    $this->assertTrue($page->findField('options[reference_default:node][target_bundles][article]')
      ->isVisible());
    $this->assertTrue($page->findField('options[reference_default:node][target_bundles][page]')
      ->isVisible());
    $this->assertTrue($page->findField('options[widget]')->isVisible());

    // Ensure that disabled form elements from selection handler do not show up
    // @see \Drupal\views\Plugin\views\filter\EntityReference method
    // buildExtraOptionsForm.
    $this->assertFalse($page->hasField('options[reference_default:node][target_bundles_update]'));
    $this->assertFalse($page->hasField('options[reference_default:node][auto_create]'));
    $this->assertFalse($page->hasField('options[reference_default:node][auto_create_bundle]'));

    // Choose the default handler using the select widget with article type
    // checked.
    $page->checkField('options[reference_default:node][target_bundles][article]');
    $page->selectFieldOption('options[widget]', 'select');
    $this->assertSame($page->findField('options[widget]')
      ->getValue(), 'select');
    $page->find('xpath', "//*[contains(text(), 'Apply and continue')]")
      ->press();

    // Test the exposed filter options show up correctly.
    $assert->waitForField('options[expose_button][checkbox][checkbox]');
    $page->findField('options[expose_button][checkbox][checkbox]')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertTrue($page->hasCheckedField('options[expose_button][checkbox][checkbox]'));

    // Check the exposed filters multiple option.
    $assert->waitForField('options[expose][multiple]');
    $page->findField('options[expose][multiple]')->click();
    $this->assertTrue($page->hasCheckedField('options[expose][multiple]'));
    $page->find('css', '.ui-dialog .ui-dialog-buttonpane')->pressButton('Apply');
    $assert->waitForElementRemoved('css', '.ui-dialog');

    // Wait for the Views Preview to show up with the new reference field.
    $assert->waitForField('field_test_config_target_id[]');
    $this->assertTrue($page->findField('field_test_target_id[]')
      ->isVisible());
    $this->assertTrue($page->find('css', 'select[name="field_test_target_id[]"]')
      ->hasAttribute('multiple'));

    // Opening the settings form and change the handler to use an Entity
    // Reference view.
    // @see views.view.test_entity_reference.yml
    $base_url = Url::fromRoute('entity.view.collection')->toString();
    $url = $base_url . '/nojs/handler-extra/content/page_1/filter/field_test_target_id';
    $extra_settings_selector = 'a[href="' . $url . '"]';
    $element = $this->assertSession()->waitForElementVisible('css', $extra_settings_selector);
    $this->assertNotNull($element);
    $element->click();
    $assert->waitForField('options[sub_handler]');
    $page->selectFieldOption('options[sub_handler]', 'views');
    $page->selectFieldOption('options[reference_views][view][view_and_display]', 'test_entity_reference:entity_reference');
    $page->find('xpath', "//*[contains(text(), 'Apply')]")
      ->press();
    $assert->assertWaitOnAjaxRequest();

    // The Views Reference filter has a title Filter to a single result, so
    // ensure only that result is available as an option.
    $assert->waitForElementRemoved('css', '.ui-dialog');

    $this->assertCount(1, $page->findAll('css', 'select[name="field_test_target_id[]"] option'));

    // Change to an autocomplete filter.
    // Opening the settings form and change the handler to use an Entity
    // Reference view.
    // @see views.view.test_entity_reference.yml
    $page->find('css', $extra_settings_selector)
      ->click();
    $assert->waitForElementVisible('named', [
      'radio',
      'options[widget]',
    ]);
    $page->selectFieldOption('options[widget]', 'autocomplete');
    $this->assertSame($page->findField('options[widget]')
      ->getValue(), 'autocomplete');
    $this->getSession()
      ->getPage()
      ->find('xpath', "//*[contains(text(), 'Apply')]")
      ->press();

    // Check that it is now an autocomplete.
    $assert->waitForField('field_test_target_id');
    $page = $this->getSession()->getPage();
    $this->assertTrue($page->findField('field_test_target_id')
      ->isVisible());
    $this->assertTrue($page->find('css', 'input[name="field_test_target_id"]')
      ->hasAttribute('data-autocomplete-path'));
  }

  /**
   * Tests end to end creation of a Config Entity Reference filter.
   */
  public function testAddConfigEntityReferenceFieldWithDefaultSelectionHandler(): void {
    $this->drupalGet('admin/structure/views/view/content');
    $assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Open the 'Add filter dialog'.
    $page->clickLink('views-add-filter');

    // Wait for the popup to open and the search field to be available.
    $assert->waitForField('override[controls][group]');

    // Test that the entity_reference option is visible.
    $this->assertTrue($page->findField('name[node__field_test_config.field_test_config_target_id]')->isVisible());
    $page->findField('name[node__field_test_config.field_test_config_target_id]')->click();
    $submitButton = $page->find('css', 'button.button.button--primary.form-submit.ui-button');
    $this->assertTrue($submitButton->isVisible());
    $submitButton->click();

    // Wait for the selection handler to show up.
    $assert->waitForField('options[sub_handler]');

    $page->selectFieldOption('options[sub_handler]', 'default:node_type');

    // Choose the default handler using the select widget with article type
    // checked.
    $page->selectFieldOption('options[widget]', 'select');
    $this->assertSame('select', $page->findField('options[widget]')->getValue());
    $page->find('xpath', "//*[contains(text(), 'Apply and continue')]")->press();

    // Test the exposed filter options show up correctly.
    $assert->waitForField('options[expose_button][checkbox][checkbox]');
    $page->findField('options[expose_button][checkbox][checkbox]')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertTrue($page->hasCheckedField('options[expose_button][checkbox][checkbox]'));

    // Check the exposed filters multiple option.
    $assert->waitForField('options[expose][multiple]');
    $page->findField('options[expose][multiple]')->click();
    $this->assertTrue($page->hasCheckedField('options[expose][multiple]'));
    $page->find('css', '.ui-dialog .ui-dialog-buttonpane')->pressButton('Apply');
    $assert->waitForElementRemoved('css', '.ui-dialog');

    // Wait for the Views Preview to show up with the reference field.
    $assert->waitForField('field_test_config_target_id[]');
    $this->assertTrue($page->findField('field_test_config_target_id[]')->isVisible());
    $this->assertTrue($page->find('css', 'select[name="field_test_config_target_id[]"]')->hasAttribute('multiple'));

    // Check references config options.
    $options = $page->findAll('css', 'select[name="field_test_config_target_id[]"] option');
    $this->assertCount(2, $options);
    $this->assertSame('article', $options[0]->getValue());
    $this->assertSame('page', $options[1]->getValue());

    $base_url = Url::fromRoute('entity.view.collection')->toString();
    $url = $base_url . '/nojs/handler-extra/content/page_1/filter/field_test_config_target_id';
    $extra_settings_selector = 'a[href="' . $url . '"]';

    // Change to an autocomplete filter.
    $page->find('css', $extra_settings_selector)->click();
    $assert->waitForField('options[widget]');
    $page->selectFieldOption('options[widget]', 'autocomplete');
    $this->assertSame('autocomplete', $page->findField('options[widget]')->getValue());
    $page->find('css', '.ui-dialog .ui-dialog-buttonpane')->pressButton('Apply');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Check that it is now an autocomplete input.
    $assert->waitForField('field_test_config_target_id');
    $this->assertTrue($page->findField('field_test_config_target_id')->isVisible());
    $this->assertTrue($page->find('css', 'input[name="field_test_config_target_id"]')->hasAttribute('data-autocomplete-path'));
  }

}

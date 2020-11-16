<?php

namespace Drupal\Tests\views_ui\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\views\Views;

/**
 * Tests the UI of field handlers.
 *
 * @group views_ui
 * @see \Drupal\views\Plugin\views\field\FieldPluginBase
 */
class FieldUITest extends UITestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_view'];

  /**
   * Tests the UI of field handlers.
   */
  public function testFieldUI() {
    // Ensure the field is not marked as hidden on the first run.
    $this->drupalGet('admin/structure/views/view/test_view/edit');
    $this->assertText('Views test: Name');
    $this->assertNoText('Views test: Name [' . t('hidden') . ']');

    // Hides the field and check whether the hidden label is appended.
    $edit_handler_url = 'admin/structure/views/nojs/handler/test_view/default/field/name';
    $this->drupalPostForm($edit_handler_url, ['options[exclude]' => TRUE], 'Apply');

    $this->assertText('Views test: Name [' . t('hidden') . ']');

    // Ensure that the expected tokens appear in the UI.
    $edit_handler_url = 'admin/structure/views/nojs/handler/test_view/default/field/age';
    $this->drupalGet($edit_handler_url);
    $result = $this->xpath('//details[@id="edit-options-alter-help"]/div[@class="details-wrapper"]/div[@class="item-list"]/ul/li');
    $this->assertEqual($result[0]->getHtml(), '{{ age }} == Age');

    $edit_handler_url = 'admin/structure/views/nojs/handler/test_view/default/field/id';
    $this->drupalGet($edit_handler_url);
    $result = $this->xpath('//details[@id="edit-options-alter-help"]/div[@class="details-wrapper"]/div[@class="item-list"]/ul/li');
    $this->assertEqual(trim($result[0]->getHtml()), '{{ age }} == Age');
    $this->assertEqual(trim($result[1]->getHtml()), '{{ id }} == ID');

    $edit_handler_url = 'admin/structure/views/nojs/handler/test_view/default/field/name';
    $this->drupalGet($edit_handler_url);
    $result = $this->xpath('//details[@id="edit-options-alter-help"]/div[@class="details-wrapper"]/div[@class="item-list"]/ul/li');
    $this->assertEqual(trim($result[0]->getHtml()), '{{ age }} == Age');
    $this->assertEqual(trim($result[1]->getHtml()), '{{ id }} == ID');
    $this->assertEqual(trim($result[2]->getHtml()), '{{ name }} == Name');

    $result = $this->xpath('//details[@id="edit-options-more"]');
    $this->assertEqual(empty($result), TRUE, "Container 'more' is empty and should not be displayed.");

    // Ensure that dialog titles are not escaped.
    $edit_groupby_url = 'admin/structure/views/nojs/handler/test_view/default/field/name';
    $this->assertSession()->linkByHrefNotExists($edit_groupby_url, 0, 'No aggregation link found.');

    // Enable aggregation on the view.
    $edit = [
      'group_by' => TRUE,
    ];
    $this->drupalPostForm('/admin/structure/views/nojs/display/test_view/default/group_by', $edit, 'Apply');

    $this->assertSession()->linkByHrefExists($edit_groupby_url, 0, 'Aggregation link found.');

    $edit_handler_url = '/admin/structure/views/ajax/handler-group/test_view/default/field/name';
    $this->drupalGet($edit_handler_url);
    $data = Json::decode($this->getSession()->getPage()->getContent());
    $this->assertEqual($data[3]['dialogOptions']['title'], 'Configure aggregation settings for field Views test: Name');
  }

  /**
   * Tests the field labels.
   */
  public function testFieldLabel() {
    // Create a view with unformatted style and make sure the fields have no
    // labels by default.
    $view = [];
    $view['label'] = $this->randomMachineName(16);
    $view['id'] = strtolower($this->randomMachineName(16));
    $view['description'] = $this->randomMachineName(16);
    $view['show[wizard_key]'] = 'node';
    $view['page[create]'] = TRUE;
    $view['page[style][style_plugin]'] = 'default';
    $view['page[title]'] = $this->randomMachineName(16);
    $view['page[path]'] = $view['id'];
    $this->drupalPostForm('admin/structure/views/add', $view, 'Save and edit');

    $view = Views::getView($view['id']);
    $view->initHandlers();
    $this->assertEqual($view->field['title']->options['label'], '', 'The field label for normal styles are empty.');
  }

}

<?php

namespace Drupal\Tests\filter\FunctionalJavascript;

use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the 'filter_html' plugin javascript functionality.
 *
 * @group filter
 */
class FilterHtmlTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['editor', 'filter'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests restricting HTML to table tags.
   */
  public function testTableTags() {
    FilterFormat::create([
      'format' => 'some_html',
      'name' => 'Some HTML',
      'filters' => [
        'filter_html' => [
          'status' => 1,
          'settings' => [
            'allowed_html' => '<caption> <tbody> <thead> <tfoot> <th> <td> <tr>',
          ],
        ],
      ],
    ])->save();

    $this->drupalLogin($this->drupalCreateUser(['administer filters']));
    $this->drupalGet('admin/config/content/formats/manage/some_html');

    $js_condition = "Drupal.behaviors.filterFilterHtmlUpdating._parseSetting(
      jQuery('#edit-filters-filter-html-settings-allowed-html').val()
    )['td'].tags.length >= 0";

    $this->assertJsCondition($js_condition);
  }

  /**
   * Tests the Allowed Tags configuration with CSS classes.
   *
   * @group legacy
   */
  public function testStylesToAllowedTagsSync() {
    \Drupal::service('module_installer')->install(['ckeditor']);
    FilterFormat::create([
      'format' => 'some_html',
      'name' => 'Some HTML',
      'filters' => [
        'filter_html' => [
          'status' => 1,
          'settings' => [
            'allowed_html' => '<span class>',
          ],
        ],
      ],
    ])->save();

    Editor::create([
      'format' => 'some_html',
      'editor' => 'ckeditor',
      'settings' => [
        'plugins' => [
          'stylescombo' => [
            'styles' => 'span.hello-world|Hello World',
          ],
        ],
      ],
    ])->save();

    $this->drupalLogin($this->drupalCreateUser(['administer filters']));
    $this->drupalGet('admin/config/content/formats/manage/some_html');

    $js_condition = "jQuery('#edit-filters-filter-html-settings-allowed-html').val() === \"<span class> <strong> <em> <a href> <ul> <li> <ol> <blockquote> <img src alt data-entity-type data-entity-uuid>\"";

    $this->assertJsCondition($js_condition);
  }

}

<?php

declare(strict_types=1);

namespace Drupal\Tests\filter\FunctionalJavascript;

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

}

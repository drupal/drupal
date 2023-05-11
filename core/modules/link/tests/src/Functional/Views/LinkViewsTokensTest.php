<?php

namespace Drupal\Tests\link\Functional\Views;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\views\Functional\ViewTestBase;

/**
 * Tests the views integration for link tokens.
 *
 * @group link
 */
class LinkViewsTokensTest extends ViewTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['link_test_views'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_link_tokens'];

  /**
   * The field name used for the link field.
   *
   * @var string
   */
  protected $fieldName = 'field_link';

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = ['link_test_views']): void {
    parent::setUp($import_test_views, $modules);

    // Create Basic page node type.
    $this->drupalCreateContentType([
      'type' => 'page',
      'name' => 'Basic page',
    ]);

    // Create a field.
    FieldStorageConfig::create([
      'field_name' => $this->fieldName,
      'type' => 'link',
      'entity_type' => 'node',
      'cardinality' => 1,
    ])->save();
    FieldConfig::create([
      'field_name' => $this->fieldName,
      'entity_type' => 'node',
      'bundle' => 'page',
      'label' => 'link field',
    ])->save();

  }

  public function testLinkViewsTokens() {
    // Array of URI's to test.
    $uris = [
      'http://www.example.com' => 'example.com',
    ];

    // Add nodes with the URI's and titles.
    foreach ($uris as $uri => $title) {
      $values = ['type' => 'page'];
      $values[$this->fieldName][] = ['uri' => $uri, 'title' => $title, 'options' => ['attributes' => ['class' => 'test-link-class']]];
      $this->drupalCreateNode($values);
    }

    $this->drupalGet('test_link_tokens');

    foreach ($uris as $uri => $title) {
      // Formatted link: {{ field_link }}<br />
      $this->assertSession()->responseContains("Formatted: <a href=\"$uri\" class=\"test-link-class\">$title</a>");

      // Raw uri: {{ field_link__uri }}<br />
      $this->assertSession()->responseContains("Raw uri: $uri");

      // Raw title: {{ field_link__title }}<br />
      $this->assertSession()->responseContains("Raw title: $title");

      // Raw options: {{ field_link__options }}<br />
      // Options is an array and should return empty after token replace.
      $this->assertSession()->responseContains("Raw options: .");
    }
  }

}

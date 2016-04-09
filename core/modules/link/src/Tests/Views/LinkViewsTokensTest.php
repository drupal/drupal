<?php

namespace Drupal\link\Tests\Views;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\views\Tests\ViewTestBase;
use Drupal\views\Tests\ViewTestData;

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
  public static $modules = ['link_test_views'];

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
  protected function setUp() {
    parent::setUp();
    ViewTestData::createTestViews(get_class($this), array('link_test_views'));

    // Create Basic page node type.
    $this->drupalCreateContentType(array(
      'type' => 'page',
      'name' => 'Basic page'
    ));

    // Create a field.
    FieldStorageConfig::create(array(
      'field_name' => $this->fieldName,
      'type' => 'link',
      'entity_type' => 'node',
      'cardinality' => 1,
    ))->save();
    FieldConfig::create(array(
      'field_name' => $this->fieldName,
      'entity_type' => 'node',
      'bundle' => 'page',
      'label' => 'link field',
    ))->save();

  }

  public function testLinkViewsTokens() {
    // Array of URI's to test.
    $uris = [
      'http://www.drupal.org' => 'Drupal.org',
    ];

    // Add nodes with the URI's and titles.
    foreach ($uris as $uri => $title) {
      $values = array('type' => 'page');
      $values[$this->fieldName][] = ['uri' => $uri, 'title' => $title, 'options' => ['attributes' => ['class' => 'test-link-class']]];
      $this->drupalCreateNode($values);
    }

    $this->drupalGet('test_link_tokens');

    foreach ($uris as $uri => $title) {
      // Formatted link: {{ field_link }}<br />
      $this->assertRaw("Formated: <a href=\"$uri\" class=\"test-link-class\">$title</a>");

      // Raw uri: {{ field_link__uri }}<br />
      $this->assertRaw("Raw uri: $uri");

      // Raw title: {{ field_link__title }}<br />
      $this->assertRaw("Raw title: $title");

      // Raw options: {{ field_link__options }}<br />
      // Options is an array and should return empty after token replace.
      $this->assertRaw("Raw options: .");
    }
  }
}

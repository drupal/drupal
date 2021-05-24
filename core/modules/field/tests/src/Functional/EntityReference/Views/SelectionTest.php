<?php

namespace Drupal\Tests\field\Functional\EntityReference\Views;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Html;
use Drupal\Core\Site\Settings;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Drupal\views\Views;

/**
 * Tests entity reference selection handler.
 *
 * @group entity_reference
 */
class SelectionTest extends BrowserTestBase {

  use EntityReferenceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'views',
    'entity_reference_test',
    'entity_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * An array of node titles, keyed by content type and node ID.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected $nodes = [];

  /**
   * The handler settings for the entity reference field.
   *
   * @var array
   */
  protected $handlerSettings;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create content types and nodes.
    $type1 = $this->drupalCreateContentType()->id();
    $type2 = $this->drupalCreateContentType()->id();
    // Add some characters that should be escaped but not double escaped.
    $node1 = $this->drupalCreateNode(['type' => $type1, 'title' => 'Test first node &<>']);
    $node2 = $this->drupalCreateNode(['type' => $type1, 'title' => 'Test second node &&&']);
    $node3 = $this->drupalCreateNode(['type' => $type2, 'title' => 'Test third node <span />']);

    foreach ([$node1, $node2, $node3] as $node) {
      $this->nodes[$node->id()] = $node;
    }

    // Create an entity reference field.
    $handler_settings = [
      'view' => [
        'view_name' => 'test_entity_reference',
        'display_name' => 'entity_reference_1',
      ],
    ];
    $this->handlerSettings = $handler_settings;
    $this->createEntityReferenceField('entity_test', 'test_bundle', 'test_field', $this->randomString(), 'node', 'views', $handler_settings);
  }

  /**
   * Tests that the Views selection handles the views output properly.
   */
  public function testAutocompleteOutput() {
    // Reset any internal static caching.
    \Drupal::service('entity_type.manager')->getStorage('node')->resetCache();

    $view = Views::getView('test_entity_reference');
    $view->setDisplay();

    // Enable the display of the 'type' field so we can test that the output
    // does not contain only the entity label.
    $fields = $view->displayHandlers->get('entity_reference_1')->getOption('fields');
    $fields['type']['exclude'] = FALSE;
    $view->displayHandlers->get('entity_reference_1')->setOption('fields', $fields);
    $view->save();

    // Prepare the selection settings key needed by the entity reference
    // autocomplete route.
    $target_type = 'node';
    $selection_handler = 'views';
    $selection_settings = $this->handlerSettings;
    $selection_settings_key = Crypt::hmacBase64(serialize($selection_settings) . $target_type . $selection_handler, Settings::getHashSalt());
    \Drupal::keyValue('entity_autocomplete')->set($selection_settings_key, $selection_settings);

    $result = Json::decode($this->drupalGet('entity_reference_autocomplete/' . $target_type . '/' . $selection_handler . '/' . $selection_settings_key, ['query' => ['q' => 't']]));

    $expected = [
      0 => [
        'value' => $this->nodes[1]->bundle() . ': ' . $this->nodes[1]->label() . ' (' . $this->nodes[1]->id() . ')',
        'label' => '<span class="views-field views-field-type"><span class="field-content">' . $this->nodes[1]->bundle() . '</span></span>: <span class="views-field views-field-title"><span class="field-content">' . Html::escape($this->nodes[1]->label()) . '</span></span>',
      ],
      1 => [
        'value' => $this->nodes[2]->bundle() . ': ' . $this->nodes[2]->label() . ' (' . $this->nodes[2]->id() . ')',
        'label' => '<span class="views-field views-field-type"><span class="field-content">' . $this->nodes[2]->bundle() . '</span></span>: <span class="views-field views-field-title"><span class="field-content">' . Html::escape($this->nodes[2]->label()) . '</span></span>',
      ],
      2 => [
        'value' => $this->nodes[3]->bundle() . ': ' . $this->nodes[3]->label() . ' (' . $this->nodes[3]->id() . ')',
        'label' => '<span class="views-field views-field-type"><span class="field-content">' . $this->nodes[3]->bundle() . '</span></span>: <span class="views-field views-field-title"><span class="field-content">' . Html::escape($this->nodes[3]->label()) . '</span></span>',
      ],
    ];
    $this->assertEquals($expected, $result, 'The autocomplete result of the Views entity reference selection handler contains the proper output.');
  }

}

<?php

namespace Drupal\Tests\link\Functional;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\link\LinkItemInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\field_ui\Traits\FieldUiTestTrait;

/**
 * Tests link field UI functionality.
 *
 * @group link
 */
class LinkFieldUITest extends BrowserTestBase {

  use FieldUiTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'link', 'field_ui', 'block'];

  /**
   * A user that can edit content types.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * A user that should see the help texts.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $helpTextUser;

  /**
   * The first content type to add fields to.
   *
   * @var \Drupal\node\Entity\NodeType
   */
  protected $firstContentType;

  /**
   * The second content type to add fields to.
   *
   * @var \Drupal\node\Entity\NodeType
   */
  protected $secondContentType;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->firstContentType = $this->drupalCreateContentType();
    $this->secondContentType = $this->drupalCreateContentType();
    $this->adminUser = $this->drupalCreateUser(['administer content types', 'administer node fields', 'administer node display']);
    $this->helpTextUser = $this->drupalCreateUser(['create ' . $this->secondContentType->id() . ' content']);
    $this->drupalPlaceBlock('system_breadcrumb_block');
  }

  /**
   * Tests the link field UI.
   */
  public function testFieldUI() {
    foreach ($this->providerTestFieldUI() as $item) {
      list($cardinality, $link_type, $title, $label, $field_name) = $item;
      $this->runFieldUIItem($cardinality, $link_type, $title, $label, $field_name);
    }
  }

  /**
   * Provides test data for ::testFieldUI().
   */
  protected function providerTestFieldUI() {
    // There are many combinations of field settings: where the description
    // should show: variation on internal, external, both; cardinality (where
    // the fieldset is hidden or used); and link text shown (required or
    // optional) or disabled. There are two descriptions: field and URL help
    // text.
    $cardinalities = [1, 2];
    $title_settings = [
      DRUPAL_DISABLED,
      DRUPAL_OPTIONAL,
    ];
    $link_types = [
      LinkItemInterface::LINK_EXTERNAL,
      LinkItemInterface::LINK_GENERIC,
      LinkItemInterface::LINK_INTERNAL,
    ];

    // Test all variations of link types on all cardinalities.
    foreach ($cardinalities as $cardinality) {
      foreach ($link_types as $link_type) {
        // Now, test this with both the title enabled and disabled.
        foreach ($title_settings as $title_setting) {
          // Test both empty and non-empty labels.
          foreach ([TRUE, FALSE] as $label_provided) {
            // Generate a unique machine name for the field so it can be
            // identified in the test.
            $id = implode('_', [
              'link',
              $cardinality,
              $link_type,
              $title_setting,
              (int) $label_provided,
            ]);

            // Use a unique label that contains some HTML.
            $label = '<img src="http://example.com">' . $id;

            yield [
              $cardinality,
              $link_type,
              $title_setting,
              $label_provided ? $label : '',
              $id,
            ];
          }
        }
      }
    }
  }

  /**
   * Tests one link field UI item.
   *
   * @param int $cardinality
   *   The field cardinality.
   * @param int $link_type
   *   Determine if the link is external, internal or both.
   * @param int $title
   *   Determine if the field will display the link text field.
   * @param string $label
   *   The field label.
   * @param string $field_name
   *   The unique machine name for the field.
   */
  public function runFieldUIItem($cardinality, $link_type, $title, $label, $field_name) {
    $this->drupalLogin($this->adminUser);
    $type_path = 'admin/structure/types/manage/' . $this->firstContentType->id();

    // Add a link field to the newly-created type. It defaults to allowing both
    // internal and external links.
    $field_label = str_replace('_', ' ', $field_name);
    $description = 'link field description';
    $field_edit = [
      'description' => $description,
    ];
    $this->fieldUIAddNewField($type_path, $field_name, $field_label, 'link', [], $field_edit);

    // Load the formatter page to check that the settings summary does not
    // generate warnings.
    // @todo Mess with the formatter settings a bit here.
    $this->drupalGet("$type_path/display");
    $this->assertText(t('Link text trimmed to @limit characters', ['@limit' => 80]));

    $storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => 'link',
      'cardinality' => $cardinality,
    ]);
    $storage->save();

    FieldConfig::create([
      'field_storage' => $storage,
      'label' => $label,
      'bundle' => $this->secondContentType->id(),
      'settings' => [
        'title' => $title,
        'link_type' => $link_type,
      ],
    ])->save();

    // Make the fields visible in the form display.
    $form_display_id = implode('.', ['node', $this->secondContentType->id(), 'default']);
    $form_display = EntityFormDisplay::load($form_display_id);
    $form_display->setComponent($field_name, ['region' => 'content']);
    $form_display->save();

    // Log in a user that is allowed to create this content type, see if
    // the user can see the expected help text.
    $this->drupalLogin($this->helpTextUser);

    $add_path = 'node/add/' . $this->secondContentType->id();
    $this->drupalGet($add_path);

    $expected_help_texts = [
      LinkItemInterface::LINK_EXTERNAL => 'This must be an external URL such as <em class="placeholder">http://example.com</em>.',
      LinkItemInterface::LINK_GENERIC => 'You can also enter an internal path such as <em class="placeholder">/node/add</em> or an external URL such as <em class="placeholder">http://example.com</em>. Enter <em class="placeholder">&lt;front&gt;</em> to link to the front page.',
      LinkItemInterface::LINK_INTERNAL => rtrim(\Drupal::url('<front>', [], ['absolute' => TRUE]), '/'),
    ];

    // Check that the help texts we assume should be there, is there.
    $this->assertFieldContainsRawText($field_name, $expected_help_texts[$link_type]);
    if ($link_type === LinkItemInterface::LINK_INTERNAL) {
      // Internal links have no "system" description. Test that none
      // of the other help texts show here.
      $this->assertNoFieldContainsRawText($field_name, $expected_help_texts[LinkItemInterface::LINK_EXTERNAL]);
      $this->assertNoFieldContainsRawText($field_name, $expected_help_texts[LinkItemInterface::LINK_GENERIC]);
    }
    // Also assert that the description we made is here, no matter what the
    // cardinality or link setting.
    if (!empty($label)) {
      $this->assertFieldContainsRawText($field_name, $label);
    }
  }

  /**
   * Checks that given field contains the given raw text.
   *
   * @param string $field_name
   *   The name of the field to check.
   * @param string $text
   *   The text to check.
   */
  protected function assertFieldContainsRawText($field_name, $text) {
    $this->assertTrue((bool) preg_match('/' . preg_quote($text, '/') . '/ui', $this->getFieldHtml($field_name)));
  }

  /**
   * Checks that given field does not contain the given raw text.
   *
   * @param string $field_name
   *   The name of the field to check.
   * @param string $text
   *   The text to check.
   */
  protected function assertNoFieldContainsRawText($field_name, $text) {
    $this->assertFalse((bool) preg_match('/' . preg_quote($text, '/') . '/ui', $this->getFieldHtml($field_name)));
  }

  /**
   * Returns the raw HTML for the given field.
   *
   * @param $field_name
   *   The name of the field for which to return the HTML.
   *
   * @return string
   *   The raw HTML.
   */
  protected function getFieldHtml($field_name) {
    $css_id = Html::cleanCssIdentifier('edit-' . $field_name . '-wrapper');
    return $this->xpath('//*[@id=:id]', [':id' => $css_id])[0]->getHtml();
  }

}

<?php

namespace Drupal\Tests\link\Functional;

use Drupal\Core\Url;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
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
  protected static $modules = ['node', 'link', 'field_ui', 'block'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

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
   * The content type to add fields to.
   *
   * @var \Drupal\node\Entity\NodeType
   */
  protected $contentType;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->contentType = $this->drupalCreateContentType();
    $this->adminUser = $this->drupalCreateUser([
      'administer content types',
      'administer node fields',
      'administer node display',
    ]);
    $this->helpTextUser = $this->drupalCreateUser([
      'create ' . $this->contentType->id() . ' content',
    ]);
    $this->drupalPlaceBlock('system_breadcrumb_block');
  }

  /**
   * Tests the link field UI.
   */
  public function testFieldUI() {
    foreach ($this->providerTestFieldUI() as $item) {
      list($cardinality, $link_type, $title, $label, $field_name, $default_uri) = $item;
      $this->runFieldUIItem($cardinality, $link_type, $title, $label, $field_name, $default_uri);
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
      LinkItemInterface::LINK_EXTERNAL => 'http://drupal.org',
      LinkItemInterface::LINK_GENERIC => '',
      LinkItemInterface::LINK_INTERNAL => '<front>',
    ];

    // Test all variations of link types on all cardinalities.
    foreach ($cardinalities as $cardinality) {
      foreach ($link_types as $link_type => $default_uri) {
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
              $default_uri,
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
   * @param string $default_uri
   *   The default URI value.
   */
  public function runFieldUIItem($cardinality, $link_type, $title, $label, $field_name, $default_uri) {
    $this->drupalLogin($this->adminUser);
    $type_path = 'admin/structure/types/manage/' . $this->contentType->id();

    // Add a link field to the newly-created type.
    $description = 'link field description';
    $field_edit = [
      'description' => $description,
      'settings[link_type]' => (int) $link_type,
    ];
    if (!empty($default_uri)) {
      $field_edit['default_value_input[field_' . $field_name . '][0][uri]'] = $default_uri;
      $field_edit['default_value_input[field_' . $field_name . '][0][title]'] = 'Default title';
    }
    $storage_edit = [
      'cardinality_number' => $cardinality,
    ];
    $this->fieldUIAddNewField($type_path, $field_name, $label, 'link', $storage_edit, $field_edit);

    // Load the formatter page to check that the settings summary does not
    // generate warnings.
    // @todo Mess with the formatter settings a bit here.
    $this->drupalGet("$type_path/display");
    $this->assertText('Link text trimmed to 80 characters');

    // Make the fields visible in the form display.
    $form_display_id = implode('.', ['node', $this->contentType->id(), 'default']);
    $form_display = EntityFormDisplay::load($form_display_id);
    $form_display->setComponent($field_name, ['region' => 'content']);
    $form_display->save();

    // Log in a user that is allowed to create this content type, see if
    // the user can see the expected help text.
    $this->drupalLogin($this->helpTextUser);

    $add_path = 'node/add/' . $this->contentType->id();
    $this->drupalGet($add_path);

    $expected_help_texts = [
      LinkItemInterface::LINK_EXTERNAL => 'This must be an external URL such as <em class="placeholder">http://example.com</em>.',
      LinkItemInterface::LINK_GENERIC => 'You can also enter an internal path such as <em class="placeholder">/node/add</em> or an external URL such as <em class="placeholder">http://example.com</em>. Enter <em class="placeholder">&lt;front&gt;</em> to link to the front page. Enter <em class="placeholder">&lt;nolink&gt;</em> to display link text only',
      LinkItemInterface::LINK_INTERNAL => rtrim(Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString(), '/'),
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

    // Test the default field value is used as expected.
    $this->assertSession()->fieldValueEquals('field_' . $field_name . '[0][uri]', $default_uri);
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
    $css_id = Html::cleanCssIdentifier('edit-field-' . $field_name . '-wrapper');
    return $this->xpath('//*[@id=:id]', [':id' => $css_id])[0]->getHtml();
  }

}

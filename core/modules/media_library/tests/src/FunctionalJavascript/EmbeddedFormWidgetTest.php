<?php

namespace Drupal\Tests\media_library\FunctionalJavascript;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests media widget nested inside another widget.
 *
 * @group media_library
 */
class EmbeddedFormWidgetTest extends WebDriverTestBase {

  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'media_library',
    'media_library_test',
    'media_library_test_widget',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $display_repository = $this->container->get('entity_display.repository');

    FieldStorageConfig::create([
      'field_name' => 'media_image_field',
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'settings' => [
        'target_type' => 'media',
        'required' => TRUE,
      ],
    ])->save();

    FieldConfig::create([
      'label' => 'A Media Image Field',
      'field_name' => 'media_image_field',
      'entity_type' => 'node',
      'bundle' => 'basic_page',
      'field_type' => 'entity_reference',
      'required' => TRUE,
      'settings' => [
        'handler_settings' => [
          'target_bundles' => [
            'type_three' => 'type_three',
          ],
        ],
      ],
    ])->save();

    $display_repository->getFormDisplay('node', 'basic_page')
      ->setComponent('media_image_field', [
        'type' => 'media_library_widget',
        'region' => 'content',
        'settings' => [
          'media_types' => ['type_three'],
        ],
      ])
      ->save();

    $this->config('media_library.settings')
      ->set('advanced_ui', TRUE)
      ->save();

    $user = $this->drupalCreateUser([
      'access content',
      'access media overview',
      'edit own basic_page content',
      'create basic_page content',
      'create media',
      'view media',
    ]);
    $this->drupalLogin($user);
  }

  /**
   * Tests media inside another widget that validates too enthusiastically.
   *
   * @dataProvider insertionReselectionProvider
   */
  public function testInsertionAndReselection($widget) {
    $this->container
      ->get('entity_display.repository')
      ->getFormDisplay('node', 'basic_page')
      ->setComponent('media_image_field', [
        'type' => $widget,
        'region' => 'content',
        'settings' => [
          'media_types' => ['type_three'],
        ],
      ])
      ->save();

    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    foreach ($this->getTestFiles('image') as $image) {
      $extension = pathinfo($image->filename, PATHINFO_EXTENSION);
      if ($extension === 'jpg') {
        $jpg_image = $image;
        break;
      }
    }

    $this->drupalGet('node/add/basic_page');
    $wrapper = $assert_session->elementExists('css', '#media_image_field-media-library-wrapper');
    $wrapper->pressButton('Add media');
    $this->assertNotNull($assert_session->waitForText('Add or select media'));
    $page->attachFileToField('Add file', $this->container->get('file_system')->realpath($jpg_image->uri));
    $this->assertNotNull($assert_session->waitForText('Alternative text'));
    $page->fillField('Alternative text', $this->randomString());
    $assert_session->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Save and insert');
    $first_item_locator = "(//div[@data-drupal-selector='edit-media-image-field-selection-0'])[1]";
    $this->assertNotNull($first_item = $assert_session->waitForElementVisible('xpath', $first_item_locator));
    $first_item->pressButton('Remove');
    $assert_session->waitForElementRemoved('xpath', $first_item_locator);
    $page->waitFor(10, function () use ($wrapper) {
      return $wrapper->hasButton('Add media');
    });
    // Test reinserting the same selection.
    $wrapper->pressButton('Add media');
    $this->assertNotNull($assert_session->waitForText('Add or select media'));
    $assert_session->elementExists('xpath', "(//div[contains(@class, 'media-library-item')])[1]")->click();
    $assert_session->checkboxChecked('media_library_select_form[0]');
    $assert_session->elementExists('css', '.ui-dialog-buttonpane')->pressButton('Insert selected');
    $this->assertNotNull($assert_session->waitForElementVisible('xpath', $first_item_locator));
  }

  /**
   * Data provider for ::testInsertionAndReselection().
   *
   * @return array
   *   Test data.
   */
  public function insertionReselectionProvider() {
    return [
      'using media_library_widget' => [
        'widget' => 'media_library_widget',
      ],
      'using media_library_inception_widget' => [
        'widget' => 'media_library_inception_widget',
      ],
    ];
  }

}

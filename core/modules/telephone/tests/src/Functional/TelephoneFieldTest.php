<?php

namespace Drupal\Tests\telephone\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\telephone\Plugin\Field\FieldType\TelephoneItem;
use Drupal\Tests\BrowserTestBase;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests the creation of telephone fields.
 *
 * @group telephone
 * @group #slow
 */
class TelephoneFieldTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'field',
    'node',
    'telephone',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with permission to create articles.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'article']);
    $this->webUser = $this->drupalCreateUser([
      'create article content',
      'edit own article content',
    ]);
    $this->drupalLogin($this->webUser);

    // Add the telephone field to the article content type.
    FieldStorageConfig::create([
      'field_name' => 'field_telephone',
      'entity_type' => 'node',
      'type' => 'telephone',
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_telephone',
      'label' => 'Telephone Number',
      'entity_type' => 'node',
      'bundle' => 'article',
    ])->save();

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');
    $display_repository->getFormDisplay('node', 'article')
      ->setComponent('field_telephone', [
        'type' => 'telephone_default',
        'settings' => [
          'placeholder' => '123-456-7890',
        ],
      ])
      ->save();

    $display_repository->getViewDisplay('node', 'article')
      ->setComponent('field_telephone', [
        'type' => 'telephone_link',
        'weight' => 1,
      ])
      ->save();
  }

  /**
   * Tests to confirm the widget is setup.
   *
   * @covers \Drupal\telephone\Plugin\Field\FieldWidget\TelephoneDefaultWidget::formElement
   */
  public function testTelephoneWidget() {
    $this->drupalGet('node/add/article');
    $this->assertSession()->fieldValueEquals("field_telephone[0][value]", '');
    $this->assertSession()->elementAttributeContains('css', 'input[name="field_telephone[0][value]"]', 'maxlength', (string) TelephoneItem::MAX_LENGTH);
    $this->assertSession()->responseContains('placeholder="123-456-7890"');
  }

  /**
   * Tests the telephone formatter.
   *
   * @covers \Drupal\telephone\Plugin\Field\FieldFormatter\TelephoneLinkFormatter::viewElements
   *
   * @dataProvider providerPhoneNumbers
   */
  public function testTelephoneFormatter($input, $expected) {
    // Test basic entry of telephone field.
    $edit = [
      'title[0][value]' => $this->randomMachineName(),
      'field_telephone[0][value]' => $input,
    ];

    $this->drupalGet('node/add/article');
    $this->submitForm($edit, 'Save');
    $this->assertSession()->responseContains('<a href="tel:' . $expected . '">');
  }

  /**
   * Provides the phone numbers to check and expected results.
   */
  public function providerPhoneNumbers() {
    return [
      'standard phone number' => ['123456789', '123456789'],
      'whitespace is removed' => ['1234 56789', '123456789'],
      'parse_url(0) return FALSE workaround' => ['0', '0-'],
      'php bug 70588 workaround - lower edge check' => ['1', '1-'],
      'php bug 70588 workaround' => ['123', '1-23'],
      'php bug 70588 workaround - with whitespace removal' => ['1 2 3 4 5', '1-2345'],
      'php bug 70588 workaround - upper edge check' => ['65534', '6-5534'],
      'php bug 70588 workaround - edge check' => ['65535', '6-5535'],
      'php bug 70588 workaround - invalid port number - lower edge check' => ['65536', '6-5536'],
      'php bug 70588 workaround - invalid port number - upper edge check' => ['99999', '9-9999'],
      'lowest number not affected by php bug 70588' => ['100000', '100000'],
    ];
  }

}

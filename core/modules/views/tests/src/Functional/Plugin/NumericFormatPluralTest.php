<?php

namespace Drupal\Tests\views\Functional\Plugin;

use Drupal\Component\Gettext\PoHeader;
use Drupal\Component\Gettext\PoItem;
use Drupal\file\Entity\File;
use Drupal\Tests\views\Functional\ViewTestBase;

/**
 * Tests the creation of numeric fields.
 *
 * @group field
 */
class NumericFormatPluralTest extends ViewTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['views_ui', 'file', 'language', 'locale'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['numeric_test'];

  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    $web_user = $this->drupalCreateUser([
      'administer views',
      'administer languages',
    ]);
    $this->drupalLogin($web_user);
  }

  /**
   * Test plural formatting setting on a numeric views handler.
   */
  public function testNumericFormatPlural() {
    // Create a file.
    $file = $this->createFile();

    // Assert that the starting configuration is correct.
    $config = $this->config('views.view.numeric_test');
    $field_config_prefix = 'display.default.display_options.fields.count.';
    $this->assertEqual($config->get($field_config_prefix . 'format_plural'), TRUE);
    $this->assertEqual($config->get($field_config_prefix . 'format_plural_string'), '1' . PoItem::DELIMITER . '@count');

    // Assert that the value is displayed.
    $this->drupalGet('numeric-test');
    $this->assertRaw('<span class="field-content">0</span>');

    // Assert that the user interface has controls to change it.
    $this->drupalGet('admin/structure/views/nojs/handler/numeric_test/page_1/field/count');
    $this->assertFieldByName('options[format_plural_values][0]', '1');
    $this->assertFieldByName('options[format_plural_values][1]', '@count');

    // Assert that changing the settings will change configuration properly.
    $edit = ['options[format_plural_values][0]' => '1 time', 'options[format_plural_values][1]' => '@count times'];
    $this->drupalPostForm(NULL, $edit, t('Apply'));
    $this->drupalPostForm(NULL, [], t('Save'));

    $config = $this->config('views.view.numeric_test');
    $field_config_prefix = 'display.default.display_options.fields.count.';
    $this->assertEqual($config->get($field_config_prefix . 'format_plural'), TRUE);
    $this->assertEqual($config->get($field_config_prefix . 'format_plural_string'), '1 time' . PoItem::DELIMITER . '@count times');

    // Assert that the value is displayed with some sample values.
    $numbers = [0, 1, 2, 3, 4, 42];
    foreach ($numbers as $i => $number) {
      \Drupal::service('file.usage')->add($file, 'views_ui', 'dummy', $i, $number);
    }
    $this->drupalGet('numeric-test');
    foreach ($numbers as $i => $number) {
      $this->assertRaw('<span class="field-content">' . $number . ($number == 1 ? ' time' : ' times') . '</span>');
    }

    // Add Slovenian and set its plural formula to test multiple plural forms.
    $edit = ['predefined_langcode' => 'sl'];
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add language'));
    $formula = 'nplurals=4; plural=(n%100==1 ? 0 : n%100==2 ? 1 : n%100==3 || n%100==4 ? 2 : 3);';
    $header = new PoHeader();
    list($nplurals, $formula) = $header->parsePluralForms($formula);
    \Drupal::service('locale.plural.formula')->setPluralFormula('sl', $nplurals, $formula);

    // Change the view to Slovenian.
    $config = $this->config('views.view.numeric_test');
    $config->set('langcode', 'sl')->save();

    // Assert that the user interface has controls with more inputs now.
    $this->drupalGet('admin/structure/views/nojs/handler/numeric_test/page_1/field/count');
    $this->assertFieldByName('options[format_plural_values][0]', '1 time');
    $this->assertFieldByName('options[format_plural_values][1]', '@count times');
    $this->assertFieldByName('options[format_plural_values][2]', '');
    $this->assertFieldByName('options[format_plural_values][3]', '');

    // Assert that changing the settings will change configuration properly.
    $edit = [
      'options[format_plural_values][0]' => '@count time0',
      'options[format_plural_values][1]' => '@count time1',
      'options[format_plural_values][2]' => '@count time2',
      'options[format_plural_values][3]' => '@count time3',
    ];
    $this->drupalPostForm(NULL, $edit, t('Apply'));
    $this->drupalPostForm(NULL, [], t('Save'));
    $config = $this->config('views.view.numeric_test');
    $field_config_prefix = 'display.default.display_options.fields.count.';
    $this->assertEqual($config->get($field_config_prefix . 'format_plural'), TRUE);
    $this->assertEqual($config->get($field_config_prefix . 'format_plural_string'), implode(PoItem::DELIMITER, array_values($edit)));

    // The view should now use the new plural configuration.
    $this->drupalGet('sl/numeric-test');
    $this->assertRaw('<span class="field-content">0 time3</span>');
    $this->assertRaw('<span class="field-content">1 time0</span>');
    $this->assertRaw('<span class="field-content">2 time1</span>');
    $this->assertRaw('<span class="field-content">3 time2</span>');
    $this->assertRaw('<span class="field-content">4 time2</span>');
    $this->assertRaw('<span class="field-content">42 time3</span>');

    // Add an English configuration translation with English plurals.
    $english = \Drupal::languageManager()->getLanguageConfigOverride('en', 'views.view.numeric_test');
    $english->set('display.default.display_options.fields.count.format_plural_string', '1 time' . PoItem::DELIMITER . '@count times')->save();

    // The view displayed in English should use the English translation.
    $this->drupalGet('numeric-test');
    $this->assertRaw('<span class="field-content">0 times</span>');
    $this->assertRaw('<span class="field-content">1 time</span>');
    $this->assertRaw('<span class="field-content">2 times</span>');
    $this->assertRaw('<span class="field-content">3 times</span>');
    $this->assertRaw('<span class="field-content">4 times</span>');
    $this->assertRaw('<span class="field-content">42 times</span>');
  }

  /**
   * Creates and saves a test file.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   A file entity.
   */
  protected function createFile() {
    // Create a new file entity.
    $file = File::create([
      'uid' => 1,
      'filename' => 'druplicon.txt',
      'uri' => 'public://druplicon.txt',
      'filemime' => 'text/plain',
      'created' => 1,
      'changed' => 1,
      'status' => FILE_STATUS_PERMANENT,
    ]);
    file_put_contents($file->getFileUri(), 'hello world');

    // Save it, inserting a new record.
    $file->save();

    return $file;
  }

}

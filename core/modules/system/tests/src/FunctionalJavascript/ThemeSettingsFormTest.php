<?php

namespace Drupal\Tests\system\FunctionalJavascript;

use Behat\Mink\Exception\ElementHtmlException;
use Drupal\file\Entity\File;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests that theme form settings works correctly.
 *
 * @group system
 */
class ThemeSettingsFormTest extends WebDriverTestBase {

  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['file', 'hold_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $admin = $this->drupalCreateUser(['administer themes']);
    $this->drupalLogin($admin);
  }

  /**
   * Tests the live editing of manifest icons.
   */
  public function testIcons() {

    \Drupal::service('theme_installer')->install(['bartik']);

    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->drupalGet('admin/appearance/settings/bartik');

    // Open Manifest section and a 'Add Icon' button appears.
    $this->click('summary[role="button"]:contains("Theme specific manifest settings")');
    $add_button = $assert_session->waitForElement('css', 'input[value="Add Icon"]');
    $this->assertNotEmpty($add_button, 'Opened manifest settings.');

    // Populate the first icon block with 'src' = 'aaa'.
    $add_button->click();
    $src_a = $assert_session->waitForElement('css', 'input[name="manifest_icons[0][fieldset][src]"]');
    $this->assertNotEmpty($src_a, 'First icon block displayed');
    $page->fillField('manifest_icons[0][fieldset][src]', 'aaa');

    $page->findButton('Add Icon')->click();

    // Populate the middle icon block with src='bbb'.
    $src_b = $assert_session->waitForElement('css', 'input[name="manifest_icons[1][fieldset][src]"]');
    $this->assertNotEmpty($src_b, 'Middle icon block displayed.');
    $page->fillField('manifest_icons[1][fieldset][src]', 'bbb');

    $page->findButton('Add Icon')->click();

    // Populate the last  icon block with src='ccc'.
    $src_c = $assert_session->waitForElement('css', 'input[name="manifest_icons[2][fieldset][src]"]');
    $this->assertNotEmpty($src_c, 'Last icon block displayed.');
    $page->fillField('manifest_icons[1][fieldset][src]', 'ccc');

    $page->findButton('Add Icon')->click();

    // Delete the middle icon block (bbb).
    $delete_button = $page->find('css', 'input[data-drupal-selector="edit-manifest-icons-1-operations"]');
    $this->assertNotEmpty($delete_button);

    hold_test_response(TRUE);
    $delete_button->click();
    $throbber = $assert_session->waitForElement('css', '.ajax-progress-throbber');
    $this->assertNotNull($throbber, 'Delete started.');
    hold_test_response(FALSE);
    try {
      $assert_session->assertNoElementAfterWait('css', '.ajax-progress-throbber', 10000, 'Delete end.');
    }
    catch (ElementHtmlException $e) {
      $this->assertTrue(FALSE, 'Delete failed.');
    }

    // Save and verify.
    $page->pressButton('Save configuration');

    // Check that the middle icon block was removed before the configuration
    // was saved.
    /** @var \Drupal\Core\Theme\ManifestGeneratorInterface $manifest_service */
    $manifest_service = \Drupal::service('theme.manifest_generator');
    $manifest = $manifest_service->generateManifest('bartik');

    $icons_expected = [
      0 => ['src' => 'aaa'],
      1 => ['src' => 'ccc'],
    ];
    $icons_actual = $manifest->toArray()['icons'];
    $this->assertSame($icons_expected, $icons_actual, 'The middle icon block was deleted before saving');
  }

  /**
   * Tests that submission handler works correctly.
   *
   * @dataProvider providerTestFormSettingsSubmissionHandler
   */
  public function testFormSettingsSubmissionHandler($theme) {

    \Drupal::service('theme_installer')->install([$theme]);

    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    $this->drupalGet("admin/appearance/settings/$theme");

    // Add a new managed file.
    $file = current($this->getTestFiles('image'));
    $image_file_path = \Drupal::service('file_system')->realpath($file->uri);
    $page->attachFileToField('files[custom_logo]', $image_file_path);
    $assert_session->waitForButton('custom_logo_remove_button');

    // Assert the new file is uploaded as temporary. This file should not be
    // saved as permanent if settings are not submitted.
    $image_field = $this->assertSession()->hiddenFieldExists('custom_logo[fids]');
    $file = File::load($image_field->getValue());
    $this->assertFalse($file->isPermanent());

    $page->pressButton('Save configuration');
    \Drupal::entityTypeManager()->getStorage('file')->resetCache();

    // Assert the uploaded file is saved as permanent.
    $image_field = $this->assertSession()->hiddenFieldExists('custom_logo[fids]');
    $file = File::load($image_field->getValue());
    $this->assertTrue($file->isPermanent());
  }

  /**
   * Provides test data for ::testFormSettingsSubmissionHandler().
   */
  public function providerTestFormSettingsSubmissionHandler() {
    return [
      'test theme.theme' => ['test_theme_theme'],
      'test theme-settings.php' => ['test_theme_settings'],
    ];
  }

}

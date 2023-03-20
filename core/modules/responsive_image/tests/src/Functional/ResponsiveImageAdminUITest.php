<?php

namespace Drupal\Tests\responsive_image\Functional;

use Drupal\responsive_image\ResponsiveImageStyleInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Thoroughly test the administrative interface of the Responsive Image module.
 *
 * @group responsive_image
 */
class ResponsiveImageAdminUITest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'responsive_image',
    'responsive_image_test_module',
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

    $this->drupalLogin($this->drupalCreateUser([
      'administer responsive images',
    ]));
  }

  /**
   * Tests responsive image administration functionality.
   */
  public function testResponsiveImageAdmin() {
    // We start without any default styles.
    $this->drupalGet('admin/config/media/responsive-image-style');
    $this->assertSession()->pageTextContains('There are no responsive image styles yet.');

    // Add a responsive image style.
    $this->drupalGet('admin/config/media/responsive-image-style/add');
    // The 'Responsive Image' breakpoint group should be selected by default.
    $this->assertSession()->fieldValueEquals('breakpoint_group', 'responsive_image');

    // Create a new group.
    $edit = [
      'label' => 'Style One',
      'id' => 'style_one',
      'breakpoint_group' => 'responsive_image',
      'fallback_image_style' => 'thumbnail',
    ];
    $this->drupalGet('admin/config/media/responsive-image-style/add');
    $this->submitForm($edit, 'Save');

    // Check if the new group is created.
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('admin/config/media/responsive-image-style');
    $this->assertSession()->pageTextNotContains('There are no responsive image styles yet.');
    $this->assertSession()->pageTextContains('Style One');

    // Edit the breakpoint_group.
    $this->drupalGet('admin/config/media/responsive-image-style/style_one');
    $this->assertSession()->fieldValueEquals('label', 'Style One');
    $this->assertSession()->fieldValueEquals('breakpoint_group', 'responsive_image');
    $edit = [
      'breakpoint_group' => 'responsive_image_test_module',
    ];
    $this->submitForm($edit, 'Save');

    // Edit the group.
    $this->drupalGet('admin/config/media/responsive-image-style/style_one');
    $this->assertSession()->fieldValueEquals('label', 'Style One');
    $this->assertSession()->fieldValueEquals('breakpoint_group', 'responsive_image_test_module');
    $this->assertSession()->fieldValueEquals('fallback_image_style', 'thumbnail');

    $cases = [
      ['mobile', '1x'],
      ['mobile', '2x'],
      ['narrow', '1x'],
      ['narrow', '2x'],
      ['wide', '1x'],
      ['wide', '2x'],
    ];
    $image_styles = array_merge(
      [ResponsiveImageStyleInterface::EMPTY_IMAGE, ResponsiveImageStyleInterface::ORIGINAL_IMAGE],
      array_keys(image_style_options(FALSE))
    );
    foreach ($cases as $case) {
      // Check if the radio buttons are present.
      $this->assertSession()->fieldExists('keyed_styles[responsive_image_test_module.' . $case[0] . '][' . $case[1] . '][image_mapping_type]');
      // Check if the image style dropdowns are present.
      $this->assertSession()->fieldExists('keyed_styles[responsive_image_test_module.' . $case[0] . '][' . $case[1] . '][image_style]');
      // Check if the sizes textfields are present.
      $this->assertSession()->fieldExists('keyed_styles[responsive_image_test_module.' . $case[0] . '][' . $case[1] . '][sizes]');

      foreach ($image_styles as $image_style_name) {
        // Check if the image styles are available in the dropdowns.
        $this->assertSession()->optionExists('keyed_styles[responsive_image_test_module.' . $case[0] . '][' . $case[1] . '][image_style]', $image_style_name);
        // Check if the image styles checkboxes are present.
        $this->assertSession()->fieldExists('keyed_styles[responsive_image_test_module.' . $case[0] . '][' . $case[1] . '][sizes_image_styles][' . $image_style_name . ']');
      }
    }

    // Save styles for 1x variant only.
    $edit = [
      'label' => 'Style One',
      'breakpoint_group' => 'responsive_image_test_module',
      'fallback_image_style' => 'thumbnail',
      'keyed_styles[responsive_image_test_module.mobile][1x][image_mapping_type]' => 'image_style',
      'keyed_styles[responsive_image_test_module.mobile][1x][image_style]' => 'thumbnail',
      'keyed_styles[responsive_image_test_module.narrow][1x][image_mapping_type]' => 'sizes',
      // Ensure the Sizes field allows long values.
      'keyed_styles[responsive_image_test_module.narrow][1x][sizes]' => '(min-resolution: 192dpi) and (min-width: 170px) 386px, (min-width: 170px) 193px, (min-width: 768px) 18vw, (min-width: 480px) 30vw, 48vw',
      'keyed_styles[responsive_image_test_module.narrow][1x][sizes_image_styles][large]' => 'large',
      'keyed_styles[responsive_image_test_module.narrow][1x][sizes_image_styles][medium]' => 'medium',
      'keyed_styles[responsive_image_test_module.wide][1x][image_mapping_type]' => 'image_style',
      'keyed_styles[responsive_image_test_module.wide][1x][image_style]' => 'large',
    ];
    $this->drupalGet('admin/config/media/responsive-image-style/style_one');
    $this->submitForm($edit, 'Save');
    $this->drupalGet('admin/config/media/responsive-image-style/style_one');

    // Check the mapping for multipliers 1x and 2x for the mobile breakpoint.
    $this->assertSession()->fieldValueEquals('keyed_styles[responsive_image_test_module.mobile][1x][image_style]', 'thumbnail');
    $this->assertSession()->fieldValueEquals('keyed_styles[responsive_image_test_module.mobile][1x][image_mapping_type]', 'image_style');
    $this->assertSession()->fieldValueEquals('keyed_styles[responsive_image_test_module.mobile][2x][image_mapping_type]', '_none');

    // Check the mapping for multipliers 1x and 2x for the narrow breakpoint.
    $this->assertSession()->fieldValueEquals('keyed_styles[responsive_image_test_module.narrow][1x][image_mapping_type]', 'sizes');
    $this->assertSession()->fieldValueEquals('keyed_styles[responsive_image_test_module.narrow][1x][sizes]', '(min-resolution: 192dpi) and (min-width: 170px) 386px, (min-width: 170px) 193px, (min-width: 768px) 18vw, (min-width: 480px) 30vw, 48vw');
    $this->assertSession()->checkboxChecked('edit-keyed-styles-responsive-image-test-modulenarrow-1x-sizes-image-styles-large');
    $this->assertSession()->checkboxChecked('edit-keyed-styles-responsive-image-test-modulenarrow-1x-sizes-image-styles-medium');
    $this->assertSession()->checkboxNotChecked('edit-keyed-styles-responsive-image-test-modulenarrow-1x-sizes-image-styles-thumbnail');
    $this->assertSession()->fieldValueEquals('keyed_styles[responsive_image_test_module.narrow][2x][image_mapping_type]', '_none');

    // Check the mapping for multipliers 1x and 2x for the wide breakpoint.
    $this->assertSession()->fieldValueEquals('keyed_styles[responsive_image_test_module.wide][1x][image_style]', 'large');
    $this->assertSession()->fieldValueEquals('keyed_styles[responsive_image_test_module.wide][1x][image_mapping_type]', 'image_style');
    $this->assertSession()->fieldValueEquals('keyed_styles[responsive_image_test_module.wide][2x][image_mapping_type]', '_none');

    // Delete the style.
    $this->drupalGet('admin/config/media/responsive-image-style/style_one/delete');
    $this->submitForm([], 'Delete');
    $this->drupalGet('admin/config/media/responsive-image-style');
    $this->assertSession()->pageTextContains('There are no responsive image styles yet.');
  }

}

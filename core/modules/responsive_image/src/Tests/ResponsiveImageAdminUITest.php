<?php

/**
 * @file
 * Contains \Drupal\responsive_image\Tests\ResponsiveImageAdminUITest.
 */

namespace Drupal\responsive_image\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Thoroughly test the administrative interface of the Responsive Image module.
 *
 * @group responsive_image
 */
class ResponsiveImageAdminUITest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('responsive_image', 'responsive_image_test_module');

  /**
   * Drupal\simpletest\WebTestBase\setUp().
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalLogin($this->drupalCreateUser([
      'administer responsive images',
    ]));
  }

  /**
   * Test responsive image administration functionality.
   */
  public function testResponsiveImageAdmin() {
    // We start without any default styles.
    $this->drupalGet('admin/config/media/responsive-image-style');
    $this->assertText('There is no Responsive image style yet.');

    // Add a responsive image style.
    $this->drupalGet('admin/config/media/responsive-image-style/add');
    // The 'Responsive Image' breakpoint group should be selected by default.
    $this->assertFieldByName('breakpoint_group', 'responsive_image');

    // Create a new group.
    $edit = array(
      'label' => 'Style One',
      'id' => 'style_one',
      'breakpoint_group' => 'responsive_image_test_module',
      'fallback_image_style' => 'thumbnail',
    );
    $this->drupalPostForm('admin/config/media/responsive-image-style/add', $edit, t('Save'));

    // Check if the new group is created.
    $this->assertResponse(200);
    $this->drupalGet('admin/config/media/responsive-image-style');
    $this->assertNoText('There is no Responsive image style yet.');
    $this->assertText('Style One');
    $this->assertText('style_one');

    // Edit the group.
    $this->drupalGet('admin/config/media/responsive-image-style/style_one');
    $this->assertFieldByName('label', 'Style One');
    $this->assertFieldByName('breakpoint_group', 'responsive_image_test_module');
    $this->assertFieldByName('fallback_image_style', 'thumbnail');

    $cases = array(
      array('mobile', '1x'),
      array('mobile', '2x'),
      array('narrow', '1x'),
      array('narrow', '2x'),
      array('wide', '1x'),
      array('wide', '2x'),
    );
    $image_styles = array_merge(
      [RESPONSIVE_IMAGE_EMPTY_IMAGE, RESPONSIVE_IMAGE_ORIGINAL_IMAGE],
      array_keys(image_style_options(FALSE))
    );
    foreach ($cases as $case) {
      // Check if the radio buttons are present.
      $this->assertFieldByName('keyed_styles[responsive_image_test_module.' . $case[0] . '][' . $case[1] . '][image_mapping_type]', '');
      // Check if the image style dropdowns are present.
      $this->assertFieldByName('keyed_styles[responsive_image_test_module.' . $case[0] . '][' . $case[1] . '][image_style]', '');
      // Check if the sizes textfields are present.
      $this->assertFieldByName('keyed_styles[responsive_image_test_module.' . $case[0] . '][' . $case[1] . '][sizes]', '');

      foreach ($image_styles as $image_style_name) {
        // Check if the image styles are available in the dropdowns.
        $this->assertTrue($this->xpath(
          '//select[@name=:name]//option[@value=:style]',
          [
            ':name' => 'keyed_styles[responsive_image_test_module.' . $case[0] . '][' . $case[1] . '][image_style]',
            ':style' => $image_style_name,
          ]
        ));
        // Check if the image styles checkboxes are present.
        $this->assertFieldByName('keyed_styles[responsive_image_test_module.' . $case[0] . '][' . $case[1] . '][sizes_image_styles][' . $image_style_name . ']');
      }
    }

    // Save styles for 1x variant only.
    $edit = array(
      'label' => 'Style One',
      'breakpoint_group' => 'responsive_image_test_module',
      'fallback_image_style' => 'thumbnail',
      'keyed_styles[responsive_image_test_module.mobile][1x][image_mapping_type]' => 'image_style',
      'keyed_styles[responsive_image_test_module.mobile][1x][image_style]' => 'thumbnail',
      'keyed_styles[responsive_image_test_module.narrow][1x][image_mapping_type]' => 'sizes',
      'keyed_styles[responsive_image_test_module.narrow][1x][sizes]' => '(min-width: 700px) 700px, 100vw',
      'keyed_styles[responsive_image_test_module.narrow][1x][sizes_image_styles][large]' => 'large',
      'keyed_styles[responsive_image_test_module.narrow][1x][sizes_image_styles][medium]' => 'medium',
      'keyed_styles[responsive_image_test_module.wide][1x][image_mapping_type]' => 'image_style',
      'keyed_styles[responsive_image_test_module.wide][1x][image_style]' => 'large',
    );
    $this->drupalPostForm('admin/config/media/responsive-image-style/style_one', $edit, t('Save'));
    $this->drupalGet('admin/config/media/responsive-image-style/style_one');

    // Check the mapping for multipliers 1x and 2x for the mobile breakpoint.
    $this->assertFieldByName('keyed_styles[responsive_image_test_module.mobile][1x][image_style]', 'thumbnail');
    $this->assertFieldByName('keyed_styles[responsive_image_test_module.mobile][1x][image_mapping_type]', 'image_style');
    $this->assertFieldByName('keyed_styles[responsive_image_test_module.mobile][2x][image_mapping_type]', '_none');

    // Check the mapping for multipliers 1x and 2x for the narrow breakpoint.
    $this->assertFieldByName('keyed_styles[responsive_image_test_module.narrow][1x][image_mapping_type]', 'sizes');
    $this->assertFieldByName('keyed_styles[responsive_image_test_module.narrow][1x][sizes]', '(min-width: 700px) 700px, 100vw');
    $this->assertFieldChecked('edit-keyed-styles-responsive-image-test-modulenarrow-1x-sizes-image-styles-large');
    $this->assertFieldChecked('edit-keyed-styles-responsive-image-test-modulenarrow-1x-sizes-image-styles-medium');
    $this->assertNoFieldChecked('edit-keyed-styles-responsive-image-test-modulenarrow-1x-sizes-image-styles-thumbnail');
    $this->assertFieldByName('keyed_styles[responsive_image_test_module.narrow][2x][image_mapping_type]', '_none');

    // Check the mapping for multipliers 1x and 2x for the wide breakpoint.
    $this->assertFieldByName('keyed_styles[responsive_image_test_module.wide][1x][image_style]', 'large');
    $this->assertFieldByName('keyed_styles[responsive_image_test_module.wide][1x][image_mapping_type]', 'image_style');
    $this->assertFieldByName('keyed_styles[responsive_image_test_module.wide][2x][image_mapping_type]', '_none');

    // Delete the style.
    $this->drupalGet('admin/config/media/responsive-image-style/style_one/delete');
    $this->drupalPostForm(NULL, array(), t('Delete'));
    $this->drupalGet('admin/config/media/responsive-image-style');
    $this->assertText('There is no Responsive image style yet.');
  }

}

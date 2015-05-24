<?php

/**
 * @file
 * Definition of Drupal\responsive_image\Tests\ResponsiveImageAdminUITest.
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

    // Add a new responsive image style, our breakpoint set should be selected.
    $this->drupalGet('admin/config/media/responsive-image-style/add');
    $this->assertFieldByName('breakpoint_group', 'responsive_image_test_module');

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

    foreach ($cases as $case) {
      // Check if the radio buttons are present.
      $this->assertFieldByName('keyed_styles[responsive_image_test_module.' . $case[0] . '][' . $case[1] . '][image_mapping]', '');
    }

    // Save styles for 1x variant only.
    $edit = array(
      'label' => 'Style One',
      'breakpoint_group' => 'responsive_image_test_module',
      'fallback_image_style' => 'thumbnail',
      'keyed_styles[responsive_image_test_module.mobile][1x][image_mapping]' => 'thumbnail',
      'keyed_styles[responsive_image_test_module.narrow][1x][image_mapping]' => 'medium',
      'keyed_styles[responsive_image_test_module.wide][1x][image_mapping]' => 'large',
    );
    $this->drupalPostForm('admin/config/media/responsive-image-style/style_one', $edit, t('Save'));
    $this->drupalGet('admin/config/media/responsive-image-style/style_one');

    // Check the style for multipliers 1x and 2x for the mobile breakpoint.
    $this->assertFieldByName('keyed_styles[responsive_image_test_module.mobile][1x][image_mapping]', 'thumbnail');
    $this->assertFieldByName('keyed_styles[responsive_image_test_module.mobile][2x][image_mapping]', '');

    // Check the style for multipliers 1x and 2x for the narrow breakpoint.
    $this->assertFieldByName('keyed_styles[responsive_image_test_module.narrow][1x][image_mapping]', 'medium');
    $this->assertFieldByName('keyed_styles[responsive_image_test_module.narrow][2x][image_mapping]', '');

    // Check the style for multipliers 1x and 2x for the wide breakpoint.
    $this->assertFieldByName('keyed_styles[responsive_image_test_module.wide][1x][image_mapping]', 'large');
    $this->assertFieldByName('keyed_styles[responsive_image_test_module.wide][2x][image_mapping]', '');

    // Delete the style.
    $this->drupalGet('admin/config/media/responsive-image-style/style_one/delete');
    $this->drupalPostForm(NULL, array(), t('Delete'));
    $this->drupalGet('admin/config/media/responsive-image-style');
    $this->assertText('There is no Responsive image style yet.');
  }

}

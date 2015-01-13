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
    // We start without any default mappings.
    $this->drupalGet('admin/config/media/responsive-image-mapping');
    $this->assertText('There is no Responsive image mapping yet.');

    // Add a new responsive image mapping, our breakpoint set should be selected.
    $this->drupalGet('admin/config/media/responsive-image-mapping/add');
    $this->assertFieldByName('breakpointGroup', 'responsive_image_test_module');

    // Create a new group.
    $edit = array(
      'label' => 'Mapping One',
      'id' => 'mapping_one',
      'breakpointGroup' => 'responsive_image_test_module',
    );
    $this->drupalPostForm('admin/config/media/responsive-image-mapping/add', $edit, t('Save'));

    // Check if the new group is created.
    $this->assertResponse(200);
    $this->drupalGet('admin/config/media/responsive-image-mapping');
    $this->assertNoText('There is no Responsive image mapping yet.');
    $this->assertText('Mapping One');
    $this->assertText('mapping_one');

    // Edit the group.
    $this->drupalGet('admin/config/media/responsive-image-mapping/mapping_one');
    $this->assertFieldByName('label', 'Mapping One');
    $this->assertFieldByName('breakpointGroup', 'responsive_image_test_module');

    // Check if the dropdowns are present for the mappings.
    $this->assertFieldByName('keyed_mappings[responsive_image_test_module.mobile][1x]', '');
    $this->assertFieldByName('keyed_mappings[responsive_image_test_module.mobile][2x]', '');
    $this->assertFieldByName('keyed_mappings[responsive_image_test_module.narrow][1x]', '');
    $this->assertFieldByName('keyed_mappings[responsive_image_test_module.narrow][2x]', '');
    $this->assertFieldByName('keyed_mappings[responsive_image_test_module.wide][1x]', '');
    $this->assertFieldByName('keyed_mappings[responsive_image_test_module.wide][2x]', '');

    // Save mappings for 1x variant only.
    $edit = array(
      'label' => 'Mapping One',
      'breakpointGroup' => 'responsive_image_test_module',
      'keyed_mappings[responsive_image_test_module.mobile][1x]' => 'thumbnail',
      'keyed_mappings[responsive_image_test_module.narrow][1x]' => 'medium',
      'keyed_mappings[responsive_image_test_module.wide][1x]' => 'large',
    );
    $this->drupalPostForm('admin/config/media/responsive-image-mapping/mapping_one', $edit, t('Save'));
    $this->drupalGet('admin/config/media/responsive-image-mapping/mapping_one');
    $this->assertFieldByName('keyed_mappings[responsive_image_test_module.mobile][1x]', 'thumbnail');
    $this->assertFieldByName('keyed_mappings[responsive_image_test_module.mobile][2x]', '');
    $this->assertFieldByName('keyed_mappings[responsive_image_test_module.narrow][1x]', 'medium');
    $this->assertFieldByName('keyed_mappings[responsive_image_test_module.narrow][2x]', '');
    $this->assertFieldByName('keyed_mappings[responsive_image_test_module.wide][1x]', 'large');
    $this->assertFieldByName('keyed_mappings[responsive_image_test_module.wide][2x]', '');

    // Delete the mapping.
    $this->drupalGet('admin/config/media/responsive-image-mapping/mapping_one/delete');
    $this->drupalPostForm(NULL, array(), t('Delete'));
    $this->drupalGet('admin/config/media/responsive-image-mapping');
    $this->assertText('There is no Responsive image mapping yet.');
  }

}

<?php

/**
 * @file
 * Definition of Drupal\responsive_image\Tests\ResponsiveImageFieldDisplayTest.
 */

namespace Drupal\responsive_image\Tests;

use Drupal\Core\Language\Language;
use Drupal\breakpoint\Entity\Breakpoint;
use Drupal\image\Tests\ImageFieldTestBase;

/**
 * Test class to check that formatters and display settings are working.
 */
class ResponsiveImageFieldDisplayTest extends ImageFieldTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('field_ui', 'responsive_image');

  /**
   * Drupal\simpletest\WebTestBase\getInfo().
   */
  public static function getInfo() {
    return array(
      'name' => 'Responsive Image field display tests',
      'description' => 'Test responsive image display formatter.',
      'group' => 'Responsive Image',
    );
  }

  /**
   * Drupal\simpletest\WebTestBase\setUp().
   */
  public function setUp() {
    parent::setUp();

    // Create user.
    $this->admin_user = $this->drupalCreateUser(array(
      'administer responsive image',
      'access content',
      'access administration pages',
      'administer site configuration',
      'administer content types',
      'administer node display',
      'administer nodes',
      'create article content',
      'edit any article content',
      'delete any article content',
      'administer image styles'
    ));
    $this->drupalLogin($this->admin_user);

    // Add breakpoint_group and breakpoints.
    $breakpoint_group = entity_create('breakpoint_group', array(
      'id' => 'atestset',
      'label' => 'A test set',
      'sourceType' => Breakpoint::SOURCE_TYPE_USER_DEFINED,
    ));

    $breakpoint_names = array('small', 'medium', 'large');
    for ($i = 0; $i < 3; $i++) {
      $width = ($i + 1) * 200;
      $breakpoint = entity_create('breakpoint', array(
        'name' => $breakpoint_names[$i],
        'mediaQuery' => "(min-width: {$width}px)",
        'source' => 'user',
        'sourceType' => Breakpoint::SOURCE_TYPE_USER_DEFINED,
        'multipliers' => array(
          '1.5x' => 0,
          '2x' => '2x',
        ),
      ));
      $breakpoint->save();
      $breakpoint_group->addBreakpoints(array($breakpoint));
    }
    $breakpoint_group->save();

    // Add responsive image mapping.
    $responsive_image_mapping = entity_create('responsive_image_mapping', array(
      'id' => 'mapping_one',
      'label' => 'Mapping One',
      'breakpointGroup' => 'atestset',
    ));
    $responsive_image_mapping->save();
    $responsive_image_mapping->mappings['custom.user.small']['1x'] = 'thumbnail';
    $responsive_image_mapping->mappings['custom.user.medium']['1x'] = 'medium';
    $responsive_image_mapping->mappings['custom.user.large']['1x'] = 'large';
    $responsive_image_mapping->save();
  }

  /**
   * Test responsive image formatters on node display for public files.
   */
  public function testResponsiveImageFieldFormattersPublic() {
    $this->_testResponsiveImageFieldFormatters('public');
  }

  /**
   * Test responsive image formatters on node display for private files.
   */
  public function testResponsiveImageFieldFormattersPrivate() {
    // Remove access content permission from anonymous users.
    user_role_change_permissions(DRUPAL_ANONYMOUS_RID, array('access content' => FALSE));
    $this->_testResponsiveImageFieldFormatters('private');
  }

  /**
   * Test responsive image formatters on node display.
   */
  public function _testResponsiveImageFieldFormatters($scheme) {
    $field_name = drupal_strtolower($this->randomName());
    $this->createImageField($field_name, 'article', array('uri_scheme' => $scheme));
    // Create a new node with an image attached.
    $test_image = current($this->drupalGetTestFiles('image'));
    $nid = $this->uploadNodeImage($test_image, $field_name, 'article');
    $node = node_load($nid, TRUE);

    // Test that the default formatter is being used.
    $image_uri = file_load($node->{$field_name}->target_id)->getFileUri();
    $image = array(
      '#theme' => 'image',
      '#uri' => $image_uri,
      '#width' => 40,
      '#height' => 20,
    );
    $default_output = drupal_render($image);
    $this->assertRaw($default_output, 'Default formatter displaying correctly on full node view.');

    // Use the responsive image formatter linked to file formatter.
    $display_options = array(
      'type' => 'responsive_image',
      'module' => 'responsive_image',
      'settings' => array('image_link' => 'file'),
    );
    $display = entity_get_display('node', 'article', 'default');
    $display->setComponent($field_name, $display_options)
      ->save();

    $image = array(
      '#theme' => 'image',
      '#uri' => $image_uri,
      '#width' => 40,
      '#height' => 20,
    );
    $default_output = l($image, file_create_url($image_uri), array('html' => TRUE));
    $this->drupalGet('node/' . $nid);
    $this->assertRaw($default_output, 'Image linked to file formatter displaying correctly on full node view.');
    // Verify that the image can be downloaded.
    $this->assertEqual(file_get_contents($test_image->uri), $this->drupalGet(file_create_url($image_uri)), 'File was downloaded successfully.');
    if ($scheme == 'private') {
      // Only verify HTTP headers when using private scheme and the headers are
      // sent by Drupal.
      $this->assertEqual($this->drupalGetHeader('Content-Type'), 'image/png', 'Content-Type header was sent.');
      $this->assertTrue(strstr($this->drupalGetHeader('Cache-Control'), 'private') !== FALSE, 'Cache-Control header was sent.');

      // Log out and try to access the file.
      $this->drupalLogout();
      $this->drupalGet(file_create_url($image_uri));
      $this->assertResponse('403', 'Access denied to original image as anonymous user.');

      // Log in again.
      $this->drupalLogin($this->admin_user);
    }

    // Use the responsive image formatter with a responsive image mapping.
    $display_options['settings']['responsive_image_mapping'] = 'mapping_one';
    $display->setComponent($field_name, $display_options)
      ->save();

    // Output should contain all image styles and all breakpoints.
    $this->drupalGet('node/' . $nid);
    $this->assertRaw('/styles/thumbnail/');
    $this->assertRaw('/styles/medium/');
    $this->assertRaw('/styles/large/');
    $this->assertRaw('media="(min-width: 200px)"');
    $this->assertRaw('media="(min-width: 400px)"');
    $this->assertRaw('media="(min-width: 600px)"');

    // Test the fallback image style.
    $display_options['settings']['image_link'] = '';
    $display_options['settings']['fallback_image_style'] = 'large';
    $display->setComponent($field_name, $display_options)
      ->save();

    $large_style = entity_load('image_style', 'large');
    $this->drupalGet($large_style->buildUrl($image_uri));
    $image_style = array(
      '#theme' => 'image_style',
      '#uri' => $image_uri,
      '#width' => 480,
      '#height' => 240,
      '#style_name' => 'large',
    );
    $default_output = '<noscript>' . drupal_render($image_style) . '</noscript>';
    $this->drupalGet('node/' . $nid);
    $this->assertRaw($default_output, 'Image style thumbnail formatter displaying correctly on full node view.');

    if ($scheme == 'private') {
      // Log out and try to access the file.
      $this->drupalLogout();
      $this->drupalGet($large_style->buildUrl($image_uri));
      $this->assertResponse('403', 'Access denied to image style thumbnail as anonymous user.');
    }
  }

}

<?php

/**
 * @file
 * Contains \Drupal\responsive_image\Tests\ResponsiveImageFieldDisplayTest.
 */

namespace Drupal\responsive_image\Tests;

use Drupal\Component\Utility\Unicode;
use Drupal\image\Tests\ImageFieldTestBase;
use Drupal\image\Entity\ImageStyle;
use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;
use Drupal\responsive_image\Plugin\Field\FieldFormatter\ResponsiveImageFormatter;
use Drupal\user\RoleInterface;

/**
 * Tests responsive image display formatter.
 *
 * @group responsive_image
 */
class ResponsiveImageFieldDisplayTest extends ImageFieldTestBase {

  protected $dumpHeaders = TRUE;

  /**
   * Responsive image style entity instance we test with.
   *
   * @var \Drupal\responsive_image\Entity\ResponsiveImageStyle
   */
  protected $responsiveImgStyle;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('field_ui', 'responsive_image', 'responsive_image_test_module');

  /**
   * Drupal\simpletest\WebTestBase\setUp().
   */
  protected function setUp() {
    parent::setUp();

    // Create user.
    $this->adminUser = $this->drupalCreateUser(array(
      'administer responsive images',
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
    $this->drupalLogin($this->adminUser);
    // Add responsive image style.
    $this->responsiveImgStyle = entity_create('responsive_image_style', array(
      'id' => 'style_one',
      'label' => 'Style One',
      'breakpoint_group' => 'responsive_image_test_module',
      'fallback_image_style' => 'large',
    ));
  }

  /**
   * Tests responsive image formatters on node display for public files.
   */
  public function testResponsiveImageFieldFormattersPublic() {
    $this->addTestImageStyleMappings();
    $this->doTestResponsiveImageFieldFormatters('public');
  }

  /**
   * Tests responsive image formatters on node display for private files.
   */
  public function testResponsiveImageFieldFormattersPrivate() {
    $this->addTestImageStyleMappings();
    // Remove access content permission from anonymous users.
    user_role_change_permissions(RoleInterface::ANONYMOUS_ID, array('access content' => FALSE));
    $this->doTestResponsiveImageFieldFormatters('private');
  }

  /**
   * Test responsive image formatters when image style is empty.
   */
  public function testResponsiveImageFieldFormattersEmptyStyle() {
    $this->addTestImageStyleMappings(TRUE);
    $this->doTestResponsiveImageFieldFormatters('public', TRUE);
  }

  /**
   * Add image style mappings to the responsive image style entity.
   *
   * @param bool $empty_styles
   *   If true, the image style mappings will get empty image styles.
   */
  protected function addTestImageStyleMappings($empty_styles = FALSE) {
    if ($empty_styles) {
      $this->responsiveImgStyle
        ->addImageStyleMapping('responsive_image_test_module.mobile', '1x', array(
          'image_mapping_type' => 'image_style',
          'image_mapping' => '',
        ))
        ->addImageStyleMapping('responsive_image_test_module.narrow', '1x', array(
          'image_mapping_type' => 'sizes',
          'image_mapping' => array(
            'sizes' => '(min-width: 700px) 700px, 100vw',
            'sizes_image_styles' => array(),
          ),
        ))
        ->addImageStyleMapping('responsive_image_test_module.wide', '1x', array(
          'image_mapping_type' => 'image_style',
          'image_mapping' => '',
        ))
        ->save();
    }
    else {
      $this->responsiveImgStyle
        // Test the output of an empty image.
        ->addImageStyleMapping('responsive_image_test_module.mobile', '1x', array(
          'image_mapping_type' => 'image_style',
          'image_mapping' => RESPONSIVE_IMAGE_EMPTY_IMAGE,
        ))
        // Test the output with a 1.5x multiplier.
        ->addImageStyleMapping('responsive_image_test_module.mobile', '1.5x', array(
          'image_mapping_type' => 'image_style',
          'image_mapping' => 'thumbnail',
        ))
        // Test the output of the 'sizes' attribute.
        ->addImageStyleMapping('responsive_image_test_module.narrow', '1x', array(
          'image_mapping_type' => 'sizes',
          'image_mapping' => array(
            'sizes' => '(min-width: 700px) 700px, 100vw',
            'sizes_image_styles' => array(
              'large',
              'medium',
            ),
          ),
        ))
        // Test the normal output of mapping to an image style.
        ->addImageStyleMapping('responsive_image_test_module.wide', '1x', array(
          'image_mapping_type' => 'image_style',
          'image_mapping' => 'large',
        ))
        ->save();
    }
  }
  /**
   * Test responsive image formatters on node display.
   *
   * If the empty styles param is set, then the function only tests for the
   * fallback image style (large).
   *
   * @param string $scheme
   *   File scheme to use.
   * @param bool $empty_styles
   *   If true, use an empty string for image style names.
   * Defaults to false.
   */
  protected function doTestResponsiveImageFieldFormatters($scheme, $empty_styles = FALSE) {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');
    $node_storage = $this->container->get('entity.manager')->getStorage('node');
    $field_name = Unicode::strtolower($this->randomMachineName());
    $this->createImageField($field_name, 'article', array('uri_scheme' => $scheme));
    // Create a new node with an image attached. Make sure we use a large image
    // so the scale effects of the image styles always have an effect.
    $test_image = current($this->drupalGetTestFiles('image', 39325));

    // Create alt text for the image.
    $alt = $this->randomMachineName();

    $nid = $this->uploadNodeImage($test_image, $field_name, 'article', $alt);
    $node_storage->resetCache(array($nid));
    $node = $node_storage->load($nid);

    // Test that the default formatter is being used.
    $image_uri = File::load($node->{$field_name}->target_id)->getFileUri();
    $image = array(
      '#theme' => 'image',
      '#uri' => $image_uri,
      '#width' => 360,
      '#height' => 240,
      '#alt' => $alt,
    );
    $default_output = str_replace("\n", NULL, $renderer->renderRoot($image));
    $this->assertRaw($default_output, 'Default formatter displaying correctly on full node view.');

    // Test field not being configured. This should not cause a fatal error.
    $display_options = array(
      'type' => 'responsive_image_test',
      'settings' => ResponsiveImageFormatter::defaultSettings(),
    );
    $display = $this->container->get('entity.manager')
      ->getStorage('entity_view_display')
      ->load('node.article.default');
    if (!$display) {
      $values = [
        'targetEntityType' => 'node',
        'bundle' => 'article',
        'mode' => 'default',
        'status' => TRUE,
      ];
      $display = $this->container->get('entity.manager')->getStorage('entity_view_display')->create($values);
    }
    $display->setComponent($field_name, $display_options)->save();

    $this->drupalGet('node/' . $nid);

    // Test theme function for responsive image, but using the test formatter.
    $display_options = array(
      'type' => 'responsive_image_test',
      'settings' => array(
        'image_link' => 'file',
        'responsive_image_style' => 'style_one',
      ),
    );
    $display = entity_get_display('node', 'article', 'default');
    $display->setComponent($field_name, $display_options)
      ->save();

    $this->drupalGet('node/' . $nid);

    // Use the responsive image formatter linked to file formatter.
    $display_options = array(
      'type' => 'responsive_image',
      'settings' => array(
        'image_link' => 'file',
        'responsive_image_style' => 'style_one',
      ),
    );
    $display = entity_get_display('node', 'article', 'default');
    $display->setComponent($field_name, $display_options)
      ->save();

    $default_output = '<a href="' . file_create_url($image_uri) . '"><picture';
    $this->drupalGet('node/' . $nid);
    $cache_tags_header = $this->drupalGetHeader('X-Drupal-Cache-Tags');
    $this->assertTrue(!preg_match('/ image_style\:/', $cache_tags_header), 'No image style cache tag found.');

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
      $this->drupalLogin($this->adminUser);
    }

    // Use the responsive image formatter with a responsive image style.
    $display_options['settings']['responsive_image_style'] = 'style_one';
    $display_options['settings']['image_link'] = '';
    $display->setComponent($field_name, $display_options)
      ->save();

    // Create a derivative so at least one MIME type will be known.
    $large_style = ImageStyle::load('large');
    $large_style->createDerivative($image_uri, $large_style->buildUri($image_uri));

    // Output should contain all image styles and all breakpoints.
    $this->drupalGet('node/' . $nid);
    if (!$empty_styles) {
      $this->assertRaw('/styles/medium/');
      // Make sure the IE9 workaround is present.
      $this->assertRaw('<!--[if IE 9]><video style="display: none;"><![endif]-->');
      $this->assertRaw('<!--[if IE 9]></video><![endif]-->');
      // Assert the empty image is present.
      $this->assertRaw('data:image/gif;base64,R0lGODlhAQABAIABAP///wAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==');
      $thumbnail_style = ImageStyle::load('thumbnail');
      // Assert the output of the 'srcset' attribute (small multipliers first).
      $this->assertRaw('data:image/gif;base64,R0lGODlhAQABAIABAP///wAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw== 1x, ' . $thumbnail_style->buildUrl($image_uri) . ' 1.5x');
      $this->assertRaw('/styles/medium/');
      // Assert the output of the breakpoints.
      $this->assertRaw('media="(min-width: 0px)"');
      $this->assertRaw('media="(min-width: 560px)"');
      // Assert the output of the 'sizes' attribute.
      $this->assertRaw('sizes="(min-width: 700px) 700px, 100vw"');
      $this->assertPattern('/media="\(min-width: 560px\)".+?sizes="\(min-width: 700px\) 700px, 100vw"/');
      // Assert the output of the 'srcset' attribute (small images first).
      $medium_style = ImageStyle::load('medium');
      $this->assertRaw($medium_style->buildUrl($image_uri) . ' 220w, ' . $large_style->buildUrl($image_uri) . ' 360w');
      $this->assertRaw('media="(min-width: 851px)"');
    }
    $this->assertRaw('/styles/large/');
    $cache_tags = explode(' ', $this->drupalGetHeader('X-Drupal-Cache-Tags'));
    $this->assertTrue(in_array('config:responsive_image.styles.style_one', $cache_tags));
    if (!$empty_styles) {
      $this->assertTrue(in_array('config:image.style.medium', $cache_tags));
      $this->assertTrue(in_array('config:image.style.thumbnail', $cache_tags));
      $this->assertRaw('type="image/png"');
    }
    $this->assertTrue(in_array('config:image.style.large', $cache_tags));

    // Test the fallback image style.
    $image = \Drupal::service('image.factory')->get($image_uri);
    $fallback_image = array(
      '#theme' => 'image',
      '#alt' => $alt,
      '#srcset' => array(
        array(
          'uri' => $large_style->buildUrl($image->getSource()),
        ),
      ),
    );
    // The image.html.twig template has a newline after the <img> tag but
    // responsive-image.html.twig doesn't have one after the fallback image, so
    // we remove it here.
    $default_output = trim($renderer->renderRoot($fallback_image));
    $this->assertRaw($default_output, 'Image style large formatter displaying correctly on full node view.');

    if ($scheme == 'private') {
      // Log out and try to access the file.
      $this->drupalLogout();
      $this->drupalGet($large_style->buildUrl($image_uri));
      $this->assertResponse('403', 'Access denied to image style large as anonymous user.');
      $cache_tags_header = $this->drupalGetHeader('X-Drupal-Cache-Tags');
      $this->assertTrue(!preg_match('/ image_style\:/', $cache_tags_header), 'No image style cache tag found.');
    }
  }

  /**
   * Tests responsive image formatters on node display linked to the file.
   */
  public function testResponsiveImageFieldFormattersLinkToFile() {
    $this->addTestImageStyleMappings();
    $this->assertResponsiveImageFieldFormattersLink('file');
  }

  /**
   * Tests responsive image formatters on node display linked to the node.
   */
  public function testResponsiveImageFieldFormattersLinkToNode() {
    $this->addTestImageStyleMappings();
    $this->assertResponsiveImageFieldFormattersLink('content');
  }

  /**
   * Tests responsive image formatter on node display with an empty media query.
   */
  public function testResponsiveImageFieldFormattersEmptyMediaQuery() {
    $this->responsiveImgStyle
      // Test the output of an empty media query.
      ->addImageStyleMapping('responsive_image_test_module.empty', '1x', array(
        'image_mapping_type' => 'image_style',
        'image_mapping' => RESPONSIVE_IMAGE_EMPTY_IMAGE,
      ))
      // Test the output with a 1.5x multiplier.
      ->addImageStyleMapping('responsive_image_test_module.mobile', '1x', array(
        'image_mapping_type' => 'image_style',
        'image_mapping' => 'thumbnail',
      ))
      ->save();
    $node_storage = $this->container->get('entity.manager')->getStorage('node');
    $field_name = Unicode::strtolower($this->randomMachineName());
    $this->createImageField($field_name, 'article', array('uri_scheme' => 'public'));
    // Create a new node with an image attached.
    $test_image = current($this->drupalGetTestFiles('image'));
    $nid = $this->uploadNodeImage($test_image, $field_name, 'article', $this->randomMachineName());
    $node_storage->resetCache(array($nid));

    // Use the responsive image formatter linked to file formatter.
    $display_options = array(
      'type' => 'responsive_image',
      'settings' => array(
        'image_link' => '',
        'responsive_image_style' => 'style_one',
      ),
    );
    $display = entity_get_display('node', 'article', 'default');
    $display->setComponent($field_name, $display_options)
      ->save();

    // View the node.
    $this->drupalGet('node/' . $nid);

    // Assert an empty media attribute is not output.
    $this->assertNoPattern('@srcset="data:image/gif;base64,R0lGODlhAQABAIABAP///wAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw== 1x".+?media=".+?/><source@');

    // Assert the media attribute is present if it has a value.
    $thumbnail_style = ImageStyle::load('thumbnail');
    $node = $node_storage->load($nid);
    $image_uri = File::load($node->{$field_name}->target_id)->getFileUri();
    $this->assertPattern('/srcset="' . preg_quote($thumbnail_style->buildUrl($image_uri), '/') . ' 1x".+?media="\(min-width: 0px\)"/');
  }

  /**
   * Tests responsive image formatters linked to the file or node.
   *
   * @param string $link_type
   *   The link type to test. Either 'file' or 'content'.
   */
  private function assertResponsiveImageFieldFormattersLink($link_type) {
    $field_name = Unicode::strtolower($this->randomMachineName());
    $field_settings = array('alt_field_required' => 0);
    $this->createImageField($field_name, 'article', array('uri_scheme' => 'public'), $field_settings);
    // Create a new node with an image attached.
    $test_image = current($this->drupalGetTestFiles('image'));

    // Test the image linked to file formatter.
    $display_options = array(
      'type' => 'responsive_image',
      'settings' => array(
        'image_link' => $link_type,
        'responsive_image_style' => 'style_one',
      ),
    );
    entity_get_display('node', 'article', 'default')
      ->setComponent($field_name, $display_options)
      ->save();
    // Ensure that preview works.
    $this->previewNodeImage($test_image, $field_name, 'article');

    // Look for a picture tag in the preview output
    $this->assertPattern('/picture/');

    $nid = $this->uploadNodeImage($test_image, $field_name, 'article');
    $this->container->get('entity.manager')->getStorage('node')->resetCache(array($nid));
    $node = Node::load($nid);

    // Use the responsive image formatter linked to file formatter.
    $display_options = array(
      'type' => 'responsive_image',
      'settings' => array(
        'image_link' => $link_type,
        'responsive_image_style' => 'style_one',
      ),
    );
    entity_get_display('node', 'article', 'default')
      ->setComponent($field_name, $display_options)
      ->save();

    // Create a derivative so at least one MIME type will be known.
    $large_style = ImageStyle::load('large');
    $image_uri = File::load($node->{$field_name}->target_id)->getFileUri();
    $large_style->createDerivative($image_uri, $large_style->buildUri($image_uri));

    // Output should contain all image styles and all breakpoints.
    $this->drupalGet('node/' . $nid);
    switch ($link_type) {
      case 'file':
        // Make sure the link to the file is present.
        $this->assertPattern('/<a(.*?)href="' . preg_quote(file_create_url($image_uri), '/') . '"(.*?)><picture/');
        break;

      case 'content':
        // Make sure the link to the node is present.
        $this->assertPattern('/<a(.*?)href="' . preg_quote($node->url(), '/') . '"(.*?)><picture/');
        break;
    }
  }
}

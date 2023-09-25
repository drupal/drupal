<?php

namespace Drupal\Tests\responsive_image\Functional;

use Drupal\image\Entity\ImageStyle;
use Drupal\image\ImageStyleInterface;
use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;
use Drupal\responsive_image\Plugin\Field\FieldFormatter\ResponsiveImageFormatter;
use Drupal\responsive_image\Entity\ResponsiveImageStyle;
use Drupal\responsive_image\ResponsiveImageStyleInterface;
use Drupal\Tests\image\Functional\ImageFieldTestBase;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\user\RoleInterface;

/**
 * Tests responsive image display formatter.
 *
 * @group responsive_image
 * @group #slow
 */
class ResponsiveImageFieldDisplayTest extends ImageFieldTestBase {

  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Responsive image style entity instance we test with.
   *
   * @var \Drupal\responsive_image\Entity\ResponsiveImageStyle
   */
  protected $responsiveImgStyle;

  /**
   * The file URL generator.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'field_ui',
    'responsive_image',
    'responsive_image_test_module',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->fileUrlGenerator = $this->container->get('file_url_generator');

    // Create user.
    $this->adminUser = $this->drupalCreateUser([
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
      'administer image styles',
    ]);
    $this->drupalLogin($this->adminUser);
    // Add responsive image style.
    $this->responsiveImgStyle = ResponsiveImageStyle::create([
      'id' => 'style_one',
      'label' => 'Style One',
      'breakpoint_group' => 'responsive_image_test_module',
      'fallback_image_style' => 'large',
    ]);
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
    user_role_change_permissions(RoleInterface::ANONYMOUS_ID, ['access content' => FALSE]);
    $this->doTestResponsiveImageFieldFormatters('private');
  }

  /**
   * Tests responsive image formatters when image style is empty.
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
        ->addImageStyleMapping('responsive_image_test_module.mobile', '1x', [
          'image_mapping_type' => 'image_style',
          'image_mapping' => '',
        ])
        ->addImageStyleMapping('responsive_image_test_module.narrow', '1x', [
          'image_mapping_type' => 'sizes',
          'image_mapping' => [
            'sizes' => '(min-width: 700px) 700px, 100vw',
            'sizes_image_styles' => [],
          ],
        ])
        ->addImageStyleMapping('responsive_image_test_module.wide', '1x', [
          'image_mapping_type' => 'image_style',
          'image_mapping' => '',
        ])
        ->save();
    }
    else {
      $this->responsiveImgStyle
        // Test the output of an empty image.
        ->addImageStyleMapping('responsive_image_test_module.mobile', '1x', [
          'image_mapping_type' => 'image_style',
          'image_mapping' => ResponsiveImageStyleInterface::EMPTY_IMAGE,
        ])
        // Test the output with a 1.5x multiplier.
        ->addImageStyleMapping('responsive_image_test_module.mobile', '1.5x', [
          'image_mapping_type' => 'image_style',
          'image_mapping' => 'thumbnail',
        ])
        // Test the output of the 'sizes' attribute.
        ->addImageStyleMapping('responsive_image_test_module.narrow', '1x', [
          'image_mapping_type' => 'sizes',
          'image_mapping' => [
            'sizes' => '(min-width: 700px) 700px, 100vw',
            'sizes_image_styles' => [
              'large',
              'medium',
            ],
          ],
        ])
        // Test the normal output of mapping to an image style.
        ->addImageStyleMapping('responsive_image_test_module.wide', '1x', [
          'image_mapping_type' => 'image_style',
          'image_mapping' => 'large',
        ])
        // Test the output of the original image.
        ->addImageStyleMapping('responsive_image_test_module.wide', '3x', [
          'image_mapping_type' => 'image_style',
          'image_mapping' => ResponsiveImageStyleInterface::ORIGINAL_IMAGE,
        ])
        ->save();
    }
  }

  /**
   * Tests responsive image formatters on node display.
   *
   * If the empty styles param is set, then the function only tests for the
   * fallback image style (large).
   *
   * @param string $scheme
   *   File scheme to use.
   * @param bool $empty_styles
   *   If true, use an empty string for image style names.
   *   Defaults to false.
   */
  protected function doTestResponsiveImageFieldFormatters($scheme, $empty_styles = FALSE) {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $field_name = $this->randomMachineName();
    $this->createImageField($field_name, 'article', ['uri_scheme' => $scheme]);
    // Create a new node with an image attached. Make sure we use a large image
    // so the scale effects of the image styles always have an effect.
    $test_image = current($this->getTestFiles('image', 39325));

    // Create alt text for the image.
    $alt = $this->randomMachineName();

    $nid = $this->uploadNodeImage($test_image, $field_name, 'article', $alt);
    $node_storage->resetCache([$nid]);
    $node = $node_storage->load($nid);

    // Test that the default formatter is being used.
    $image_uri = File::load($node->{$field_name}->target_id)->getFileUri();
    $image = [
      '#theme' => 'image',
      '#uri' => $image_uri,
      '#width' => 360,
      '#height' => 240,
      '#alt' => $alt,
      '#attributes' => ['loading' => 'lazy'],
    ];
    $default_output = str_replace("\n", '', $renderer->renderRoot($image));
    $this->assertSession()->responseContains($default_output);

    // Test field not being configured. This should not cause a fatal error.
    $display_options = [
      'type' => 'responsive_image_test',
      'settings' => ResponsiveImageFormatter::defaultSettings(),
    ];
    $display = $this->container->get('entity_type.manager')
      ->getStorage('entity_view_display')
      ->load('node.article.default');
    if (!$display) {
      $values = [
        'targetEntityType' => 'node',
        'bundle' => 'article',
        'mode' => 'default',
        'status' => TRUE,
      ];
      $display = $this->container->get('entity_type.manager')->getStorage('entity_view_display')->create($values);
    }
    $display->setComponent($field_name, $display_options)->save();

    $this->drupalGet('node/' . $nid);

    // Test theme function for responsive image, but using the test formatter.
    $display_options = [
      'type' => 'responsive_image_test',
      'settings' => [
        'image_link' => 'file',
        'responsive_image_style' => 'style_one',
      ],
    ];
    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');
    $display = $display_repository->getViewDisplay('node', 'article');
    $display->setComponent($field_name, $display_options)
      ->save();

    $this->drupalGet('node/' . $nid);

    // Use the responsive image formatter linked to file formatter.
    $display_options = [
      'type' => 'responsive_image',
      'settings' => [
        'image_link' => 'file',
        'responsive_image_style' => 'style_one',
      ],
    ];
    $display = $display_repository->getViewDisplay('node', 'article');
    $display->setComponent($field_name, $display_options)
      ->save();

    $this->drupalGet('node/' . $nid);
    // No image style cache tag should be found.
    $this->assertSession()->responseHeaderNotContains('X-Drupal-Cache-Tags', 'image_style:');

    $this->assertSession()->responseMatches('/<a(.*?)href="' . preg_quote($this->fileUrlGenerator->generateString($image_uri), '/') . '"(.*?)>\s*<picture/');
    // Verify that the image can be downloaded.
    $this->assertEquals(file_get_contents($test_image->uri), $this->drupalGet($this->fileUrlGenerator->generateAbsoluteString($image_uri)), 'File was downloaded successfully.');
    if ($scheme == 'private') {
      // Only verify HTTP headers when using private scheme and the headers are
      // sent by Drupal.
      $this->assertSession()->responseHeaderEquals('Content-Type', 'image/png');
      $this->assertSession()->responseHeaderContains('Cache-Control', 'private');

      // Log out and ensure the file cannot be accessed.
      $this->drupalLogout();
      $this->drupalGet($this->fileUrlGenerator->generateAbsoluteString($image_uri));
      $this->assertSession()->statusCodeEquals(403);

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
      $this->assertSession()->responseContains('/styles/medium/');
      // Assert the empty image is present.
      $this->assertSession()->responseContains('data:image/gif;base64,R0lGODlhAQABAIABAP///wAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==');
      $thumbnail_style = ImageStyle::load('thumbnail');
      // Assert the output of the 'srcset' attribute (small multipliers first).
      $this->assertSession()->responseContains('data:image/gif;base64,R0lGODlhAQABAIABAP///wAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw== 1x, ' . $this->fileUrlGenerator->transformRelative($thumbnail_style->buildUrl($image_uri)) . ' 1.5x');
      $this->assertSession()->responseContains('/styles/medium/');
      // Assert the output of the original image.
      $this->assertSession()->responseContains($this->fileUrlGenerator->generateString($image_uri) . ' 3x');
      // Assert the output of the breakpoints.
      $this->assertSession()->responseContains('media="(min-width: 0px)"');
      $this->assertSession()->responseContains('media="(min-width: 560px)"');
      // Assert the output of the 'sizes' attribute.
      $this->assertSession()->responseContains('sizes="(min-width: 700px) 700px, 100vw"');
      $this->assertSession()->responseMatches('/media="\(min-width: 560px\)".+?sizes="\(min-width: 700px\) 700px, 100vw"/');
      // Assert the output of the 'srcset' attribute (small images first).
      $medium_style = ImageStyle::load('medium');
      $this->assertSession()->responseContains($this->fileUrlGenerator->transformRelative($medium_style->buildUrl($image_uri)) . ' 220w, ' . $this->fileUrlGenerator->transformRelative($large_style->buildUrl($image_uri)) . ' 360w');
      $this->assertSession()->responseContains('media="(min-width: 851px)"');
      // Assert the output of the 'width' attribute.
      $this->assertSession()->responseContains('width="360"');
      // Assert the output of the 'height' attribute.
      $this->assertSession()->responseContains('height="240"');
      $this->assertSession()->responseContains('loading="lazy"');
    }
    $this->assertSession()->responseContains('/styles/large/');
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', 'config:responsive_image.styles.style_one');
    if (!$empty_styles) {
      $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', 'config:image.style.medium');
      $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', 'config:image.style.thumbnail');
      $this->assertSession()->responseContains('type="image/png"');
    }
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', 'config:image.style.large');

    // Test the fallback image style. Copy the source image:
    $fallback_image = $image;
    // Set the fallback image style uri:
    $fallback_image['#uri'] = $this->fileUrlGenerator->transformRelative($large_style->buildUrl($image_uri));
    // The image.html.twig template has a newline after the <img> tag but
    // responsive-image.html.twig doesn't have one after the fallback image, so
    // we remove it here.
    $default_output = trim($renderer->renderRoot($fallback_image));
    $this->assertSession()->responseContains($default_output);

    if ($scheme == 'private') {
      // Log out and ensure the file cannot be accessed.
      $this->drupalLogout();
      $this->drupalGet($large_style->buildUrl($image_uri));
      $this->assertSession()->statusCodeEquals(403);
      $this->assertSession()->responseHeaderNotMatches('X-Drupal-Cache-Tags', '/ image_style\:/');
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
      ->addImageStyleMapping('responsive_image_test_module.empty', '1x', [
        'image_mapping_type' => 'image_style',
        'image_mapping' => ResponsiveImageStyleInterface::EMPTY_IMAGE,
      ])
      // Test the output with a 1.5x multiplier.
      ->addImageStyleMapping('responsive_image_test_module.mobile', '1x', [
        'image_mapping_type' => 'image_style',
        'image_mapping' => 'thumbnail',
      ])
      ->save();
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $field_name = $this->randomMachineName();
    $this->createImageField($field_name, 'article', ['uri_scheme' => 'public']);
    // Create a new node with an image attached.
    $test_image = current($this->getTestFiles('image'));
    $nid = $this->uploadNodeImage($test_image, $field_name, 'article', $this->randomMachineName());
    $node_storage->resetCache([$nid]);

    // Use the responsive image formatter linked to file formatter.
    $display_options = [
      'type' => 'responsive_image',
      'settings' => [
        'image_link' => '',
        'responsive_image_style' => 'style_one',
      ],
    ];
    $display = \Drupal::service('entity_display.repository')
      ->getViewDisplay('node', 'article');
    $display->setComponent($field_name, $display_options)
      ->save();

    // View the node.
    $this->drupalGet('node/' . $nid);

    // Assert an empty media attribute is not output.
    $this->assertSession()->responseNotMatches('@srcset="data:image/gif;base64,R0lGODlhAQABAIABAP///wAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw== 1x".+?media=".+?/><source@');

    // Assert the media attribute is present if it has a value.
    $thumbnail_style = ImageStyle::load('thumbnail');
    $node = $node_storage->load($nid);
    $image_uri = File::load($node->{$field_name}->target_id)->getFileUri();
    $this->assertSession()->responseMatches('/srcset="' . preg_quote($this->fileUrlGenerator->transformRelative($thumbnail_style->buildUrl($image_uri)), '/') . ' 1x".+?media="\(min-width: 0px\)"/');
  }

  /**
   * Tests responsive image formatter on node display with one and two sources.
   */
  public function testResponsiveImageFieldFormattersMultipleSources() {
    // Setup known image style sizes so the test can assert on known sizes.
    $large_style = ImageStyle::load('large');
    assert($large_style instanceof ImageStyleInterface);
    $large_style->addImageEffect([
      'id' => 'image_resize',
      'data' => [
        'width' => '480',
        'height' => '480',
      ],
    ]);
    $large_style->save();
    $medium_style = ImageStyle::load('medium');
    assert($medium_style instanceof ImageStyleInterface);
    $medium_style->addImageEffect([
      'id' => 'image_resize',
      'data' => [
        'width' => '220',
        'height' => '220',
      ],
    ]);
    $medium_style->save();

    $this->responsiveImgStyle
      // Test the output of an empty media query.
      ->addImageStyleMapping('responsive_image_test_module.empty', '1x', [
        'image_mapping_type' => 'image_style',
        'image_mapping' => $medium_style->id(),
      ])
      ->addImageStyleMapping('responsive_image_test_module.empty', '1.5x', [
        'image_mapping_type' => 'image_style',
        'image_mapping' => $large_style->id(),
      ])
      ->addImageStyleMapping('responsive_image_test_module.empty', '2x', [
        'image_mapping_type' => 'image_style',
        'image_mapping' => $large_style->id(),
      ])
      ->save();
    $node_storage = $this->container->get('entity_type.manager')->getStorage('node');
    $field_name = $this->randomMachineName();
    $this->createImageField($field_name, 'article', ['uri_scheme' => 'public']);
    // Create a new node with an image attached.
    $test_image = current($this->getTestFiles('image'));
    $nid = $this->uploadNodeImage($test_image, $field_name, 'article', $this->randomMachineName());
    $node_storage->resetCache([$nid]);

    // Use the responsive image formatter linked to file formatter.
    $display_options = [
      'type' => 'responsive_image',
      'settings' => [
        'image_link' => '',
        'responsive_image_style' => 'style_one',
        'image_loading' => [
          // Test the image loading default option can be overridden.
          'attribute' => 'eager',
        ],
      ],
    ];
    $display = \Drupal::service('entity_display.repository')
      ->getViewDisplay('node', 'article');
    $display->setComponent($field_name, $display_options)
      ->save();

    // View the node.
    $this->drupalGet('node/' . $nid);

    // Assert the img tag has medium and large images and fallback dimensions
    // from the large image style are used.
    $node = $node_storage->load($nid);
    $image_uri = File::load($node->{$field_name}->target_id)->getFileUri();
    $medium_transform_url = $this->fileUrlGenerator->transformRelative($medium_style->buildUrl($image_uri));
    $large_transform_url = $this->fileUrlGenerator->transformRelative($large_style->buildUrl($image_uri));
    $this->assertSession()->responseMatches('/<img loading="eager" srcset="' . \preg_quote($medium_transform_url, '/') . ' 1x, ' . \preg_quote($large_transform_url, '/') . ' 1.5x, ' . \preg_quote($large_transform_url, '/') . ' 2x" width="220" height="220" src="' . \preg_quote($large_transform_url, '/') . '" alt="\w+" \/>/');

    $this->responsiveImgStyle
      // Test the output of an empty media query.
      ->addImageStyleMapping('responsive_image_test_module.wide', '1x', [
        'image_mapping_type' => 'image_style',
        'image_mapping' => $large_style->id(),
      ])
      ->save();

    // Assert the picture tag has source tags that include dimensions.
    $this->drupalGet('node/' . $nid);
    $this->assertSession()->responseMatches('/<picture>\s+<source srcset="' . \preg_quote($large_transform_url, '/') . ' 1x" media="\(min-width: 851px\)" type="image\/png" width="480" height="480"\/>\s+<source srcset="' . \preg_quote($medium_transform_url, '/') . ' 1x, ' . \preg_quote($large_transform_url, '/') . ' 1.5x, ' . \preg_quote($large_transform_url, '/') . ' 2x" type="image\/png" width="220" height="220"\/>\s+<img loading="eager" src="' . \preg_quote($large_transform_url, '/') . '" width="480" height="480" alt="\w+" \/>\s+<\/picture>/');
  }

  /**
   * Tests responsive image formatters linked to the file or node.
   *
   * @param string $link_type
   *   The link type to test. Either 'file' or 'content'.
   */
  private function assertResponsiveImageFieldFormattersLink(string $link_type): void {
    $field_name = $this->randomMachineName();
    $field_settings = ['alt_field_required' => 0];
    $this->createImageField($field_name, 'article', ['uri_scheme' => 'public'], $field_settings);
    // Create a new node with an image attached.
    $test_image = current($this->getTestFiles('image'));

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');

    // Test the image linked to file formatter.
    $display_options = [
      'type' => 'responsive_image',
      'settings' => [
        'image_link' => $link_type,
        'responsive_image_style' => 'style_one',
      ],
    ];
    $display_repository->getViewDisplay('node', 'article')
      ->setComponent($field_name, $display_options)
      ->save();
    // Ensure that preview works.
    $this->previewNodeImage($test_image, $field_name, 'article');

    // Look for a picture tag in the preview output
    $this->assertSession()->responseMatches('/picture/');

    $nid = $this->uploadNodeImage($test_image, $field_name, 'article');
    $this->container->get('entity_type.manager')->getStorage('node')->resetCache([$nid]);
    $node = Node::load($nid);

    // Use the responsive image formatter linked to file formatter.
    $display_options = [
      'type' => 'responsive_image',
      'settings' => [
        'image_link' => $link_type,
        'responsive_image_style' => 'style_one',
      ],
    ];
    $display_repository->getViewDisplay('node', 'article')
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
        $this->assertSession()->responseMatches('/<a(.*?)href="' . preg_quote($this->fileUrlGenerator->generateString($image_uri), '/') . '"(.*?)>\s*<picture/');
        break;

      case 'content':
        // Make sure the link to the node is present.
        $this->assertSession()->responseMatches('/<a(.*?)href="' . preg_quote($node->toUrl()->toString(), '/') . '"(.*?)>\s*<picture/');
        break;
    }
  }

}

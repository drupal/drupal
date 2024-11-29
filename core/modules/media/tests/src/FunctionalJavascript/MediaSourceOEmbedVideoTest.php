<?php

declare(strict_types=1);

namespace Drupal\Tests\media\FunctionalJavascript;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Database\Database;
use Drupal\dblog\Controller\DbLogController;
use Drupal\media\Entity\Media;
use Drupal\media\Entity\MediaType;
use Drupal\media_test_oembed\Controller\ResourceController;
use Drupal\Tests\media\Traits\OEmbedTestTrait;
use Drupal\user\Entity\Role;
use Symfony\Component\DependencyInjection\ContainerInterface;

// cspell:ignore dailymotion Schipulcon

/**
 * Tests the oembed:video media source.
 *
 * @group media
 */
class MediaSourceOEmbedVideoTest extends MediaSourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['media_test_oembed', 'dblog'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  use OEmbedTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->lockHttpClientToFixtures();
  }

  /**
   * {@inheritdoc}
   */
  protected function initConfig(ContainerInterface $container): void {
    parent::initConfig($container);

    // Enable twig debugging to make testing template usage easy.
    $parameters = $container->getParameter('twig.config');
    $parameters['debug'] = TRUE;
    $this->setContainerParameter('twig.config', $parameters);
  }

  /**
   * Tests the oembed media source.
   */
  public function testMediaOEmbedVideoSource(): void {
    $media_type_id = 'test_media_oembed_type';
    $provided_fields = [
      'type',
      'title',
      'default_name',
      'author_name',
      'author_url',
      'provider_name',
      'provider_url',
      'cache_age',
      'thumbnail_uri',
      'thumbnail_width',
      'thumbnail_height',
      'url',
      'width',
      'height',
      'html',
    ];

    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    $this->doTestCreateMediaType($media_type_id, 'oembed:video', $provided_fields);

    // Create custom fields for the media type to store metadata attributes.
    $fields = [
      'field_string_width' => 'string',
      'field_string_height' => 'string',
      'field_string_author_name' => 'string',
    ];
    $this->createMediaTypeFields($fields, $media_type_id);

    // Hide the name field widget to test default name generation.
    $this->hideMediaTypeFieldWidget('name', $media_type_id);

    $this->drupalGet("admin/structure/media/manage/$media_type_id");
    // Only accept Vimeo videos.
    $page->checkField("source_configuration[providers][Vimeo]");
    $assert_session->selectExists('field_map[width]')->setValue('field_string_width');
    $assert_session->selectExists('field_map[height]')->setValue('field_string_height');
    $assert_session->selectExists('field_map[author_name]')->setValue('field_string_author_name');
    $assert_session->buttonExists('Save')->press();

    // Configure the iframe to be narrower than the actual video, so we can
    // verify that the video scales correctly.
    $display = \Drupal::service('entity_display.repository')->getViewDisplay('media', $media_type_id);
    $this->assertFalse($display->isNew());
    $component = $display->getComponent('field_media_oembed_video');
    $this->assertIsArray($component);
    $component['settings']['max_width'] = 240;
    $display->setComponent('field_media_oembed_video', $component);
    $this->assertSame(SAVED_UPDATED, $display->save());

    $this->hijackProviderEndpoints();
    $video_url = 'https://vimeo.com/7073899';
    ResourceController::setResourceUrl($video_url, $this->getFixturesDirectory() . '/video_vimeo.json');

    // Create a media item.
    $this->drupalGet("media/add/$media_type_id");
    $assert_session->fieldExists('Remote video URL')->setValue($video_url);
    $assert_session->buttonExists('Save')->press();

    $assert_session->addressEquals('admin/content/media');

    // Get the media entity view URL from the creation message.
    $this->drupalGet($this->assertLinkToCreatedMedia());

    /** @var \Drupal\media\MediaInterface $media */
    $media = Media::load(1);

    // The thumbnail should have been downloaded.
    $thumbnail = $media->getSource()->getMetadata($media, 'thumbnail_uri');
    $this->assertFileExists($thumbnail);

    // Ensure the iframe exists and has the expected CSS class, and that its src
    // attribute contains a coherent URL with the query parameters we expect.
    $iframe = $assert_session->elementExists('css', 'iframe.media-oembed-content');
    $iframe_url = parse_url($iframe->getAttribute('src'));
    $this->assertStringEndsWith('/media/oembed', $iframe_url['path']);
    $this->assertNotEmpty($iframe_url['query']);
    $query = [];
    parse_str($iframe_url['query'], $query);
    $this->assertSame($video_url, $query['url']);
    $this->assertNotEmpty($query['hash']);
    // Ensure that the outer iframe's width respects the formatter settings.
    $this->assertSame('480', $iframe->getAttribute('width'));
    // Check the inner iframe to make sure that CSS has been applied to scale it
    // correctly, regardless of whatever its width attribute may be (the fixture
    // hard-codes it to 480).
    $inner_frame = 'frames[0].document.querySelector("iframe")';
    $this->assertSame('480', $session->evaluateScript("$inner_frame.getAttribute('width')"));
    $this->assertLessThanOrEqual(240, $session->evaluateScript("$inner_frame.clientWidth"));

    // The oEmbed content iFrame should be visible.
    $assert_session->elementExists('css', 'iframe.media-oembed-content');
    // The thumbnail should not be displayed.
    $assert_session->elementNotExists('css', 'img');

    // Load the media and check that all fields are properly populated.
    $media = Media::load(1);
    $this->assertSame('Drupal Rap Video - Schipulcon09', $media->getName());
    $this->assertSame('480', $media->field_string_width->value);
    $this->assertSame('360', $media->field_string_height->value);

    // Try to create a media asset from a disallowed provider.
    $this->drupalGet("media/add/$media_type_id");
    $assert_session->fieldExists('Remote video URL')->setValue('https://www.dailymotion.com/video/x2vzluh');
    $page->pressButton('Save');

    $assert_session->pageTextContains('The Dailymotion provider is not allowed.');

    // Register a Dailymotion video as a second oEmbed resource. Note that its
    // thumbnail URL does not have a file extension.
    $media_type = MediaType::load($media_type_id);
    $source_configuration = $media_type->getSource()->getConfiguration();
    $source_configuration['providers'][] = 'Dailymotion';
    $media_type->getSource()->setConfiguration($source_configuration);
    $media_type->save();
    $video_url = 'https://www.dailymotion.com/video/x2vzluh';
    ResourceController::setResourceUrl($video_url, $this->getFixturesDirectory() . '/video_dailymotion.xml');

    // Create a new media item using a Dailymotion video.
    $this->drupalGet("media/add/$media_type_id");
    $assert_session->fieldExists('Remote video URL')->setValue($video_url);
    $assert_session->buttonExists('Save')->press();

    /** @var \Drupal\media\MediaInterface $media */
    $media = Media::load(2);
    $thumbnail = $media->getSource()->getMetadata($media, 'thumbnail_uri');
    $this->assertFileExists($thumbnail);
    // Although the resource's thumbnail URL doesn't have a file extension, we
    // should have deduced the correct one.
    $this->assertStringEndsWith('.png', $thumbnail);

    // Test ResourceException logging.
    $video_url = 'https://vimeo.com/1111';
    ResourceController::setResourceUrl($video_url, $this->getFixturesDirectory() . '/video_vimeo.json');
    $this->drupalGet("media/add/$media_type_id");
    $assert_session->fieldExists('Remote video URL')->setValue($video_url);
    $assert_session->buttonExists('Save')->press();
    $assert_session->addressEquals('admin/content/media');
    ResourceController::setResource404($video_url);
    $this->drupalGet($this->assertLinkToCreatedMedia());
    $row = Database::getConnection()->select('watchdog')
      ->fields('watchdog', ['message', 'variables'])
      ->orderBy('wid', 'DESC')
      ->range(0, 1)
      ->execute()
      ->fetchObject();
    $message = (string) DbLogController::create($this->container)->formatMessage($row);
    $this->assertStringContainsString('resulted in a `404 Not Found` response', $message);

    // Test anonymous access to media via iframe.
    $this->drupalLogout();

    // Without a hash should be denied.
    $no_hash_query = array_diff_key($query, ['hash' => '']);
    $this->drupalGet('media/oembed', ['query' => $no_hash_query]);
    $assert_session->pageTextNotContains('Vimeo works!');
    $assert_session->pageTextContains('This resource is not available');

    // A correct query should be allowed because the anonymous role has the
    // 'view media' permission.
    $this->drupalGet('media/oembed', ['query' => $query]);
    $assert_session->pageTextContains('Vimeo works!');

    // Remove the 'view media' permission to test that this restricts access.
    $role = Role::load(AccountInterface::ANONYMOUS_ROLE);
    $role->revokePermission('view media');
    $role->save();
    $this->drupalGet('media/oembed', ['query' => $query]);
    $assert_session->pageTextNotContains('Vimeo works!');
    $assert_session->pageTextContains('Access denied');
  }

  /**
   * Tests that a security warning appears if iFrame domain is not set.
   */
  public function testOEmbedSecurityWarning(): void {
    $media_type_id = 'test_media_oembed_type';
    $source_id = 'oembed:video';

    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    $this->drupalGet('admin/structure/media/add');
    $page->fillField('label', $media_type_id);
    $this->getSession()
      ->wait(5000, "jQuery('.machine-name-value').text() === '{$media_type_id}'");

    // Make sure the source is available.
    $assert_session->fieldExists('Media source');
    $assert_session->optionExists('Media source', $source_id);
    $page->selectFieldOption('Media source', $source_id);
    $result = $assert_session->waitForElementVisible('css', 'fieldset[data-drupal-selector="edit-source-configuration"]');
    $this->assertNotEmpty($result);

    $assert_session->pageTextContains('It is potentially insecure to display oEmbed content in a frame');

    $this->config('media.settings')->set('iframe_domain', 'http://example.com')->save();

    $this->drupalGet('admin/structure/media/add');
    $page->fillField('label', $media_type_id);
    $this->getSession()
      ->wait(5000, "jQuery('.machine-name-value').text() === '{$media_type_id}'");

    // Make sure the source is available.
    $assert_session->fieldExists('Media source');
    $assert_session->optionExists('Media source', $source_id);
    $page->selectFieldOption('Media source', $source_id);
    $result = $assert_session->waitForElementVisible('css', 'fieldset[data-drupal-selector="edit-source-configuration"]');
    $this->assertNotEmpty($result);

    $assert_session->pageTextNotContains('It is potentially insecure to display oEmbed content in a frame');
  }

}

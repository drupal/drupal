<?php

namespace Drupal\media\Plugin\media\Source;

use Drupal\Component\Render\PlainTextOutput;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\File\Exception\FileException;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\Core\Utility\Token;
use Drupal\media\Attribute\OEmbedMediaSource;
use Drupal\media\IFrameUrlHelper;
use Drupal\media\MediaInterface;
use Drupal\media\MediaSourceBase;
use Drupal\media\MediaTypeInterface;
use Drupal\media\OEmbed\Resource;
use Drupal\media\OEmbed\ResourceException;
use Drupal\media\OEmbed\ResourceFetcherInterface;
use Drupal\media\OEmbed\UrlResolverInterface;
use GuzzleHttp\ClientInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Mime\MimeTypes;

/**
 * Provides a media source plugin for oEmbed resources.
 *
 * For security reasons, the oEmbed source (and, therefore, anything that
 * extends it) obeys a hard-coded list of allowed third-party oEmbed providers
 * set in its plugin definition's providers array. This array is a set of
 * provider names, exactly as they appear in the canonical oEmbed provider
 * database at https://oembed.com/providers.json.
 *
 * You can implement support for additional providers by defining a new plugin
 * that uses this class. This can be done in hook_media_source_info_alter().
 * For example:
 * @code
 * <?php
 *
 * function example_media_source_info_alter(array &$sources) {
 *   $sources['artwork'] = [
 *     'id' => 'artwork',
 *     'label' => $this->t('Artwork'),
 *     'description' => $this->t('Use artwork from Flickr and DeviantArt.'),
 *     'allowed_field_types' => ['string'],
 *     'default_thumbnail_filename' => 'no-thumbnail.png',
 *     'providers' => ['Deviantart.com', 'Flickr'],
 *     'class' => 'Drupal\media\Plugin\media\Source\OEmbed',
 *   ];
 * }
 * @endcode
 * The "Deviantart.com" and "Flickr" provider names are specified in
 * https://oembed.com/providers.json. The
 * \Drupal\media\Plugin\media\Source\OEmbed class already knows how to handle
 * standard interactions with third-party oEmbed APIs, so there is no need to
 * define a new class which extends it. With the code above, you will able to
 * create media types which use the "Artwork" source plugin, and use those media
 * types to link to assets on Deviantart and Flickr.
 */
#[OEmbedMediaSource(
  id: "oembed",
  label: new TranslatableMarkup("oEmbed source"),
  description: new TranslatableMarkup("Use oEmbed URL for reusable media."),
  allowed_field_types: ["string"],
  default_thumbnail_filename: "no-thumbnail.png",
  deriver: OEmbedDeriver::class,
)]
class OEmbed extends MediaSourceBase implements OEmbedInterface {

  /**
   * The logger channel for media.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The oEmbed resource fetcher service.
   *
   * @var \Drupal\media\OEmbed\ResourceFetcherInterface
   */
  protected $resourceFetcher;

  /**
   * The OEmbed manager service.
   *
   * @var \Drupal\media\OEmbed\UrlResolverInterface
   */
  protected $urlResolver;

  /**
   * The iFrame URL helper service.
   *
   * @var \Drupal\media\IFrameUrlHelper
   */
  protected $iFrameUrlHelper;

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The token replacement service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Constructs a new OEmbed instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The field type plugin manager service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger channel for media.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\media\OEmbed\ResourceFetcherInterface $resource_fetcher
   *   The oEmbed resource fetcher service.
   * @param \Drupal\media\OEmbed\UrlResolverInterface $url_resolver
   *   The oEmbed URL resolver service.
   * @param \Drupal\media\IFrameUrlHelper $iframe_url_helper
   *   The iFrame URL helper service.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system.
   * @param \Drupal\Core\Utility\Token $token
   *   The token replacement service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, ConfigFactoryInterface $config_factory, FieldTypePluginManagerInterface $field_type_manager, LoggerInterface $logger, MessengerInterface $messenger, ClientInterface $http_client, ResourceFetcherInterface $resource_fetcher, UrlResolverInterface $url_resolver, IFrameUrlHelper $iframe_url_helper, FileSystemInterface $file_system, Token $token) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $entity_field_manager, $field_type_manager, $config_factory);
    $this->logger = $logger;
    $this->messenger = $messenger;
    $this->httpClient = $http_client;
    $this->resourceFetcher = $resource_fetcher;
    $this->urlResolver = $url_resolver;
    $this->iFrameUrlHelper = $iframe_url_helper;
    $this->fileSystem = $file_system;
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('config.factory'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('logger.factory')->get('media'),
      $container->get('messenger'),
      $container->get('http_client'),
      $container->get('media.oembed.resource_fetcher'),
      $container->get('media.oembed.url_resolver'),
      $container->get('media.oembed.iframe_url_helper'),
      $container->get('file_system'),
      $container->get('token')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadataAttributes() {
    return [
      'type' => $this->t('Resource type'),
      'title' => $this->t('Resource title'),
      'author_name' => $this->t('Author/owner name'),
      'author_url' => $this->t('Author/owner URL'),
      'provider_name' => $this->t('Provider name'),
      'provider_url' => $this->t('Provider URL'),
      'cache_age' => $this->t('Suggested cache lifetime'),
      'default_name' => $this->t('Media item default name'),
      'thumbnail_uri' => $this->t('Thumbnail local URI'),
      'thumbnail_width' => $this->t('Thumbnail width'),
      'thumbnail_height' => $this->t('Thumbnail height'),
      'url' => $this->t('Resource source URL'),
      'width' => $this->t('Resource width'),
      'height' => $this->t('Resource height'),
      'html' => $this->t('Resource HTML representation'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata(MediaInterface $media, $name) {
    $media_url = $this->getSourceFieldValue($media);
    // The URL may be NULL if the source field is empty, in which case just
    // return NULL.
    if (empty($media_url)) {
      return NULL;
    }

    try {
      $resource_url = $this->urlResolver->getResourceUrl($media_url);
      $resource = $this->resourceFetcher->fetchResource($resource_url);
    }
    catch (ResourceException $e) {
      $this->messenger->addError($e->getMessage());
      return NULL;
    }

    switch ($name) {
      case 'default_name':
        if ($title = $this->getMetadata($media, 'title')) {
          return $title;
        }
        elseif ($url = $this->getMetadata($media, 'url')) {
          return $url;
        }
        return parent::getMetadata($media, 'default_name');

      case 'thumbnail_uri':
        return $this->getLocalThumbnailUri($resource, $media) ?: parent::getMetadata($media, 'thumbnail_uri');

      case 'type':
        return $resource->getType();

      case 'title':
        return $resource->getTitle();

      case 'author_name':
        return $resource->getAuthorName();

      case 'author_url':
        return $resource->getAuthorUrl();

      case 'provider_name':
        $provider = $resource->getProvider();
        return $provider ? $provider->getName() : '';

      case 'provider_url':
        $provider = $resource->getProvider();
        return $provider ? $provider->getUrl() : NULL;

      case 'cache_age':
        return $resource->getCacheMaxAge();

      case 'thumbnail_width':
        return $resource->getThumbnailWidth();

      case 'thumbnail_height':
        return $resource->getThumbnailHeight();

      case 'url':
        $url = $resource->getUrl();
        return $url ? $url->toString() : NULL;

      case 'width':
        return $resource->getWidth();

      case 'height':
        return $resource->getHeight();

      case 'html':
        return $resource->getHtml();

      default:
        break;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $domain = $this->configFactory->get('media.settings')->get('iframe_domain');
    if (!$this->iFrameUrlHelper->isSecure($domain)) {
      array_unshift($form, [
        '#markup' => '<p>' . $this->t('It is potentially insecure to display oEmbed content in a frame that is served from the same domain as your main Drupal site, as this may allow execution of third-party code. <a href=":url">You can specify a different domain for serving oEmbed content in the Media settings</a>.', [
          ':url' => Url::fromRoute('media.settings')->setAbsolute()->toString(),
        ]) . '</p>',
      ]);
    }

    $form['thumbnails_directory'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Thumbnails location'),
      '#default_value' => $this->configuration['thumbnails_directory'],
      '#description' => $this->t('Thumbnails will be fetched from the provider for local usage. This is the URI of the directory where they will be placed.'),
      '#required' => TRUE,
    ];

    $configuration = $this->getConfiguration();
    $plugin_definition = $this->getPluginDefinition();

    $form['providers'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Allowed providers'),
      '#default_value' => $configuration['providers'],
      '#options' => array_combine($plugin_definition['providers'], $plugin_definition['providers']),
      '#description' => $this->t('Optionally select the allowed oEmbed providers for this media type. If left blank, all providers will be allowed.'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $configuration = $this->getConfiguration();
    $configuration['providers'] = array_filter(array_values($configuration['providers']));
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $thumbnails_directory = $form_state->getValue('thumbnails_directory');

    /** @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager */
    $stream_wrapper_manager = \Drupal::service('stream_wrapper_manager');

    if (!$stream_wrapper_manager->isValidUri($thumbnails_directory)) {
      $form_state->setErrorByName('thumbnails_directory', $this->t('@path is not a valid path.', [
        '@path' => $thumbnails_directory,
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'thumbnails_directory' => 'public://oembed_thumbnails/[date:custom:Y-m]',
      'providers' => [],
    ];
  }

  /**
   * Returns the local URI for a resource thumbnail.
   *
   * If the thumbnail is not already locally stored, this method will attempt
   * to download it.
   *
   * @param \Drupal\media\OEmbed\Resource $resource
   *   The oEmbed resource.
   * @param \Drupal\media\MediaInterface|null $media
   *   The media entity that contains the resource.
   *
   * @return string|null
   *   The local thumbnail URI, or NULL if it could not be downloaded, or if the
   *   resource has no thumbnail at all.
   *
   * @todo Determine whether or not oEmbed media thumbnails should be stored
   * locally at all, and if so, whether that functionality should be
   * toggle-able. See https://www.drupal.org/project/drupal/issues/2962751 for
   * more information.
   */
  protected function getLocalThumbnailUri(Resource $resource, ?MediaInterface $media = NULL) {
    if (is_null($media)) {
      @trigger_error('Calling ' . __METHOD__ . '() without the $media argument is deprecated in drupal:10.3.0 and it will be required in drupal:11.0.0. See https://www.drupal.org/node/3432920', E_USER_DEPRECATED);
      $token_data = [];
    }
    else {
      $token_data = ['date' => $media->getCreatedTime()];
    }

    // If there is no remote thumbnail, there's nothing for us to fetch here.
    $remote_thumbnail_url = $resource->getThumbnailUrl();
    if (!$remote_thumbnail_url) {
      return NULL;
    }

    // Use the configured directory to store thumbnails. The directory can
    // contain basic (i.e., global) tokens. If any of the replaced tokens
    // contain HTML, the tags will be removed and XML entities will be decoded.
    $configuration = $this->getConfiguration();
    $directory = $configuration['thumbnails_directory'];
    // The thumbnail directory might contain a date token, so we pass in the
    // creation date of the media entity so that the token won't rely on the
    // current request time, making the current request have a max-age of 0.
    // @see system_tokens() for $type == 'date'.
    $directory = $this->token->replace($directory, $token_data);
    $directory = PlainTextOutput::renderFromHtml($directory);

    // The local thumbnail doesn't exist yet, so try to download it. First,
    // ensure that the destination directory is writable, and if it's not,
    // log an error and bail out.
    if (!$this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS)) {
      $this->logger->warning('Could not prepare thumbnail destination directory @dir for oEmbed media.', [
        '@dir' => $directory,
      ]);
      return NULL;
    }

    // The local filename of the thumbnail is always a hash of its remote URL.
    // If a file with that name already exists in the thumbnails directory,
    // regardless of its extension, return its URI.
    $remote_thumbnail_url = $remote_thumbnail_url->toString();
    $hash = Crypt::hashBase64($remote_thumbnail_url);
    $files = $this->fileSystem->scanDirectory($directory, "/^$hash\..*/");
    if (count($files) > 0) {
      return reset($files)->uri;
    }

    // The local thumbnail doesn't exist yet, so we need to download it.
    try {
      $response = $this->httpClient->request('GET', $remote_thumbnail_url);
      if ($response->getStatusCode() === 200) {
        $local_thumbnail_uri = $directory . DIRECTORY_SEPARATOR . $hash . '.' . $this->getThumbnailFileExtensionFromUrl($remote_thumbnail_url, $response);
        $this->fileSystem->saveData((string) $response->getBody(), $local_thumbnail_uri, FileExists::Replace);
        return $local_thumbnail_uri;
      }
    }
    catch (ClientExceptionInterface $e) {
      $this->logger->warning('Failed to download remote thumbnail file due to "%error".', [
        '%error' => $e->getMessage(),
      ]);
    }
    catch (FileException $e) {
      $this->logger->warning('Could not download remote thumbnail from {url}.', [
        'url' => $remote_thumbnail_url,
      ]);
    }
    return NULL;
  }

  /**
   * Tries to determine the file extension of a thumbnail.
   *
   * @param string $thumbnail_url
   *   The remote URL of the thumbnail.
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The response for the downloaded thumbnail.
   *
   * @return string|null
   *   The file extension, or NULL if it could not be determined.
   */
  protected function getThumbnailFileExtensionFromUrl(string $thumbnail_url, ResponseInterface $response): ?string {
    // First, try to glean the extension from the URL path.
    $path = parse_url($thumbnail_url, PHP_URL_PATH);
    if ($path) {
      $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
      if ($extension) {
        return $extension;
      }
    }

    // If the URL didn't give us any clues about the file extension, see if the
    // response headers will give us a MIME type.
    $content_type = $response->getHeader('Content-Type');
    // If there was no Content-Type header, there's nothing else we can do.
    if (empty($content_type)) {
      return NULL;
    }
    $extensions = MimeTypes::getDefault()->getExtensions(reset($content_type));
    if ($extensions) {
      return reset($extensions);
    }
    // If no file extension could be determined from the Content-Type header,
    // we're stumped.
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceFieldConstraints() {
    return [
      'oembed_resource' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareViewDisplay(MediaTypeInterface $type, EntityViewDisplayInterface $display) {
    $display->setComponent($this->getSourceFieldDefinition($type)->getName(), [
      'type' => 'oembed',
      'label' => 'visually_hidden',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareFormDisplay(MediaTypeInterface $type, EntityFormDisplayInterface $display) {
    parent::prepareFormDisplay($type, $display);
    $source_field = $this->getSourceFieldDefinition($type)->getName();

    $display->setComponent($source_field, [
      'type' => 'oembed_textfield',
      'weight' => $display->getComponent($source_field)['weight'],
    ]);
    $display->removeComponent('name');
  }

  /**
   * {@inheritdoc}
   */
  public function getProviders() {
    $configuration = $this->getConfiguration();
    return $configuration['providers'] ?: $this->getPluginDefinition()['providers'];
  }

  /**
   * {@inheritdoc}
   */
  public function createSourceField(MediaTypeInterface $type) {
    $plugin_definition = $this->getPluginDefinition();

    $label = (string) $this->t('@type URL', [
      '@type' => $plugin_definition['label'],
    ]);
    return parent::createSourceField($type)->set('label', $label);
  }

}

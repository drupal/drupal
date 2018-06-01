<?php

namespace Drupal\media\Plugin\media\Source;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\Display\EntityFormDisplayInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Drupal\media\OEmbed\ResourceException;
use Drupal\media\MediaSourceBase;
use Drupal\media\MediaInterface;
use Drupal\media\MediaTypeInterface;
use Drupal\media\OEmbed\ResourceFetcherInterface;
use Drupal\media\OEmbed\UrlResolverInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a media source plugin for oEmbed resources.
 *
 * For security reasons, the oEmbed source (and, therefore, anything that
 * extends it) obeys a hard-coded list of allowed third-party oEmbed providers
 * set in its plugin definition's supported_providers array. This array is a set
 * of supported provider names, exactly as they appear in the canonical oEmbed
 * provider database at https://oembed.com/providers.json.
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
 *     'label' => t('Artwork'),
 *     'description' => t('Use artwork from Flickr and DeviantArt.'),
 *     'allowed_field_types' => ['string'],
 *     'default_thumbnail_filename' => 'no-thumbnail.png',
 *     'supported_providers' => ['Deviantart.com', 'Flickr'],
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
 *
 * @MediaSource(
 *   id = "oembed",
 *   label = @Translation("oEmbed source"),
 *   description = @Translation("Use oEmbed URL for reusable media."),
 *   allowed_field_types = {"string"},
 *   default_thumbnail_filename = "no-thumbnail.png",
 *   deriver = "Drupal\media\Plugin\media\Source\OEmbedDeriver",
 *   supported_providers = {},
 * )
 */
class OEmbed extends MediaSourceBase implements OEmbedInterface {

  /**
   * The logger channel for media.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
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
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger channel for media.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\media\OEmbed\ResourceFetcherInterface $resource_fetcher
   *   The oEmbed resource fetcher service.
   * @param \Drupal\media\OEmbed\UrlResolverInterface $url_resolver
   *   The oEmbed URL resolver service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, ConfigFactoryInterface $config_factory, FieldTypePluginManagerInterface $field_type_manager, LoggerChannelInterface $logger, MessengerInterface $messenger, ClientInterface $http_client, ResourceFetcherInterface $resource_fetcher, UrlResolverInterface $url_resolver) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $entity_field_manager, $field_type_manager, $config_factory);
    $this->logger = $logger;
    $this->messenger = $messenger;
    $this->httpClient = $http_client;
    $this->resourceFetcher = $resource_fetcher;
    $this->urlResolver = $url_resolver;
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
      $container->get('media.oembed.url_resolver')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadataAttributes() {
    return [
      'type' => $this->t('Resource type'),
      'title' => $this->t('Resource title'),
      'author_name' => $this->t('The name of the author/owner'),
      'author_url' => $this->t('The URL of the author/owner'),
      'provider_name' => $this->t("The name of the provider"),
      'provider_url' => $this->t('The URL of the provider'),
      'cache_age' => $this->t('Suggested cache lifetime'),
      'thumbnail_url' => $this->t('The remote URL of the thumbnail'),
      'thumbnail_local_uri' => $this->t('The local URI of the thumbnail'),
      'thumbnail_local' => $this->t('The local URL of the thumbnail'),
      'thumbnail_width' => $this->t('Thumbnail width'),
      'thumbnail_height' => $this->t('Thumbnail height'),
      'url' => $this->t('The source URL of the resource'),
      'width' => $this->t('The width of the resource'),
      'height' => $this->t('The height of the resource'),
      'html' => $this->t('The HTML representation of the resource'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata(MediaInterface $media, $name) {
    $media_url = $this->getSourceFieldValue($media);

    try {
      $resource_url = $this->urlResolver->getResourceUrl($media_url);
      $resource = $this->resourceFetcher->fetchResource($resource_url);
    }
    catch (ResourceException $e) {
      $this->messenger->addError($e->getMessage());
      return NULL;
    }

    switch ($name) {
      case 'thumbnail_local':
        $local_uri = $this->getMetadata($media, 'thumbnail_local_uri');

        if ($local_uri) {
          if (file_exists($local_uri)) {
            return $local_uri;
          }
          else {
            try {
              $response = $this->httpClient->get($this->getMetadata($media, 'thumbnail_url'));
              if ($response->getStatusCode() === 200) {
                return file_unmanaged_save_data((string) $response->getBody(), $local_uri, FILE_EXISTS_REPLACE) ?: NULL;
              }
            }
            catch (RequestException $e) {
              $this->logger->warning($e->getMessage());
              // Return NULL so the default image will be used.
            }
          }
        }
        return NULL;

      case 'thumbnail_local_uri':
        return $this->getLocalImageUri($media);

      case 'default_name':
        if ($title = $this->getMetadata($media, 'title')) {
          return $title;
        }
        elseif ($url = $this->getMetadata($media, 'url')) {
          return $url;
        }
        return parent::getMetadata($media, 'default_name');

      case 'thumbnail_uri':
        return $this->getMetadata($media, 'thumbnail_local') ?: parent::getMetadata($media, 'thumbnail_uri');

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

      case 'thumbnail_url':
        $thumbnail_url = $resource->getThumbnailUrl();
        return $thumbnail_url ? $thumbnail_url->toString() : NULL;

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
    if (!$this->urlResolver->isSecure($domain)) {
      array_unshift($form, [
        '#markup' => '<p>' . $this->t('It is potentially insecure to display oEmbed content in a frame that is served from the same domain as your main Drupal site, as this may allow execution of third-party code. <a href=":url" target="_blank">You can specify a different domain for serving oEmbed content here</a> (opens in a new window).', [
          ':url' => Url::fromRoute('media.settings')->setAbsolute()->toString(),
        ]) . '</p>',
      ]);
    }

    $form['thumbnails_uri'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Thumbnails location'),
      '#default_value' => $this->configuration['thumbnails_uri'],
      '#description' => $this->t('Thumbnails will be fetched from the provider for local usage. This is the location where they will be placed.'),
      '#required' => TRUE,
    ];

    $configuration = $this->getConfiguration();
    $plugin_definition = $this->getPluginDefinition();

    $form['allowed_providers'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Allowed providers'),
      '#default_value' => $configuration['allowed_providers'],
      '#options' => array_combine($plugin_definition['supported_providers'], $plugin_definition['supported_providers']),
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
    $configuration['allowed_providers'] = array_filter(array_values($configuration['allowed_providers']));
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $thumbnails_uri = $form_state->getValue('thumbnails_uri');
    if (!file_valid_uri($thumbnails_uri)) {
      $form_state->setErrorByName('thumbnails_uri', $this->t('@path is not a valid path.', ['@path' => $thumbnails_uri]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'thumbnails_uri' => 'public://oembed_thumbnails',
      'allowed_providers' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * Computes the destination URI for a thumbnail.
   *
   * @param \Drupal\media\MediaInterface $media
   *   A media item.
   *
   * @return string
   *   The local thumbnail URI.
   *
   * @todo Determine whether or not oEmbed media thumbnails should be stored
   * locally at all, and if so, whether that functionality should be
   * toggle-able. See https://www.drupal.org/project/drupal/issues/2962751 for
   * more information.
   */
  protected function getLocalImageUri(MediaInterface $media) {
    $remote_url = $this->getMetadata($media, 'thumbnail_url');
    if (!$remote_url) {
      return parent::getMetadata($media, 'thumbnail_uri');
    }

    $configuration = $this->getConfiguration();
    $directory = $configuration['thumbnails_uri'];
    // Ensure that the destination directory is writable. If not, log a warning
    // and return the default thumbnail.
    if (!file_prepare_directory($directory, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS)) {
      $this->logger->warning('Could not prepare thumbnail destination directory @dir for oEmbed media.', [
        '@dir' => $directory,
      ]);
      return parent::getMetadata($media, 'thumbnail_uri');
    }

    return $directory . '/' . $media->uuid() . '.' . pathinfo($remote_url, PATHINFO_EXTENSION);
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
  public function getAllowedProviderNames() {
    $configuration = $this->getConfiguration();
    return $configuration['allowed_providers'] ?: $this->getSupportedProviderNames();
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedProviderNames() {
    return $this->getPluginDefinition()['supported_providers'];
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

<?php

namespace Drupal\hal\Normalizer;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\hal\LinkManager\LinkManagerInterface;
use GuzzleHttp\ClientInterface;

/**
 * Converts the Drupal entity object structure to a HAL array structure.
 *
 * @deprecated in Drupal 8.5.0, to be removed before Drupal 9.0.0.
 */
class FileEntityNormalizer extends ContentEntityNormalizer {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = 'Drupal\file\FileInterface';

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The HAL settings config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $halSettings;

  /**
   * Constructs a FileEntityNormalizer object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP Client.
   * @param \Drupal\hal\LinkManager\LinkManagerInterface $link_manager
   *   The hypermedia link manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(EntityManagerInterface $entity_manager, ClientInterface $http_client, LinkManagerInterface $link_manager, ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory) {
    parent::__construct($link_manager, $entity_manager, $module_handler);

    $this->httpClient = $http_client;
    $this->halSettings = $config_factory->get('hal.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, array $context = []) {
    $data = parent::normalize($entity, $format, $context);

    $this->addCacheableDependency($context, $this->halSettings);

    if ($this->halSettings->get('bc_file_uri_as_url_normalizer')) {
      // Replace the file url with a full url for the file.
      $data['uri'][0]['value'] = $this->getEntityUri($entity);
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = []) {
    $file_data = (string) $this->httpClient->get($data['uri'][0]['value'])->getBody();

    $path = 'temporary://' . drupal_basename($data['uri'][0]['value']);
    $data['uri'] = file_unmanaged_save_data($file_data, $path);

    return $this->entityManager->getStorage('file')->create($data);
  }

}

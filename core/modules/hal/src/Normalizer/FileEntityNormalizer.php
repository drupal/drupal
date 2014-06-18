<?php

/**
 * @file
 * Contains \Drupal\hal\Normalizer\FileEntityNormalizer.
 */

namespace Drupal\hal\Normalizer;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\rest\LinkManager\LinkManagerInterface;
use GuzzleHttp\ClientInterface;

/**
 * Converts the Drupal entity object structure to a HAL array structure.
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
   * Constructs a FileEntityNormalizer object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP Client.
   * @param \Drupal\rest\LinkManager\LinkManagerInterface $link_manager
   *   The hypermedia link manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(EntityManagerInterface $entity_manager, ClientInterface $http_client, LinkManagerInterface $link_manager, ModuleHandlerInterface $module_handler) {
    parent::__construct($link_manager, $entity_manager, $module_handler);

    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, array $context = array()) {
    $data = parent::normalize($entity, $format, $context);
    // Replace the file url with a full url for the file.
    $data['uri'][0]['value'] = $this->getEntityUri($entity);

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = array()) {
    $file_data = $this->httpClient->get($data['uri'][0]['value'])->getBody(TRUE);

    $path = 'temporary://' . drupal_basename($data['uri'][0]['value']);
    $data['uri'] = file_unmanaged_save_data($file_data, $path);

    return $this->entityManager->getStorage('file')->create($data);
  }

}

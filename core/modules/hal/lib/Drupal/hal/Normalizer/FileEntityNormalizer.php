<?php

/**
 * @file
 * Contains \Drupal\hal\Normalizer\FileEntityNormalizer.
 */

namespace Drupal\hal\Normalizer;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\file\Plugin\Core\Entity\File;
use Drupal\rest\LinkManager\LinkManagerInterface;
use Guzzle\Http\ClientInterface;

/**
 * Converts the Drupal entity object structure to a HAL array structure.
 */
class FileEntityNormalizer extends EntityNormalizer {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = 'Drupal\file\FileInterface';

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The HTTP client.
   *
   * @var \Guzzle\Http\ClientInterface
   */
  protected $httpClient;

  /**
   * Constructs a FileEntityNormalizer object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Guzzle\Http\ClientInterface $http_client
   *   The HTTP Client.
   * @param \Drupal\rest\LinkManager\LinkManagerInterface $link_manager
   *   The hypermedia link manager.
   */
  public function __construct(EntityManagerInterface $entity_manager, ClientInterface $http_client, LinkManagerInterface $link_manager) {
    parent::__construct($link_manager);

    $this->entityManager = $entity_manager;
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, array $context = array()) {
    $data = parent::normalize($entity, $format, $context);
    // Replace the file url with a full url for the file.
    $data['uri'][0]['value'] = file_create_url($data['uri'][0]['value']);

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = array()) {
    $file_data = $this->httpClient->get($data['uri'][0]['value'])
      ->send()
      ->getBody(TRUE);

    $path = 'temporary://' . drupal_basename($data['uri'][0]['value']);
    $data['uri'] = file_unmanaged_save_data($file_data, $path);

    return $this->entityManager->getStorageController('file')->create($data);
  }

}

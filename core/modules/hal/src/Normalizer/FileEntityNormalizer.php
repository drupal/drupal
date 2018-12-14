<?php

namespace Drupal\hal\Normalizer;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\file\FileInterface;
use Drupal\hal\LinkManager\LinkManagerInterface;

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
   * @param \Drupal\hal\LinkManager\LinkManagerInterface $link_manager
   *   The hypermedia link manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(EntityManagerInterface $entity_manager, LinkManagerInterface $link_manager, ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory) {
    parent::__construct($link_manager, $entity_manager, $module_handler);

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
  protected function getEntityUri(EntityInterface $entity, array $context = []) {
    assert($entity instanceof FileInterface);
    // https://www.drupal.org/project/drupal/issues/2277705 introduced a hack
    // in \Drupal\file\Entity\File::url(), but EntityInterface::url() was
    // deprecated in favor of ::toUrl(). The parent implementation now calls
    // ::toUrl(), but this normalizer (for File entities) needs to override that
    // back to the old behavior because it relies on said hack, not just to
    // generate the value for the 'uri' field of a file (see ::normalize()), but
    // also for the HAL normalization's '_links' value.
    return $entity->createFileUrl(FALSE);
  }

}

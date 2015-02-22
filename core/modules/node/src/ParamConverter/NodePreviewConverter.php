<?php

/**
 * @file
 * Contains \Drupal\node\ParamConverter\NodePreviewConverter.
 */

namespace Drupal\node\ParamConverter;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\Routing\Route;
use Drupal\Core\ParamConverter\ParamConverterInterface;

/**
 * Provides upcasting for a node entity in preview.
 */
class NodePreviewConverter implements ParamConverterInterface {

  /**
   * Stores the tempstore factory.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * Constructs a new NodePreviewConverter.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The factory for the temp store object.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory) {
    $this->tempStoreFactory = $temp_store_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    $store = $this->tempStoreFactory->get('node_preview');
    if ($form_state = $store->get($value)) {
      return $form_state->getFormObject()->getEntity();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    if (!empty($definition['type']) && $definition['type'] == 'node_preview') {
      return TRUE;
    }
    return FALSE;
  }

}

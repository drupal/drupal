<?php

namespace Drupal\node\ParamConverter;

use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\Routing\Route;
use Drupal\Core\ParamConverter\ParamConverterInterface;

/**
 * Provides upcasting for a node entity in preview.
 */
class NodePreviewConverter implements ParamConverterInterface {

  /**
   * Constructs a new NodePreviewConverter.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $tempStoreFactory
   *   The factory for the temp store object.
   */
  public function __construct(
    protected PrivateTempStoreFactory $tempStoreFactory,
  ) {
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

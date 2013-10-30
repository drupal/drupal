<?php

/**
 * @file
 * Contains \Drupal\views_ui\ParamConverter\ViewUIConverter.
 */

namespace Drupal\views_ui\ParamConverter;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\ParamConverter\EntityConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\user\TempStoreFactory;
use Drupal\views_ui\ViewUI;

/**
 * Provides upcasting for a view entity to be used in the Views UI.
 *
 * Example:
 *
 * pattern: '/some/{view}/and/{bar}'
 * options:
 *   parameters:
 *     view:
 *       type: 'entity:view'
 *       tempstore: TRUE
 *
 * The value for {view} will be converted to a view entity prepared for the
 * Views UI and loaded from the views temp store, but it will not touch the
 * value for {bar}.
 */
class ViewUIConverter extends EntityConverter implements ParamConverterInterface {

  /**
   * Stores the tempstore factory.
   *
   * @var \Drupal\user\TempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * Constructs a new ViewUIConverter.
   *
   * @param \Drupal\user\TempStoreFactory $temp_store_factory
   *   The factory for the temp store object.
   */
  public function __construct(EntityManagerInterface $entity_manager, TempStoreFactory $temp_store_factory) {
    parent::__construct($entity_manager);

    $this->tempStoreFactory = $temp_store_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults, Request $request) {
    if (!$entity = parent::convert($value, $definition, $name, $defaults, $request)) {
      return;
    }

    // Get the temp store for this variable if it needs one. Attempt to load the
    // view from the temp store, synchronize its status with the existing view,
    // and store the lock metadata.
    $store = $this->tempStoreFactory->get('views');
    if ($view = $store->get($value)) {
      if ($entity->status()) {
        $view->enable();
      }
      else {
        $view->disable();
      }
      $view->lock = $store->getMetadata($value);
    }
    // Otherwise, decorate the existing view for use in the UI.
    else {
      $view = new ViewUI($entity);
    }

    return $view;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    if (parent::applies($definition, $name, $route)) {
      return !empty($definition['tempstore']) && $definition['type'] === 'entity:view';
    }
    return FALSE;
  }

}

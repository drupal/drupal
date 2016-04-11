<?php

namespace Drupal\views_ui\ParamConverter;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\ParamConverter\AdminPathConfigEntityConverter;
use Drupal\Core\Routing\AdminContext;
use Symfony\Component\Routing\Route;
use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\user\SharedTempStoreFactory;
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
class ViewUIConverter extends AdminPathConfigEntityConverter implements ParamConverterInterface {

  /**
   * Stores the tempstore factory.
   *
   * @var \Drupal\user\SharedTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * Constructs a new ViewUIConverter.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\user\SharedTempStoreFactory $temp_store_factory
   *   The factory for the temp store object.
   */
  public function __construct(EntityManagerInterface $entity_manager, SharedTempStoreFactory $temp_store_factory, ConfigFactoryInterface $config_factory = NULL, AdminContext $admin_context = NULL) {
    // The config factory and admin context are new arguments due to changing
    // the parent. Avoid an error on updated sites by falling back to getting
    // them from the container.
    // @todo Remove in 8.2.x in https://www.drupal.org/node/2674328.
    if (!$config_factory) {
      $config_factory = \Drupal::configFactory();
    }
    if (!$admin_context) {
      $admin_context = \Drupal::service('router.admin_context');
    }
    parent::__construct($entity_manager, $config_factory, $admin_context);

    $this->tempStoreFactory = $temp_store_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    if (!$entity = parent::convert($value, $definition, $name, $defaults)) {
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

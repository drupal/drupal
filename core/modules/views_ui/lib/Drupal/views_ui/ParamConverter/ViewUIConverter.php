<?php

/**
 * @file
 * Contains \Drupal\views_ui\ParamConverter\ViewUIConverter.
 */

namespace Drupal\views_ui\ParamConverter;

use Symfony\Component\Routing\Route;
use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\user\TempStoreFactory;
use Drupal\views\ViewStorageInterface;
use Drupal\views_ui\ViewUI;

/**
 * Provides upcasting for a view entity to be used in the Views UI.
 */
class ViewUIConverter implements ParamConverterInterface {

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
  public function __construct(TempStoreFactory $temp_store_factory) {
    $this->tempStoreFactory = $temp_store_factory;
  }

  /**
   * Tries to upcast every view entity to a decorated ViewUI object.
   *
   * The key refers to the portion of the route that is a view entity that
   * should be prepared for the Views UI. If there is a non-null value, it will
   * be used as the collection of a temp store object used for loading.
   *
   * Example:
   *
   * pattern: '/some/{view}/and/{foo}/and/{bar}'
   * options:
   *   converters:
   *     foo: 'view'
   *   tempstore:
   *     view: 'views'
   *     foo: NULL
   *
   * The values for {view} and {foo} will be converted to view entities prepared
   * for the Views UI, with {view} being loaded from the views temp store, but
   * it will not touch the value for {bar}.
   *
   * Note: This requires that the placeholder either be named {view}, or that a
   * converter is specified as done above for {foo}.
   *
   * It will still process variables which are marked as converted. It will mark
   * any variable it processes as converted.
   *
   * @param array &$variables
   *   Array of values to convert to their corresponding objects, if applicable.
   * @param \Symfony\Component\Routing\Route $route
   *   The route object.
   * @param array &$converted
   *   Array collecting the names of all variables which have been
   *   altered by a converter.
   */
  public function process(array &$variables, Route $route, array &$converted) {
    // If nothing was specified to convert, return.
    $options = $route->getOptions();
    if (!isset($options['tempstore'])) {
      return;
    }

    foreach ($options['tempstore'] as $name => $collection) {
      // Only convert if the variable is a view.
      if ($variables[$name] instanceof ViewStorageInterface) {
        // Get the temp store for this variable if it needs one.
        // Attempt to load the view from the temp store, synchronize its
        // status with the existing view, and store the lock metadata.
        if ($collection && ($temp_store = $this->tempStoreFactory->get($collection)) && ($view = $temp_store->get($variables[$name]->id()))) {
          if ($variables[$name]->status()) {
            $view->enable();
          }
          else {
            $view->disable();
          }
          $view->lock = $temp_store->getMetadata($variables[$name]->id());
        }
        // Otherwise, decorate the existing view for use in the UI.
        else {
          $view = new ViewUI($variables[$name]);
        }

        // Store the new view and mark this variable as converted.
        $variables[$name] = $view;
        $converted[] = $name;
      }
    }
  }

}

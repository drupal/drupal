<?php

/**
 * @file
 * Contains \Drupal\Core\Form\ConfirmFormHelper.
 */

namespace Drupal\Core\Form;

use Drupal\Component\Utility\String;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides common functionality to confirmation forms.
 */
class ConfirmFormHelper {

  /**
   * Builds the cancel link for a confirmation form.
   *
   * @param \Drupal\Core\Form\ConfirmFormInterface $form
   *   The confirmation form.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return array
   *   The link render array for the cancel form.
   *
   * @throws \UnexpectedValueException
   *   Ensures that \Drupal\Core\Form\ConfirmFormInterface::getCancelRoute()
   *   returns an array containing a route name.
   */
  public static function buildCancelLink(ConfirmFormInterface $form, Request $request) {
    // Prepare cancel link.
    $query = $request->query;
    // If a destination is specified, that serves as the cancel link.
    if ($query->has('destination')) {
      $options = UrlHelper::parse($query->get('destination'));
      $link = array(
        '#href' => $options['path'],
        '#options' => $options,
      );
    }
    // Check for a route-based cancel link.
    elseif ($route = $form->getCancelRoute()) {
      if (!($route instanceof Url)) {
        if (empty($route['route_name'])) {
          throw new \UnexpectedValueException(String::format('Missing route name in !class::getCancelRoute().', array('!class' => get_class($form))));
        }
        // Ensure there is something to pass as the params and options.
        $route += array(
          'route_parameters' => array(),
          'options' => array(),
        );
        $route = new Url($route['route_name'], $route['route_parameters'], $route['options']);
      }

      $link = $route->toRenderArray();
    }

    $link['#type'] = 'link';
    $link['#title'] = $form->getCancelText();
    return $link;
  }

}

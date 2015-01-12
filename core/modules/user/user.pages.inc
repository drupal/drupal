<?php

/**
 * @file
 * User page callback file for the user module.
 */

use Drupal\Core\Render\Element;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Prepares variables for user templates.
 *
 * Default template: user.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *     fields attached to the user. Properties used:
 *     - #user: A \Drupal\user\Entity\User object. The user account of the
 *       profile being viewed.
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_user(&$variables) {
  $account = $variables['elements']['#user'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}

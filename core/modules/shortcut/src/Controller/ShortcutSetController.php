<?php

/**
 * @file
 * Contains \Drupal\shortcut\Controller\ShortcutSetController.
 */

namespace Drupal\shortcut\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\shortcut\ShortcutSetInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Builds the page for administering shortcut sets.
 */
class ShortcutSetController extends ControllerBase {

  /**
   * Creates a new link in the provided shortcut set.
   *
   * @param \Drupal\shortcut\ShortcutSetInterface $shortcut_set
   *   The shortcut set to add a link to.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response to the front page, or the previous location.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   */
  public function addShortcutLinkInline(ShortcutSetInterface $shortcut_set, Request $request) {
    $link = $request->query->get('link');
    $name = $request->query->get('name');
    if (shortcut_valid_link($link)) {
      $shortcut = $this->entityManager()->getStorage('shortcut')->create(array(
        'title' => $name,
        'shortcut_set' => $shortcut_set->id(),
        'path' => $link,
      ));

      try {
        $shortcut->save();
        drupal_set_message($this->t('Added a shortcut for %title.', array('%title' => $shortcut->label())));
      }
      catch (\Exception $e) {
        drupal_set_message($this->t('Unable to add a shortcut for %title.', array('%title' => $shortcut->label())));
      }

      return $this->redirect('<front>');
    }

    throw new AccessDeniedHttpException();
  }

}

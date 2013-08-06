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
    $token = $request->query->get('token');
    $link = $request->query->get('link');
    if (isset($token) && drupal_valid_token($token, 'shortcut-add-link') && shortcut_valid_link($link)) {
      $item = menu_get_item($link);
      $title = ($item && $item['title']) ? $item['title'] : $link;
      $link = array(
        'link_title' => $title,
        'link_path' => $link,
      );
      $this->moduleHandler()->loadInclude('shortcut', 'admin.inc');
      shortcut_admin_add_link($link, $shortcut_set);
      if ($shortcut_set->save() == SAVED_UPDATED) {
        drupal_set_message(t('Added a shortcut for %title.', array('%title' => $link['link_title'])));
      }
      else {
        drupal_set_message(t('Unable to add a shortcut for %title.', array('%title' => $link['link_title'])));
      }
      return new RedirectResponse($this->urlGenerator()->generateFromPath('<front>', array('absolute' => TRUE)));
    }

    throw new AccessDeniedHttpException();
  }

}

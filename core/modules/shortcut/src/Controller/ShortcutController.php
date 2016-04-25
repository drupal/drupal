<?php

namespace Drupal\shortcut\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\shortcut\ShortcutSetInterface;
use Drupal\shortcut\ShortcutInterface;

/**
 * Provides route responses for taxonomy.module.
 */
class ShortcutController extends ControllerBase {

  /**
   * Returns a form to add a new shortcut to a given set.
   *
   * @param \Drupal\shortcut\ShortcutSetInterface $shortcut_set
   *   The shortcut set this shortcut will be added to.
   *
   * @return array
   *   The shortcut add form.
   */
  public function addForm(ShortcutSetInterface $shortcut_set) {
    $shortcut = $this->entityManager()->getStorage('shortcut')->create(array('shortcut_set' => $shortcut_set->id()));
    return $this->entityFormBuilder()->getForm($shortcut, 'add');
  }

  /**
   * Deletes the selected shortcut.
   *
   * @param \Drupal\shortcut\ShortcutInterface $shortcut
   *   The shortcut to delete.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect to the previous location or the front page when destination
   *   is not set.
   */
  public function deleteShortcutLinkInline(ShortcutInterface $shortcut) {
    $label = $shortcut->label();

    try {
      $shortcut->delete();
      drupal_set_message($this->t('The shortcut %title has been deleted.', array('%title' => $label)));
    }
    catch (\Exception $e) {
      drupal_set_message($this->t('Unable to delete the shortcut for %title.', array('%title' => $label)), 'error');
    }

    return $this->redirect('<front>');
  }

}

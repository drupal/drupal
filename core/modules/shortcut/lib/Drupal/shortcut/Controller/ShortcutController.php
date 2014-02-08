<?php

/**
 * @file
 * Contains \Drupal\shortcut\Controller\ShortcutController.
 */

namespace Drupal\shortcut\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\shortcut\ShortcutSetInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides route responses for taxonomy.module.
 */
class ShortcutController extends ControllerBase {

  /**
   * Returns a rendered edit form to create a new shortcut associated to the
   * given shortcut set.
   *
   * @param \Drupal\shortcut\ShortcutSetInterface $shortcut_set
   *   The shortcut set this shortcut will be added to.
   *
   * @return array
   *   The shortcut add form.
   */
  public function addForm(ShortcutSetInterface $shortcut_set) {
    $shortcut = $this->entityManager()->getStorageController('shortcut')->create(array('shortcut_set' => $shortcut_set->id()));
    if ($this->moduleHandler()->moduleExists('language')) {
      $shortcut->langcode = language_get_default_langcode('shortcut', $shortcut_set->id());
    }
    return $this->entityFormBuilder()->getForm($shortcut, 'add');
  }

}

<?php
/**
 * @file
 * Contains \Drupal\form_test\Controller\FormTestController.
 */

namespace Drupal\form_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Language\LanguageInterface;

/**
 * Controller routines for form_test routes.
 */
class FormTestController extends ControllerBase {

  /**
   * Returns two instances of the node form.
   *
   * @return string
   *   A HTML-formatted string with the double node form page content.
   */
  public function twoFormInstances() {
    $user = $this->currentUser();
    $values = array(
      'uid' => $user->id(),
      'name' => $user->getUsername(),
      'type' => 'page',
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    );
    $node1 = $this->entityManager()->getStorage('node')->create($values);
    $node2 = clone($node1);
    $return['node_form_1'] = $this->entityFormBuilder()->getForm($node1);
    $return['node_form_2'] = $this->entityFormBuilder()->getForm($node2);
    return $return;
  }

}

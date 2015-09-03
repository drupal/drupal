<?php
/**
 * @file
 * Contains \Drupal\block_test\Controller\TestMultipleFormController.
 */

namespace Drupal\block_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormState;

/**
 * Controller for block_test module
 */
class TestMultipleFormController  extends ControllerBase {

  public function testMultipleForms() {
    $form_state = new FormState();
    $build = [
      'form1' => $this->formBuilder()->buildForm('\Drupal\block_test\Form\TestForm', $form_state),
      'form2' => $this->formBuilder()->buildForm('\Drupal\block_test\Form\FavoriteAnimalTestForm', $form_state),
    ];

    // Output all attached placeholders trough drupal_set_message(), so we can
    // see if there's only one in the tests.
    $post_render_callable = function ($elements) {
      $matches = [];
      preg_match_all('<form\s(.*?)action="(.*?)"(.*)>', $elements, $matches);

      $action_values = $matches[2];

      foreach ($action_values as $action_value) {
        drupal_set_message('Form action: ' . $action_value);
      }
      return $elements;
    };

    $build['#post_render'] = [$post_render_callable];

    return $build;
  }

}

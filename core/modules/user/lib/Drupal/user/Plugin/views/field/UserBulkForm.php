<?php

/**
 * @file
 * Contains \Drupal\user\Plugin\views\field\UserBulkForm.
 */

namespace Drupal\user\Plugin\views\field;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\views\Plugin\views\field\ActionBulkForm;
use Drupal\Component\Annotation\PluginID;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a user operations bulk form element.
 *
 * @PluginID("user_bulk_form")
 */
class UserBulkForm extends ActionBulkForm {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityManagerInterface $manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $manager);

    // Filter the actions to only include those for the 'user' entity type.
    $this->actions = array_filter($this->actions, function ($action) {
      return $action->getType() == 'user';
    });

  }

  /**
   * {@inheritdoc}
   *
   * Provide a more useful title to improve the accessibility.
   */
  public function viewsForm(&$form, &$form_state) {
    parent::viewsForm($form, $form_state);

    if (!empty($this->view->result)) {
      foreach ($this->view->result as $row_index => $result) {
        $account = $result->_entity;
        if ($account instanceof UserInterface) {
          $form[$this->options['id']][$row_index]['#title'] = t('Update the user %name', array('%name' => $account->label()));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function viewsFormValidate(&$form, &$form_state) {
    $selected = array_filter($form_state['values'][$this->options['id']]);
    if (empty($selected)) {
      form_set_error('', $form_state, t('No users selected.'));
    }
  }

}

<?php

/**
 * @file
 * Contains \Drupal\action\Form\ActionAdminManageForm.
 */

namespace Drupal\action\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Action\ActionManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a configuration form for configurable actions.
 */
class ActionAdminManageForm extends FormBase {

  /**
   * The action plugin manager.
   *
   * @var \Drupal\Core\Action\ActionManager
   */
  protected $manager;

  /**
   * Constructs a new ActionAdminManageForm.
   *
   * @param \Drupal\Core\Action\ActionManager $manager
   *   The action plugin manager.
   */
  public function __construct(ActionManager $manager) {
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.action')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'action_admin_manage';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $actions = array();
    foreach ($this->manager->getDefinitions() as $id => $definition) {
      if (is_subclass_of($definition['class'], '\Drupal\Core\Plugin\PluginFormInterface')) {
        $key = Crypt::hashBase64($id);
        $actions[$key] = $definition['label'] . '...';
      }
    }
    $form['parent'] = array(
      '#type' => 'details',
      '#title' => $this->t('Create an advanced action'),
      '#attributes' => array('class' => array('container-inline')),
      '#open' => TRUE,
    );
    $form['parent']['action'] = array(
      '#type' => 'select',
      '#title' => $this->t('Action'),
      '#title_display' => 'invisible',
      '#options' => $actions,
      '#empty_option' => $this->t('Choose an advanced action'),
    );
    $form['parent']['actions'] = array(
      '#type' => 'actions'
    );
    $form['parent']['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Create'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    if ($form_state['values']['action']) {
      $form_state['redirect_route'] = array(
        'route_name' => 'action.admin_add',
        'route_parameters' => array('action_id' => $form_state['values']['action']),
      );
    }
  }

}

<?php

namespace Drupal\action\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Action\ActionManager;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a configuration form for configurable actions.
 *
 * @internal
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
  public function buildForm(array $form, FormStateInterface $form_state) {
    $actions = [];
    foreach ($this->manager->getDefinitions() as $id => $definition) {
      if (is_subclass_of($definition['class'], '\Drupal\Core\Plugin\PluginFormInterface')) {
        $actions[$id] = $definition['label'];
      }
    }
    asort($actions);
    $form['parent'] = [
      '#type' => 'details',
      '#title' => $this->t('Create an advanced action'),
      '#attributes' => ['class' => ['container-inline']],
      '#open' => TRUE,
    ];
    $form['parent']['action'] = [
      '#type' => 'select',
      '#title' => $this->t('Action'),
      '#title_display' => 'invisible',
      '#options' => $actions,
      '#empty_option' => $this->t('- Select -'),
    ];
    $form['parent']['actions'] = [
      '#type' => 'actions',
    ];
    $form['parent']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('action')) {
      $form_state->setRedirect(
        'action.admin_add',
        ['action_id' => $form_state->getValue('action')]
      );
    }
  }

}

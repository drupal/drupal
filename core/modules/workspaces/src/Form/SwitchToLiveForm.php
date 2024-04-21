<?php

namespace Drupal\workspaces\Form;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\WorkspaceSafeFormInterface;
use Drupal\Core\Url;
use Drupal\workspaces\WorkspaceManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form that switches to the live version of the site.
 */
class SwitchToLiveForm extends ConfirmFormBase implements ContainerInjectionInterface, WorkspaceSafeFormInterface {

  /**
   * The workspace manager.
   *
   * @var \Drupal\workspaces\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * Constructs a new SwitchToLiveForm.
   *
   * @param \Drupal\workspaces\WorkspaceManagerInterface $workspace_manager
   *   The workspace manager.
   */
  public function __construct(WorkspaceManagerInterface $workspace_manager) {
    $this->workspaceManager = $workspace_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('workspaces.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'switch_to_live_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Would you like to switch to the live version of the site?');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Switch to the live version of the site.');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('<current>');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->workspaceManager->switchToLive();
    $this->messenger()->addMessage($this->t('You are now viewing the live version of the site.'));
  }

}

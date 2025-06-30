<?php

declare(strict_types=1);

namespace Drupal\workspaces_test\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\WorkspaceSafeFormInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\Core\Url;
use Drupal\workspaces\WorkspaceManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for testing the active workspace.
 *
 * @internal
 */
class ActiveWorkspaceTestForm extends FormBase implements WorkspaceSafeFormInterface {

  /**
   * The workspace manager.
   */
  protected WorkspaceManagerInterface $workspaceManager;

  /**
   * The test key-value store.
   */
  protected KeyValueStoreInterface $keyValue;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->workspaceManager = $container->get('workspaces.manager');
    $instance->keyValue = $container->get('keyvalue')->get('ws_test');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'active_workspace_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['test'] = [
      '#type' => 'textfield',
      '#ajax' => [
        'url' => Url::fromRoute('workspaces_test.get_form'),
        'callback' => function () {
          $this->keyValue->set('ajax_test_active_workspace', $this->workspaceManager->getActiveWorkspace()->id());
          return new AjaxResponse();
        },
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->keyValue->set('form_test_active_workspace', $this->workspaceManager->getActiveWorkspace()->id());
  }

}

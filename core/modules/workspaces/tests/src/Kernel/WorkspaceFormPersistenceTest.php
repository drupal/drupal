<?php

declare(strict_types=1);

namespace Drupal\Tests\workspaces\Kernel;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\workspaces\Entity\Workspace;
use Drupal\workspaces_test\Form\ActiveWorkspaceTestForm;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests form persistence for the active workspace.
 *
 * @group workspaces
 */
class WorkspaceFormPersistenceTest extends KernelTestBase {

  use UserCreationTrait;
  use WorkspaceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'workspaces',
    'workspaces_test',
  ];

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The form builder.
   */
  protected FormBuilderInterface $formBuilder;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = \Drupal::entityTypeManager();
    $this->formBuilder = \Drupal::formBuilder();

    $this->installEntitySchema('user');
    $this->installEntitySchema('workspace');

    Workspace::create(['id' => 'ham', 'label' => 'Ham'])->save();
    Workspace::create(['id' => 'cheese', 'label' => 'Cheese'])->save();

    $this->setCurrentUser($this->createUser([
      'view any workspace',
    ]));
  }

  /**
   * Tests that the active workspace is persisted throughout a form's lifecycle.
   */
  public function testFormPersistence(): void {
    $form_arg = ActiveWorkspaceTestForm::class;

    $this->switchToWorkspace('ham');
    $form_state_1 = new FormState();
    $form_1 = $this->formBuilder->buildForm($form_arg, $form_state_1);

    $this->switchToWorkspace('cheese');
    $form_state_2 = new FormState();
    $this->formBuilder->buildForm($form_arg, $form_state_2);

    // Submit the second form and check the workspace in which it was submitted.
    $this->formBuilder->submitForm($form_arg, $form_state_2);
    $this->assertSame('cheese', $this->keyValue->get('ws_test')->get('form_test_active_workspace'));

    // Submit the first form and check the workspace in which it was submitted.
    $this->formBuilder->submitForm($form_arg, $form_state_1);
    $this->assertSame('ham', $this->keyValue->get('ws_test')->get('form_test_active_workspace'));

    // Reset the workspace manager service to simulate a new request and check
    // that the second workspace is still active.
    \Drupal::getContainer()->set('workspaces.manager', NULL);
    $this->assertSame('cheese', \Drupal::service('workspaces.manager')->getActiveWorkspace()->id());

    // Reset the workspace manager service again to prepare for a new request.
    \Drupal::getContainer()->set('workspaces.manager', NULL);

    $request = Request::create(
      $form_1['test']['#ajax']['url']->toString(),
      'POST',
      [
        MainContentViewSubscriber::WRAPPER_FORMAT => 'drupal_ajax',
      ] + $form_1['test']['#attached']['drupalSettings']['ajax'][$form_1['test']['#id']]['submit'],
    );
    \Drupal::service('http_kernel')->handle($request);

    $form_state_1->setTriggeringElement($form_1['test']);
    \Drupal::service('form_ajax_response_builder')->buildResponse($request, $form_1, $form_state_1, []);

    // Check that the AJAX callback is executed in the initial workspace of its
    // parent form.
    $this->assertSame('ham', $this->keyValue->get('ws_test')->get('ajax_test_active_workspace'));

    // Reset the workspace manager service again and check that the AJAX request
    // didn't change the persisted workspace.
    \Drupal::getContainer()->set('workspaces.manager', NULL);
    \Drupal::requestStack()->pop();
    $this->assertSame('cheese', \Drupal::service('workspaces.manager')->getActiveWorkspace()->id());
  }

}

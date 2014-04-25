<?php

/**
 * @file
 * Contains \Drupal\action\ActionAddForm.
 */

namespace Drupal\action;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Action\ActionManager;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for action add forms.
 */
class ActionAddForm extends ActionFormBase {

  /**
   * The action manager.
   *
   * @var \Drupal\Core\Action\ActionManager
   */
  protected $actionManager;

  /**
   * Constructs a new ActionAddForm.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The action storage.
   * @param \Drupal\Core\Action\ActionManager $action_manager
   *   The action plugin manager.
   */
  public function __construct(EntityStorageInterface $storage, ActionManager $action_manager) {
    parent::__construct($storage);

    $this->actionManager = $action_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('action'),
      $container->get('plugin.manager.action')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @param string $action_id
   *   The hashed version of the action ID.
   */
  public function buildForm(array $form, array &$form_state, $action_id = NULL) {
    // In \Drupal\action\Form\ActionAdminManageForm::buildForm() the action
    // are hashed. Here we have to decrypt it to find the desired action ID.
    foreach ($this->actionManager->getDefinitions() as $id => $definition) {
      $key = Crypt::hashBase64($id);
      if ($key === $action_id) {
        $this->entity->setPlugin($id);
        // Derive the label and type from the action definition.
        $this->entity->set('label', $definition['label']);
        $this->entity->set('type', $definition['type']);
        break;
      }
    }

    return parent::buildForm($form, $form_state);
  }

}

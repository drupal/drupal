<?php

/**
 * @file
 * Contains \Drupal\shortcut\Form\SetCustomize.
 */

namespace Drupal\shortcut\Form;

use Drupal\Core\Entity\EntityFormController;
use Drupal\Core\Entity\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the shortcut set customize form.
 */
class SetCustomize extends EntityFormController {

  /**
   * The shortcut storage controller.
   *
   * @var \Drupal\Core\Entity\EntityStorageControllerInterface
   */
  protected $storageController;

  /**
   * Constructs a SetCustomize object.
   *
   * @param \Drupal\Core\Entity\EntityManager $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManager $entity_manager) {
    $this->storageController = $entity_manager->getStorageController('shortcut');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, array &$form_state) {
    $form = parent::form($form, $form_state);
    $form['shortcuts'] = array(
      '#tree' => TRUE,
      '#weight' => -20,
    );

    $form['shortcuts']['links'] = array(
      '#type' => 'table',
      '#header' => array(t('Name'), t('Weight'), t('Operations')),
      '#empty' => $this->t('No shortcuts available. <a href="@link">Add a shortcut</a>', array('@link' => $this->urlGenerator()->generateFromRoute('shortcut.link_add', array('shortcut_set' => $this->entity->id())))),
      '#attributes' => array('id' => 'shortcuts'),
      '#tabledrag' => array(
        array('order', 'sibling', 'shortcut-weight'),
      ),
    );

    $shortcuts = $this->storageController->loadByProperties(array('shortcut_set' => $this->entity->id()));
    foreach ($shortcuts as $shortcut) {
      $id = $shortcut->id();
      $form['shortcuts']['links'][$id]['#attributes']['class'][] = 'draggable';
      $form['shortcuts']['links'][$id]['name']['#markup'] = l($shortcut->title->value, $shortcut->path->value);
      $form['shortcuts']['links'][$id]['#weight'] = $shortcut->weight->value;
      $form['shortcuts']['links'][$id]['weight'] = array(
        '#type' => 'weight',
        '#title' => t('Weight for @title', array('@title' => $shortcut->title->value)),
        '#title_display' => 'invisible',
        '#default_value' => $shortcut->weight->value,
        '#attributes' => array('class' => array('shortcut-weight')),
      );

      $links['edit'] = array(
        'title' => t('Edit'),
        'href' => "admin/config/user-interface/shortcut/link/$id",
      );
      $links['delete'] = array(
        'title' => t('Delete'),
        'href' => "admin/config/user-interface/shortcut/link/$id/delete",
      );
      $form['shortcuts']['links'][$id]['operations'] = array(
        '#type' => 'operations',
        '#links' => $links,
      );
    }
    // Sort the list so the output is ordered by weight.
    uasort($form['shortcuts']['links'], 'element_sort');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, array &$form_state) {
    // Only includes a Save action for the entity, no direct Delete button.
    return array(
      'submit' => array(
        '#value' => t('Save changes'),
        '#access' => (bool) element_get_visible_children($form['shortcuts']['links']),
        '#submit' => array(
          array($this, 'submit'),
          array($this, 'save'),
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, array &$form_state) {
    $shortcuts = $this->storageController->loadByProperties(array('shortcut_set' => $this->entity->id()));
    foreach ($shortcuts as $shortcut) {
      $shortcut->weight->value = $form_state['values']['shortcuts']['links'][$shortcut->id()]['weight'];
      $shortcut->save();
    }
    drupal_set_message(t('The shortcut set has been updated.'));
  }

}

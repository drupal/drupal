<?php

namespace Drupal\outside_in\Form;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormBase;
use Drupal\Core\Render\Element;
use Drupal\Core\Routing\RedirectDestinationTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\system\MenuInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The off-canvas form handler for the SystemMenuBlock.
 *
 * @see outside_in_block_alter()
 */
class SystemMenuOffCanvasForm extends PluginFormBase implements ContainerInjectionInterface {

  use StringTranslationTrait;
  use RedirectDestinationTrait;

  /**
   * The plugin.
   *
   * @var \Drupal\Core\Block\BlockPluginInterface
   */
  protected $plugin;

  /**
   * @var \Drupal\system\MenuInterface
   */
  protected $entity;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $menuStorage;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * SystemMenuOffCanvasForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $menu_storage
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   */
  public function __construct(EntityStorageInterface $menu_storage, EntityTypeManagerInterface $entity_type_manager, TranslationInterface $string_translation) {
    $this->menuStorage = $menu_storage;
    $this->entityTypeManager = $entity_type_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('menu'),
      $container->get('entity_type.manager'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = $this->plugin->buildConfigurationForm([], $form_state);
    // Move the menu levels section to the bottom.
    $form['menu_levels']['#weight'] = 100;

    $form['entity_form'] = [
      '#type' => 'details',
      '#title' => $this->t('Edit menu %label', ['%label' => $this->entity->label()]),
      '#open' => TRUE,
    ];
    $form['entity_form'] += $this->getEntityForm($this->entity)->buildForm([], $form_state);

    // Print the menu link titles as text instead of a link.
    if (!empty($form['entity_form']['links']['links'])) {
      foreach (Element::children($form['entity_form']['links']['links']) as $child) {
        $title = $form['entity_form']['links']['links'][$child]['title'][1]['#title'];
        $form['entity_form']['links']['links'][$child]['title'][1] = ['#markup' => $title];
      }
    }
    // Change the header text.
    $form['entity_form']['links']['links']['#header'][0] = $this->t('Link');
    $form['entity_form']['links']['links']['#header'][1]['data'] = $this->t('On');

    // Remove the label, ID, description, and buttons from the entity form.
    unset($form['entity_form']['label'], $form['entity_form']['id'], $form['entity_form']['description'], $form['entity_form']['actions']);
    // Since the overview form is further nested than expected, update the
    // #parents. See \Drupal\menu_ui\MenuForm::form().
    $form_state->set('menu_overview_form_parents', ['settings', 'entity_form', 'links']);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->plugin->validateConfigurationForm($form, $form_state);
    $this->getEntityForm($this->entity)->validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->plugin->submitConfigurationForm($form, $form_state);
    $this->getEntityForm($this->entity)->submitForm($form, $form_state);
    $this->entity->save();
  }

  /**
   * Gets the entity form for this menu.
   *
   * @param \Drupal\system\MenuInterface $entity
   *   The menu entity.
   *
   * @return \Drupal\Core\Entity\EntityFormInterface
   *   The entity form.
   */
  protected function getEntityForm(MenuInterface $entity) {
    $entity_form = $this->entityTypeManager->getFormObject('menu', 'edit');
    $entity_form->setEntity($entity);
    return $entity_form;
  }

  /**
   * {@inheritdoc}
   */
  public function setPlugin(PluginInspectionInterface $plugin) {
    $this->plugin = $plugin;
    $this->entity = $this->menuStorage->load($this->plugin->getDerivativeId());
  }

}

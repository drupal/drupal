<?php

namespace Drupal\menu_ui\Form;

use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\MenuLinkInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a generic edit form for all menu link plugin types.
 *
 * The menu link plugin defines which class defines the corresponding form.
 *
 * @see \Drupal\Core\Menu\MenuLinkInterface::getFormClass()
 */
class MenuLinkEditForm extends FormBase {

  /**
   * The class resolver.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface
   */
  protected $classResolver;

  /**
   * Constructs a MenuLinkEditForm object.
   *
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   The class resolver.
   */
  public function __construct(ClassResolverInterface $class_resolver) {
    $this->classResolver = $class_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('class_resolver')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'menu_link_edit';
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Menu\MenuLinkInterface $menu_link_plugin
   *   The plugin instance to use for this form.
   */
  public function buildForm(array $form, FormStateInterface $form_state, MenuLinkInterface $menu_link_plugin = NULL) {
    $form['menu_link_id'] = array(
      '#type' => 'value',
      '#value' => $menu_link_plugin->getPluginId(),
    );
    $class_name = $menu_link_plugin->getFormClass();
    $form['#plugin_form'] = $this->classResolver->getInstanceFromDefinition($class_name);
    $form['#plugin_form']->setMenuLinkInstance($menu_link_plugin);

    $form += $form['#plugin_form']->buildConfigurationForm($form, $form_state);

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $form['#plugin_form']->validateConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $link = $form['#plugin_form']->submitConfigurationForm($form, $form_state);

    drupal_set_message($this->t('The menu link has been saved.'));
    $form_state->setRedirect(
      'entity.menu.edit_form',
      array('menu' => $link->getMenuName())
    );
  }

}

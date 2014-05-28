<?php

/**
 * @file
 * Contains \Drupal\path\Form\DeleteForm.
 */

namespace Drupal\path\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Path\AliasStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the form to delete a path alias.
 */
class DeleteForm extends ConfirmFormBase {

  /**
   * The alias storage service.
   *
   * @var AliasStorageInterface $path
   */
  protected $aliasStorage;

  /**
   * The path alias being deleted.
   *
   * @var array $pathAlias
   */
  protected $pathAlias;

  /**
   * Constructs a \Drupal\path\Form\DeleteForm object.
   *
   * @param \Drupal\Core\Path\AliasStorageInterface $alias_storage
   *   The alias storage service.
   */
  public function __construct(AliasStorageInterface $alias_storage) {
    $this->aliasStorage = $alias_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('path.alias_storage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'path_alias_delete';
  }

  /**
   * Implements \Drupal\Core\Form\ConfirmFormBase::getQuestion().
   */
  public function getQuestion() {
    return t('Are you sure you want to delete path alias %title?', array('%title' => $this->pathAlias['alias']));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
  }

  /**
   * Overrides \Drupal\Core\Form\ConfirmFormBase::buildForm().
   */
  public function buildForm(array $form, array &$form_state, $pid = NULL) {
    $this->pathAlias = $this->aliasStorage->load(array('pid' => $pid));

    $form = parent::buildForm($form, $form_state);

    // @todo Convert to getCancelRoute() after http://drupal.org/node/1987802.
    $form['actions']['cancel']['#href'] = 'admin/config/search/path';
    return $form;
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->aliasStorage->delete(array('pid' => $this->pathAlias['pid']));

    $form_state['redirect'] = 'admin/config/search/path';
  }
}

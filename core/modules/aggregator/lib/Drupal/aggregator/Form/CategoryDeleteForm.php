<?php

/**
 * @file
 * Contains \Drupal\aggregator\Form\CategoryDeleteForm.
 */

namespace Drupal\aggregator\Form;

use Drupal\aggregator\CategoryStorageControllerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a confirm delete form.
 */
class CategoryDeleteForm extends ConfirmFormBase implements ContainerInjectionInterface {

  /**
   * The category to be deleted.
   *
   * @var array
   */
  protected $category;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The category storage controller.
   *
   * @var \Drupal\aggregator\CategoryStorageControllerInterface
   */
  protected $categoryStorageController;

  /**
   * Creates a new CategoryDeleteForm.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param CategoryStorageControllerInterface $category_storage_controller
   *   The category storage controller.
   */
  public function __construct(ModuleHandlerInterface $module_handler, EntityManagerInterface $entity_manager, CategoryStorageControllerInterface $category_storage_controller) {
    $this->moduleHandler = $module_handler;
    $this->entityManager = $entity_manager;
    $this->categoryStorageController = $category_storage_controller;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static (
      $container->get('module_handler'),
      $container->get('entity.manager'),
      $container->get('aggregator.category.storage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the category %title?', array('%title' => $this->category->title));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
    return array(
      'route_name' => 'aggregator.admin_overview',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'aggregator_category_delete';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This will delete the aggregator category, the menu item for this category, and any related category blocks.');
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param array $form_state
   *   An associative array containing the current state of the form.
   * @param int|null $cid
   *   The category ID.
   *
   * @return array
   *   The form structure.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   If the cid param or category is not found.
   */
  public function buildForm(array $form, array &$form_state, $cid = NULL) {
    $category = $this->categoryStorageController->load($cid);
    if (empty($cid) || empty($category)) {
      throw new NotFoundHttpException();
    }
    $this->category = $category;
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $cid = $this->category->cid;
    $title = $this->category->title;
    $this->categoryStorageController->delete($cid);
    // Make sure there is no active block for this category.
    $this->deleteBlocks($cid);
    watchdog('aggregator', 'Category %category deleted.', array('%category' => $title));
    drupal_set_message($this->t('The category %category has been deleted.', array('%category' => $title)));
    if (preg_match('/^\/admin/', $this->getRequest()->getPathInfo())) {
      $form_state['redirect'] = 'admin/config/services/aggregator/';
    }
    else {
      $form_state['redirect'] = 'aggregator';
    }
    $this->updateMenuLink('delete', 'aggregator/categories/' . $cid, $title);
  }

  /**
   * Delete aggregator category blocks.
   *
   * @param int $cid
   *   The category ID.
   */
  protected function deleteBlocks($cid) {
    if ($this->moduleHandler->moduleExists('block')) {
      foreach ($this->entityManager->getStorageController('block')->loadByProperties(array('plugin' => 'aggregator_category_block:' . $cid)) as $block) {
        $block->delete();
      }
    }
  }

  /**
   * Updates a category menu link.
   *
   * @param string $op
   *   The operation to perform.
   * @param string $link_path
   *   The path of the menu link.
   * @param string $title
   *   The title of the menu link.
   *
   * @see menu_link_maintain()
   */
  protected function updateMenuLink($op, $link_path, $title) {
    if (isset($op) && $this->moduleHandler->moduleExists('menu_link')) {
      menu_link_maintain('aggregator', $op, $link_path, $title);
    }
  }

}

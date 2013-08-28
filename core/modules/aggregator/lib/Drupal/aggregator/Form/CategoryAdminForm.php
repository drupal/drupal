<?php

/**
 * @file
 * Contains Drupal\aggregator\Form\CategoryAdminForm.
 */

namespace Drupal\aggregator\Form;

use Drupal\aggregator\CategoryStorageControllerInterface;
use Drupal\block\Plugin\Type\BlockManager;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for configuring aggregator categories.
 */
class CategoryAdminForm extends FormBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The category storage controller.
   *
   * @var \Drupal\aggregator\CategoryStorageControllerInterface.
   */
  protected $categoryStorageController;

  /**
   * The block manager.
   *
   * @var \Drupal\block\Plugin\Type\BlockManager
   */
  protected $blockManager;

  /**
   * Creates a new CategoryForm object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\aggregator\CategoryStorageControllerInterface $category_storage_controller
   *   The category storage controller.
   * @param \Drupal\block\Plugin\Type\BlockManager $block_manager
   *   (optional) The block manager. Used if block module is enabled.
   */
  public function __construct(ModuleHandlerInterface $module_handler, CategoryStorageControllerInterface $category_storage_controller, BlockManager $block_manager = NULL) {
    $this->moduleHandler = $module_handler;
    $this->categoryStorageController = $category_storage_controller;
    $this->blockManager = $block_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $block_manager = NULL;
    if ($container->get('module_handler')->moduleExists('block')) {
      $block_manager = $container->get('plugin.manager.block');
    }
    return new static(
      $container->get('module_handler'),
      $container->get('aggregator.category.storage'),
      $block_manager
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'aggregator_form_category';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, $cid = NULL) {
    $category = $this->categoryStorageController->load($cid);
    $form['title'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => isset($category->title) ? $category->title : '',
      '#maxlength' => 64,
      '#required' => TRUE,
    );
    $form['description'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => isset($category->description) ? $category->description : '',
    );
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    );
    if (!empty($category->cid)) {
      $form['actions']['delete'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Delete'),
      );
      $form['cid'] = array('#type' => 'hidden', '#value' => $category->cid);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    if ($form_state['values']['op'] == $this->t('Save')) {
      // Check for duplicate titles.
      $title = $form_state['values']['title'];
      if (isset($form_state['values']['cid'])) {
        // Exclude the current category ID when checking if it's unique.
        $unique = $this->categoryStorageController->isUnique($title, $form_state['values']['cid']);
      }
      else {
        $unique = $this->categoryStorageController->isUnique($title);
      }
      if (!$unique) {
        form_set_error('title', $this->t('A category named %category already exists. Enter a unique title.', array('%category' => $title)));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    // @todo Replicate this cache invalidation when these ops are separated.
    // Invalidate the block cache to update aggregator category-based derivatives.
    $this->clearBlockCache();

    $link_path = 'aggregator/categories/';
    $title = $form_state['values']['title'];

    // Redirect to a confirm delete form.
    if ($form_state['values']['op'] == $this->t('Delete')) {
      $cid = $form_state['values']['cid'];
      $form_state['redirect'] = 'admin/config/services/aggregator/delete/category/' . $cid;
      return;
    }

    // Update the category.
    if (!empty($form_state['values']['cid'])) {
      $cid = $form_state['values']['cid'];
      $this->categoryStorageController->update((object) $form_state['values']);
      drupal_set_message($this->t('The category %category has been updated.', array('%category' => $title)));
      if (preg_match('/^\/admin/', $this->getRequest()->getPathInfo())) {
        $form_state['redirect'] = 'admin/config/services/aggregator/';
      }
      else {
        $form_state['redirect'] = 'aggregator/categories/' . $cid;
      }
      $this->updateMenuLink('update', $link_path . $cid, $title);
      return;
    }

    // Insert the category.
    $cid = $this->categoryStorageController->save((object) $form_state['values']);
    watchdog('aggregator', 'Category %category added.', array('%category' => $form_state['values']['title']), WATCHDOG_NOTICE, l($this->t('view'), 'admin/config/services/aggregator'));
    drupal_set_message($this->t('The category %category has been added.', array('%category' => $title)));

    $this->updateMenuLink('insert', $link_path . $cid, $title);

  }

  /**
   * Clear the block cached definitions.
   */
  protected function clearBlockCache() {
    if (!empty($this->blockManager)) {
      $this->blockManager->clearCachedDefinitions();
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

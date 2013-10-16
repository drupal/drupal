<?php

/**
 * @file
 * Contains \Drupal\aggregator\Form\AggregatorCategorizeFormBase.
 */

namespace Drupal\aggregator\Form;

use Drupal\aggregator\CategoryStorageControllerInterface;
use Drupal\aggregator\FeedInterface;
use Drupal\aggregator\ItemStorageControllerInterface;
use Drupal\Component\Utility\String;
use Drupal\Core\Entity\EntityRenderControllerInterface;
use Drupal\Core\Form\FormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base form to allow items to be categorized.
 */
abstract class AggregatorCategorizeFormBase extends FormBase {

  /**
   * The aggregator item render controller.
   *
   * @var \Drupal\Core\Entity\EntityRenderControllerInterface
   */
  protected $aggregatorItemRenderer;

  /**
   * The aggregator config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The aggregator item storage controller.
   *
   * @var \Drupal\aggregator\ItemStorageControllerInterface
   */
  protected $aggregatorItemStorage;

  /**
   * The aggregator category storage controller.
   *
   * @var \Drupal\aggregator\CategoryStorageControllerInterface
   */
  protected $categoryStorage;

  /**
   * The feed to use.
   *
   * @var \Drupal\aggregator\FeedInterface
   */
  protected $feed;

  /**
   * Constructs a \Drupal\aggregator\Controller\AggregatorController object.
   *
   * @param \Drupal\Core\Entity\EntityRenderControllerInterface $aggregator_item_renderer
   *   The item render controller.
   * @param \Drupal\aggregator\ItemStorageControllerInterface $aggregator_item_storage
   *   The aggregator item storage controller.
   * @param \Drupal\aggregator\CategoryStorageControllerInterface $category_storage
   *   The category storage controller.
   */
  public function __construct(EntityRenderControllerInterface $aggregator_item_renderer, ItemStorageControllerInterface $aggregator_item_storage, CategoryStorageControllerInterface $category_storage) {
    $this->aggregatorItemRenderer = $aggregator_item_renderer;
    $this->config = $this->config('aggregator.settings');
    $this->aggregatorItemStorage = $aggregator_item_storage;
    $this->categoryStorage = $category_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.entity')->getRenderController('aggregator_item'),
      $container->get('plugin.manager.entity')->getStorageController('aggregator_item'),
      $container->get('aggregator.category.storage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, array $items = NULL) {

    $form['feed_source'] = array(
      '#value' => $this->feed,
    );
    $categories = array();
    $done = FALSE;

    $form['items'] = array(
      '#type' => 'table',
      '#header' => array('', $this->t('Categorize')),
    );
    if ($items && ($form_items = $this->aggregatorItemRenderer->viewMultiple($items, 'default'))) {
      foreach (element_children($form_items) as $iid) {
        $categories_result = $this->categoryStorage->loadByItem($iid);

        $selected = array();
        foreach ($categories_result as $category) {
          if (!$done) {
            $categories[$category->cid] = String::checkPlain($category->title);
          }
          if ($category->iid) {
            $selected[] = $category->cid;
          }
        }
        $done = TRUE;
        $form['items'][$iid]['item'] = $form_items[$iid];
        $form['items'][$iid]['categories'] = array(
          '#type' => $this->config->get('source.category_selector'),
          '#default_value' => $selected,
          '#options' => $categories,
          '#size' => 10,
          '#multiple' => TRUE,
          '#parents' => array('categories', $iid),
        );
      }
    }

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save categories'),
    );
    $form['pager'] = array('#theme' => 'pager');

    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    if (!empty($form_state['values']['categories'])) {
      foreach ($form_state['values']['categories'] as $iid => $cids) {
        $this->categoryStorage->updateItem($iid, $cids);
      }
    }
    drupal_set_message($this->t('The categories have been saved.'));
  }

}

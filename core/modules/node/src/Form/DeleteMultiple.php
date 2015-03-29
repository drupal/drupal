<?php

/**
 * @file
 * Contains \Drupal\node\Form\DeleteMultiple.
 */

namespace Drupal\node\Form;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Url;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a node deletion confirmation form.
 */
class DeleteMultiple extends ConfirmFormBase {

  /**
   * The array of nodes to delete.
   *
   * @var array
   */
  protected $nodes = array();

  /**
   * The tempstore factory.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The node storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $manager;

  /**
   * Constructs a DeleteMultiple form object.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Entity\EntityManagerInterface $manager
   *   The entity manager.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, EntityManagerInterface $manager) {
    $this->tempStoreFactory = $temp_store_factory;
    $this->storage = $manager->getStorage('node');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore'),
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'node_multiple_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->formatPlural(count($this->nodes), 'Are you sure you want to delete this item?', 'Are you sure you want to delete these items?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('system.admin_content');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->nodes = $this->tempStoreFactory->get('node_multiple_delete_confirm')->get(\Drupal::currentUser()->id());
    if (empty($this->nodes)) {
      return new RedirectResponse($this->getCancelUrl()->setAbsolute()->toString());
    }

    $form['nodes'] = array(
      '#theme' => 'item_list',
      '#items' => array_map(function ($node) {
        return SafeMarkup::checkPlain($node->label());
      }, $this->nodes),
    );
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('confirm') && !empty($this->nodes)) {
      $this->storage->delete($this->nodes);
      $this->tempStoreFactory->get('node_multiple_delete_confirm')->delete(\Drupal::currentUser()->id());
      $count = count($this->nodes);
      $this->logger('content')->notice('Deleted @count posts.', array('@count' => $count));
      drupal_set_message($this->formatPlural($count, 'Deleted 1 post.', 'Deleted @count posts.'));
    }
    $form_state->setRedirect('system.admin_content');
  }

}

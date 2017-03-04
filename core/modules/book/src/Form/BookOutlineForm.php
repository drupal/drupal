<?php

namespace Drupal\book\Form;

use Drupal\book\BookManagerInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Displays the book outline form.
 */
class BookOutlineForm extends ContentEntityForm {

  /**
   * The book being displayed.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $entity;

  /**
   * BookManager service.
   *
   * @var \Drupal\book\BookManagerInterface
   */
  protected $bookManager;

  /**
   * Constructs a BookOutlineForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\book\BookManagerInterface $book_manager
   *   The BookManager service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(EntityManagerInterface $entity_manager, BookManagerInterface $book_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL) {
    parent::__construct($entity_manager, $entity_type_bundle_info, $time);
    $this->bookManager = $book_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('book.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFormId() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form['#title'] = $this->entity->label();

    if (!isset($this->entity->book)) {
      // The node is not part of any book yet - set default options.
      $this->entity->book = $this->bookManager->getLinkDefaults($this->entity->id());
    }
    else {
      $this->entity->book['original_bid'] = $this->entity->book['bid'];
    }

    // Find the depth limit for the parent select.
    if (!isset($this->entity->book['parent_depth_limit'])) {
      $this->entity->book['parent_depth_limit'] = $this->bookManager->getParentDepthLimit($this->entity->book);
    }
    $form = $this->bookManager->addFormElements($form, $form_state, $this->entity, $this->currentUser(), FALSE);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->entity->book['original_bid'] ? $this->t('Update book outline') : $this->t('Add to book outline');
    $actions['delete']['#title'] = $this->t('Remove from book outline');
    $actions['delete']['#url'] = new Url('entity.node.book_remove_form', ['node' => $this->entity->book['nid']]);
    $actions['delete']['#access'] = $this->bookManager->checkNodeIsRemovable($this->entity);
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $form_state->setRedirect(
      'entity.node.canonical',
      ['node' => $this->entity->id()]
    );
    $book_link = $form_state->getValue('book');
    if (!$book_link['bid']) {
      drupal_set_message($this->t('No changes were made'));
      return;
    }

    $this->entity->book = $book_link;
    if ($this->bookManager->updateOutline($this->entity)) {
      if (isset($this->entity->book['parent_mismatch']) && $this->entity->book['parent_mismatch']) {
        // This will usually only happen when JS is disabled.
        drupal_set_message($this->t('The post has been added to the selected book. You may now position it relative to other pages.'));
        $form_state->setRedirectUrl($this->entity->urlInfo('book-outline-form'));
      }
      else {
        drupal_set_message($this->t('The book outline has been updated.'));
      }
    }
    else {
      drupal_set_message($this->t('There was an error adding the post to the book.'), 'error');
    }
  }

}

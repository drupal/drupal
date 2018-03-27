<?php

namespace Drupal\comment\Form;

<<<<<<< HEAD
use Drupal\Core\Entity\Form\DeleteMultipleForm as EntityDeleteMultipleForm;
=======
use Drupal\comment\CommentStorageInterface;
use Drupal\user\PrivateTempStoreFactory;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
>>>>>>> e6affc593631de76bc37f1e5340dde005ad9b0bd
use Drupal\Core\Url;

/**
 * Provides the comment multiple delete confirmation form.
 *
 * @internal
 */
<<<<<<< HEAD
class ConfirmDeleteMultiple extends EntityDeleteMultipleForm {
=======
class ConfirmDeleteMultiple extends ConfirmFormBase {

  /**
   * The tempstore factory.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The comment storage.
   *
   * @var \Drupal\comment\CommentStorageInterface
   */
  protected $commentStorage;

  /**
   * An array of comments to be deleted.
   *
   * @var string[][]
   */
  protected $commentInfo;

  /**
   * Creates an new ConfirmDeleteMultiple form.
   *
   * @param \Drupal\comment\CommentStorageInterface $comment_storage
   *   The comment storage.
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   */
  public function __construct(CommentStorageInterface $comment_storage, PrivateTempStoreFactory $temp_store_factory) {
    $this->commentStorage = $comment_storage;
    $this->tempStoreFactory = $temp_store_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('comment'),
      $container->get('user.private_tempstore')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'comment_multiple_delete_confirm';
  }
>>>>>>> e6affc593631de76bc37f1e5340dde005ad9b0bd

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
<<<<<<< HEAD
    return $this->formatPlural(count($this->selection), 'Are you sure you want to delete this comment and all its children?', 'Are you sure you want to delete these comments and all their children?');
=======
    return $this->formatPlural(count($this->commentInfo), 'Are you sure you want to delete this comment and all its children?', 'Are you sure you want to delete these comments and all their children?');
>>>>>>> e6affc593631de76bc37f1e5340dde005ad9b0bd
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('comment.admin');
  }

  /**
   * {@inheritdoc}
   */
<<<<<<< HEAD
  protected function getDeletedMessage($count) {
    return $this->formatPlural($count, 'Deleted @count comment.', 'Deleted @count comments.');
=======
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->commentInfo = $this->tempStoreFactory->get('comment_multiple_delete_confirm')->get($this->currentUser()->id());
    if (empty($this->commentInfo)) {
      return $this->redirect('comment.admin');
    }
    /** @var \Drupal\comment\CommentInterface[] $comments */
    $comments = $this->commentStorage->loadMultiple(array_keys($this->commentInfo));

    $items = [];
    foreach ($this->commentInfo as $id => $langcodes) {
      foreach ($langcodes as $langcode) {
        $comment = $comments[$id]->getTranslation($langcode);
        $key = $id . ':' . $langcode;
        $default_key = $id . ':' . $comment->getUntranslated()->language()->getId();

        // If we have a translated entity we build a nested list of translations
        // that will be deleted.
        $languages = $comment->getTranslationLanguages();
        if (count($languages) > 1 && $comment->isDefaultTranslation()) {
          $names = [];
          foreach ($languages as $translation_langcode => $language) {
            $names[] = $language->getName();
            unset($items[$id . ':' . $translation_langcode]);
          }
          $items[$default_key] = [
            'label' => [
              '#markup' => $this->t('@label (Original translation) - <em>The following comment translations will be deleted:</em>', ['@label' => $comment->label()]),
            ],
            'deleted_translations' => [
              '#theme' => 'item_list',
              '#items' => $names,
            ],
          ];
        }
        elseif (!isset($items[$default_key])) {
          $items[$key] = $comment->label();
        }
      }
    }

    $form['comments'] = [
      '#theme' => 'item_list',
      '#items' => $items,
    ];

    return parent::buildForm($form, $form_state);
>>>>>>> e6affc593631de76bc37f1e5340dde005ad9b0bd
  }

  /**
   * {@inheritdoc}
   */
<<<<<<< HEAD
  protected function getInaccessibleMessage($count) {
    return $this->formatPlural($count, "@count comment has not been deleted because you do not have the necessary permissions.", "@count comments have not been deleted because you do not have the necessary permissions.");
=======
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('confirm') && !empty($this->commentInfo)) {
      $total_count = 0;
      $delete_comments = [];
      /** @var \Drupal\Core\Entity\ContentEntityInterface[][] $delete_translations */
      $delete_translations = [];
      /** @var \Drupal\comment\CommentInterface[] $comments */
      $comments = $this->commentStorage->loadMultiple(array_keys($this->commentInfo));

      foreach ($this->commentInfo as $id => $langcodes) {
        foreach ($langcodes as $langcode) {
          $comment = $comments[$id]->getTranslation($langcode);
          if ($comment->isDefaultTranslation()) {
            $delete_comments[$id] = $comment;
            unset($delete_translations[$id]);
            $total_count += count($comment->getTranslationLanguages());
          }
          elseif (!isset($delete_comments[$id])) {
            $delete_translations[$id][] = $comment;
          }
        }
      }

      if ($delete_comments) {
        $this->commentStorage->delete($delete_comments);
        $this->logger('content')->notice('Deleted @count comments.', ['@count' => count($delete_comments)]);
      }

      if ($delete_translations) {
        $count = 0;
        foreach ($delete_translations as $id => $translations) {
          $comment = $comments[$id]->getUntranslated();
          foreach ($translations as $translation) {
            $comment->removeTranslation($translation->language()->getId());
          }
          $comment->save();
          $count += count($translations);
        }
        if ($count) {
          $total_count += $count;
          $this->logger('content')->notice('Deleted @count comment translations.', ['@count' => $count]);
        }
      }

      if ($total_count) {
        drupal_set_message($this->formatPlural($total_count, 'Deleted 1 comment.', 'Deleted @count comments.'));
      }

      $this->tempStoreFactory->get('comment_multiple_delete_confirm')->delete($this->currentUser()->id());
    }

    $form_state->setRedirectUrl($this->getCancelUrl());
>>>>>>> e6affc593631de76bc37f1e5340dde005ad9b0bd
  }

}

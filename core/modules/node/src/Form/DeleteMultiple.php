<?php

namespace Drupal\node\Form;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides a node deletion confirmation form.
 */
class DeleteMultiple extends ConfirmFormBase {

  /**
   * The array of nodes to delete.
   *
   * @var string[][]
   */
  protected $nodeInfo = [];

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
    return $this->formatPlural(count($this->nodeInfo), 'Are you sure you want to delete this item?', 'Are you sure you want to delete these items?');
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
    $this->nodeInfo = $this->tempStoreFactory->get('node_multiple_delete_confirm')->get(\Drupal::currentUser()->id());
    if (empty($this->nodeInfo)) {
      return new RedirectResponse($this->getCancelUrl()->setAbsolute()->toString());
    }
    /** @var \Drupal\node\NodeInterface[] $nodes */
    $nodes = $this->storage->loadMultiple(array_keys($this->nodeInfo));

    $items = [];
    foreach ($this->nodeInfo as $id => $langcodes) {
      foreach ($langcodes as $langcode) {
        $node = $nodes[$id]->getTranslation($langcode);
        $key = $id . ':' . $langcode;
        $default_key = $id . ':' . $node->getUntranslated()->language()->getId();

        // If we have a translated entity we build a nested list of translations
        // that will be deleted.
        $languages = $node->getTranslationLanguages();
        if (count($languages) > 1 && $node->isDefaultTranslation()) {
          $names = [];
          foreach ($languages as $translation_langcode => $language) {
            $names[] = $language->getName();
            unset($items[$id . ':' . $translation_langcode]);
          }
          $items[$default_key] = [
            'label' => [
              '#markup' => $this->t('@label (Original translation) - <em>The following content translations will be deleted:</em>', ['@label' => $node->label()]),
            ],
            'deleted_translations' => [
              '#theme' => 'item_list',
              '#items' => $names,
            ],
          ];
        }
        elseif (!isset($items[$default_key])) {
          $items[$key] = $node->label();
        }
      }
    }

    $form['nodes'] = [
      '#theme' => 'item_list',
      '#items' => $items,
    ];
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('confirm') && !empty($this->nodeInfo)) {
      $total_count = 0;
      $delete_nodes = [];
      /** @var \Drupal\Core\Entity\ContentEntityInterface[][] $delete_translations */
      $delete_translations = [];
      /** @var \Drupal\node\NodeInterface[] $nodes */
      $nodes = $this->storage->loadMultiple(array_keys($this->nodeInfo));

      foreach ($this->nodeInfo as $id => $langcodes) {
        foreach ($langcodes as $langcode) {
          $node = $nodes[$id]->getTranslation($langcode);
          if ($node->isDefaultTranslation()) {
            $delete_nodes[$id] = $node;
            unset($delete_translations[$id]);
            $total_count += count($node->getTranslationLanguages());
          }
          elseif (!isset($delete_nodes[$id])) {
            $delete_translations[$id][] = $node;
          }
        }
      }

      if ($delete_nodes) {
        $this->storage->delete($delete_nodes);
        $this->logger('content')->notice('Deleted @count posts.', ['@count' => count($delete_nodes)]);
      }

      if ($delete_translations) {
        $count = 0;
        foreach ($delete_translations as $id => $translations) {
          $node = $nodes[$id]->getUntranslated();
          foreach ($translations as $translation) {
            $node->removeTranslation($translation->language()->getId());
          }
          $node->save();
          $count += count($translations);
        }
        if ($count) {
          $total_count += $count;
          $this->logger('content')->notice('Deleted @count content translations.', ['@count' => $count]);
        }
      }

      if ($total_count) {
        drupal_set_message($this->formatPlural($total_count, 'Deleted 1 post.', 'Deleted @count posts.'));
      }

      $this->tempStoreFactory->get('node_multiple_delete_confirm')->delete(\Drupal::currentUser()->id());
    }

    $form_state->setRedirect('system.admin_content');
  }

}

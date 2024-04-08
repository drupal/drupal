<?php

namespace Drupal\system\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a form removing module content entities data before uninstallation.
 *
 * @internal
 */
class PrepareModulesEntityUninstallForm extends ConfirmFormBase {

  /**
   * The entity type ID of the entities to delete.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a PrepareModulesEntityUninstallForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'system_prepare_modules_entity_uninstall';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $entity_type = $this->entityTypeManager->getDefinition($this->entityTypeId);

    return $this->t('Are you sure you want to delete all @entity_type_plural?', ['@entity_type_plural' => $entity_type->getPluralLabel()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This action cannot be undone.<br />Make a backup of your database if you want to be able to restore these items.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    $entity_type = $this->entityTypeManager->getDefinition($this->entityTypeId);

    return $this->t('Delete all @entity_type_plural', ['@entity_type_plural' => $entity_type->getPluralLabel()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('system.modules_uninstall');
  }

  /**
   * Gets the form title.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The form title.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown when the entity-type does not exist.
   */
  public function formTitle(string $entity_type_id): TranslatableMarkup {
    $this->entityTypeId = $entity_type_id;
    return $this->getQuestion();
  }

  /**
   * Checks access based on the validity of the entity type ID.
   *
   * @param string $entity_type_id
   *   Entity type ID.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public static function checkAccess(string $entity_type_id): AccessResultInterface {
    return AccessResult::allowedIf(\Drupal::entityTypeManager()->hasDefinition($entity_type_id));
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL) {
    $this->entityTypeId = $entity_type_id;
    if (!$this->entityTypeManager->hasDefinition($this->entityTypeId)) {
      throw new NotFoundHttpException();
    }
    $form = parent::buildForm($form, $form_state);

    $storage = $this->entityTypeManager->getStorage($entity_type_id);
    $count = $storage->getQuery()->accessCheck(FALSE)->count()->execute();
    $accessible_count = $storage->getQuery()->accessCheck(TRUE)->count()->execute();

    $form['entity_type_id'] = [
      '#type' => 'value',
      '#value' => $entity_type_id,
    ];

    // Display a list of the 10 entity labels, if possible.
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    if ($count == 0) {
      $form['total'] = [
        '#markup' => $this->t(
          'There are 0 @entity_type_plural to delete.',
          ['@entity_type_plural' => $entity_type->getPluralLabel()]
        ),
      ];
    }
    elseif ($accessible_count > 0 && $entity_type->hasKey('label')) {
      $recent_entity_ids = $storage->getQuery()
        ->accessCheck(TRUE)
        ->sort($entity_type->getKey('id'), 'DESC')
        ->pager(10)
        ->execute();
      $recent_entities = $storage->loadMultiple($recent_entity_ids);

      $labels = [];
      foreach ($recent_entities as $entity) {
        $labels[] = $entity->label();
      }

      if ($labels) {
        $form['recent_entity_labels'] = [
          '#theme' => 'item_list',
          '#items' => $labels,
        ];
        $more_count = $count - count($labels);
        $form['total'] = [
          '#markup' => $this->formatPlural(
            $more_count,
            'And <strong>@count</strong> more @entity_type_singular.',
            'And <strong>@count</strong> more @entity_type_plural.',
            [
              '@entity_type_singular' => $entity_type->getSingularLabel(),
              '@entity_type_plural' => $entity_type->getPluralLabel(),
            ]
          ),
          '#access' => (bool) $more_count,
        ];
      }
    }
    else {
      $form['total'] = [
        '#markup' => $this->formatPlural(
          $count,
          'This will delete <strong>@count</strong> @entity_type_singular.',
          'This will delete <strong>@count</strong> @entity_type_plural.',
          [
            '@entity_type_singular' => $entity_type->getSingularLabel(),
            '@entity_type_plural' => $entity_type->getPluralLabel(),
          ]
        ),
      ];
    }

    $form['description']['#prefix'] = '<p>';
    $form['description']['#suffix'] = '</p>';
    $form['description']['#weight'] = 5;

    // Only show the delete button if there are entities to delete.
    $form['actions']['submit']['#access'] = (bool) $count;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity_type_id = $form_state->getValue('entity_type_id');

    $entity_type_plural = $this->entityTypeManager->getDefinition($entity_type_id)->getPluralLabel();
    $batch_builder = (new BatchBuilder())
      ->setTitle($this->t('Deleting @entity_type_plural', ['@entity_type_plural' => $entity_type_plural]))
      ->setProgressMessage('')
      ->setFinishCallback([__CLASS__, 'moduleBatchFinished'])
      ->addOperation([__CLASS__, 'deleteContentEntities'], [$entity_type_id]);
    batch_set($batch_builder->toArray());
  }

  /**
   * Deletes the content entities of the specified entity type.
   *
   * @param string $entity_type_id
   *   The entity type ID from which data will be deleted.
   * @param array|\ArrayAccess $context
   *   The batch context array, passed by reference.
   *
   * @internal
   *   This batch callback is only meant to be used by this form.
   */
  public static function deleteContentEntities($entity_type_id, &$context) {
    $storage = \Drupal::entityTypeManager()->getStorage($entity_type_id);

    // Set the entity type ID in the results array so we can access it in the
    // batch finished callback.
    $context['results']['entity_type_id'] = $entity_type_id;

    if (!isset($context['sandbox']['progress'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['max'] = $storage->getQuery()->accessCheck(FALSE)->count()->execute();
    }

    $entity_type = \Drupal::entityTypeManager()->getDefinition($entity_type_id);
    $entity_ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->sort($entity_type->getKey('id'), 'ASC')
      ->range(0, 10)
      ->execute();
    if ($entities = $storage->loadMultiple($entity_ids)) {
      $storage->delete($entities);
    }
    // Sometimes deletes cause secondary deletes. For example, deleting a
    // taxonomy term can cause its children to be deleted too.
    $context['sandbox']['progress'] = $context['sandbox']['max'] - $storage->getQuery()->accessCheck(FALSE)->count()->execute();

    // Inform the batch engine that we are not finished and provide an
    // estimation of the completion level we reached.
    if (count($entity_ids) > 0 && $context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
      $context['message'] = new TranslatableMarkup('Deleting items... Completed @percentage% (@current of @total).', ['@percentage' => round(100 * $context['sandbox']['progress'] / $context['sandbox']['max']), '@current' => $context['sandbox']['progress'], '@total' => $context['sandbox']['max']]);

    }
    else {
      $context['finished'] = 1;
    }
  }

  /**
   * Implements callback_batch_finished().
   *
   * Finishes the module batch, redirect to the uninstall page and output the
   * successful data deletion message.
   */
  public static function moduleBatchFinished($success, $results, $operations) {
    $entity_type_plural = \Drupal::entityTypeManager()->getDefinition($results['entity_type_id'])->getPluralLabel();
    \Drupal::messenger()->addStatus(new TranslatableMarkup('All @entity_type_plural have been deleted.', ['@entity_type_plural' => $entity_type_plural]));

    return new RedirectResponse(Url::fromRoute('system.modules_uninstall')->setAbsolute()->toString());
  }

}

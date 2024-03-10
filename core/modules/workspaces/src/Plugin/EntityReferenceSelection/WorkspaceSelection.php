<?php

namespace Drupal\workspaces\Plugin\EntityReferenceSelection;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\Attribute\EntityReferenceSelection;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides specific access control for the workspace entity type.
 */
#[EntityReferenceSelection(
  id: "default:workspace",
  label: new TranslatableMarkup("Workspace selection"),
  entity_types: ["workspace"],
  group: "default",
  weight: 1
)]
class WorkspaceSelection extends DefaultSelection {

  /**
   * The workspace repository service.
   *
   * @var \Drupal\workspaces\WorkspaceRepositoryInterface
   */
  protected $workspaceRepository;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->workspaceRepository = $container->get('workspaces.repository');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'sort' => [
        'field' => 'label',
        'direction' => 'asc',
      ],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Sorting is not possible for workspaces because we always sort them by
    // depth and label.
    $form['sort']['#access'] = FALSE;

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    // Get all the workspace entities and sort them in tree order.
    $storage = $this->entityTypeManager->getStorage('workspace');
    $workspace_tree = $this->workspaceRepository->loadTree();
    $entities = array_replace($workspace_tree, $storage->loadMultiple());

    // If we need to restrict the list of workspaces by searching only a part of
    // their label ($match) or by a number of results ($limit), the workspace
    // tree would be mangled because it wouldn't contain all the tree items.
    if ($match || $limit) {
      $options = parent::getReferenceableEntities($match, $match_operator, $limit);
    }
    else {
      $options = [];
      foreach ($entities as $entity) {
        $options[$entity->bundle()][$entity->id()] = str_repeat('-', $workspace_tree[$entity->id()]['depth']) . Html::escape($this->entityRepository->getTranslationFromContext($entity)->label());
      }
    }

    $restricted_access_entities = [];
    foreach ($options as $bundle => $bundle_options) {
      foreach (array_keys($bundle_options) as $id) {
        // If a user can not view a workspace, we need to prevent them from
        // referencing that workspace as well as its descendants.
        if (in_array($id, $restricted_access_entities) || !$entities[$id]->access('view', $this->currentUser)) {
          $restricted_access_entities += $workspace_tree[$id]['descendants'];
          unset($options[$bundle][$id]);
        }
      }
    }

    return $options;
  }

}

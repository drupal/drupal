<?php

namespace Drupal\taxonomy\Entity;

use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\taxonomy\TermInterface;
use Drupal\user\StatusItem;

/**
 * Defines the taxonomy term entity.
 *
 * @ContentEntityType(
 *   id = "taxonomy_term",
 *   label = @Translation("Taxonomy term"),
 *   label_collection = @Translation("Taxonomy terms"),
 *   label_singular = @Translation("taxonomy term"),
 *   label_plural = @Translation("taxonomy terms"),
 *   label_count = @PluralTranslation(
 *     singular = "@count taxonomy term",
 *     plural = "@count taxonomy terms",
 *   ),
 *   bundle_label = @Translation("Vocabulary"),
 *   handlers = {
 *     "storage" = "Drupal\taxonomy\TermStorage",
 *     "storage_schema" = "Drupal\taxonomy\TermStorageSchema",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "access" = "Drupal\taxonomy\TermAccessControlHandler",
 *     "views_data" = "Drupal\taxonomy\TermViewsData",
 *     "form" = {
 *       "default" = "Drupal\taxonomy\TermForm",
 *       "delete" = "Drupal\taxonomy\Form\TermDeleteForm",
 *       "revision-delete" = \Drupal\Core\Entity\Form\RevisionDeleteForm::class,
 *       "revision-revert" = \Drupal\Core\Entity\Form\RevisionRevertForm::class,
 *     },
 *     "route_provider" = {
 *       "revision" = \Drupal\Core\Entity\Routing\RevisionHtmlRouteProvider::class,
 *     },
 *     "translation" = "Drupal\taxonomy\TermTranslationHandler"
 *   },
 *   base_table = "taxonomy_term_data",
 *   data_table = "taxonomy_term_field_data",
 *   revision_table = "taxonomy_term_revision",
 *   revision_data_table = "taxonomy_term_field_revision",
 *   show_revision_ui = TRUE,
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "tid",
 *     "revision" = "revision_id",
 *     "bundle" = "vid",
 *     "label" = "name",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid",
 *     "published" = "status",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_user",
 *     "revision_created" = "revision_created",
 *     "revision_log_message" = "revision_log_message",
 *   },
 *   bundle_entity_type = "taxonomy_vocabulary",
 *   field_ui_base_route = "entity.taxonomy_vocabulary.overview_form",
 *   common_reference_target = TRUE,
 *   links = {
 *     "canonical" = "/taxonomy/term/{taxonomy_term}",
 *     "delete-form" = "/taxonomy/term/{taxonomy_term}/delete",
 *     "edit-form" = "/taxonomy/term/{taxonomy_term}/edit",
 *     "create" = "/taxonomy/term",
 *     "revision" = "/taxonomy/term/{taxonomy_term}/revision/{taxonomy_term_revision}/view",
 *     "revision-delete-form" = "/taxonomy/term/{taxonomy_term}/revision/{taxonomy_term_revision}/delete",
 *     "revision-revert-form" = "/taxonomy/term/{taxonomy_term}/revision/{taxonomy_term_revision}/revert",
 *     "version-history" = "/taxonomy/term/{taxonomy_term}/revisions",
 *   },
 *   permission_granularity = "bundle",
 *   collection_permission = "access taxonomy overview",
 *   constraints = {
 *     "TaxonomyHierarchy" = {}
 *   }
 * )
 */
class Term extends EditorialContentEntityBase implements TermInterface {

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    // See if any of the term's children are about to be become orphans.
    $orphans = [];
    /** @var \Drupal\taxonomy\TermInterface $term */
    foreach ($entities as $tid => $term) {
      if ($children = $storage->getChildren($term)) {
        /** @var \Drupal\taxonomy\TermInterface $child */
        foreach ($children as $child) {
          $parent = $child->get('parent');
          // Update child parents item list.
          $parent->filter(function ($item) use ($tid) {
            return $item->target_id != $tid;
          });

          // If the term has multiple parents, we don't delete it.
          if ($parent->count()) {
            $child->save();
          }
          else {
            $orphans[] = $child;
          }
        }
      }
    }

    if (!empty($orphans)) {
      $storage->delete($orphans);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    // Terms with no parents are mandatory children of <root>.
    if (!$this->get('parent')->count()) {
      $this->parent->target_id = 0;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    /** @var \Drupal\Core\Field\BaseFieldDefinition[] $fields */
    $fields = parent::baseFieldDefinitions($entity_type);

    // @todo Remove the usage of StatusItem in
    //   https://www.drupal.org/project/drupal/issues/2936864.
    $fields['status']->getItemDefinition()->setClass(StatusItem::class);

    $fields['tid']->setLabel(t('Term ID'))
      ->setDescription(t('The term ID.'));

    $fields['uuid']->setDescription(t('The term UUID.'));

    $fields['status']
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => TRUE,
        ],
        'weight' => 100,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['vid']->setLabel(t('Vocabulary'))
      ->setDescription(t('The vocabulary to which the term is assigned.'));

    $fields['langcode']->setDescription(t('The term language code.'));

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Description'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'text_default',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'text_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Weight'))
      ->setDescription(t('The weight of this term in relation to other terms.'))
      ->setDefaultValue(0);

    $fields['parent'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Term Parents'))
      ->setDescription(t('The parents of this term.'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the term was last edited.'))
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    // Only terms in the same bundle can be a parent.
    $fields['parent'] = clone $base_field_definitions['parent'];
    $fields['parent']->setSetting('handler_settings', ['target_bundles' => [$bundle => $bundle]]);
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->get('description')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->set('description', $description);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormat() {
    return $this->get('description')->format;
  }

  /**
   * {@inheritdoc}
   */
  public function setFormat($format) {
    $this->get('description')->format = $format;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->label() ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return (int) $this->get('weight')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->set('weight', $weight);
    return $this;
  }

}

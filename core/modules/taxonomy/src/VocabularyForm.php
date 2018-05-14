<?php

namespace Drupal\taxonomy;

use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\language\Entity\ContentLanguageSettings;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base form for vocabulary edit forms.
 *
 * @internal
 */
class VocabularyForm extends BundleEntityFormBase {

  /**
   * The vocabulary storage.
   *
   * @var \Drupal\taxonomy\VocabularyStorageInterface
   */
  protected $vocabularyStorage;

  /**
   * Constructs a new vocabulary form.
   *
   * @param \Drupal\taxonomy\VocabularyStorageInterface $vocabulary_storage
   *   The vocabulary storage.
   */
  public function __construct(VocabularyStorageInterface $vocabulary_storage) {
    $this->vocabularyStorage = $vocabulary_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('taxonomy_vocabulary')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $vocabulary = $this->entity;
    if ($vocabulary->isNew()) {
      $form['#title'] = $this->t('Add vocabulary');
    }
    else {
      $form['#title'] = $this->t('Edit vocabulary');
    }

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#default_value' => $vocabulary->label(),
      '#maxlength' => 255,
      '#required' => TRUE,
    ];
    $form['vid'] = [
      '#type' => 'machine_name',
      '#default_value' => $vocabulary->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#machine_name' => [
        'exists' => [$this, 'exists'],
        'source' => ['name'],
      ],
    ];
    $form['description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#default_value' => $vocabulary->getDescription(),
    ];

    // $form['langcode'] is not wrapped in an
    // if ($this->moduleHandler->moduleExists('language')) check because the
    // language_select form element works also without the language module being
    // installed. https://www.drupal.org/node/1749954 documents the new element.
    $form['langcode'] = [
      '#type' => 'language_select',
      '#title' => $this->t('Vocabulary language'),
      '#languages' => LanguageInterface::STATE_ALL,
      '#default_value' => $vocabulary->language()->getId(),
    ];
    if ($this->moduleHandler->moduleExists('language')) {
      $form['default_terms_language'] = [
        '#type' => 'details',
        '#title' => $this->t('Term language'),
        '#open' => TRUE,
      ];
      $form['default_terms_language']['default_language'] = [
        '#type' => 'language_configuration',
        '#entity_information' => [
          'entity_type' => 'taxonomy_term',
          'bundle' => $vocabulary->id(),
        ],
        '#default_value' => ContentLanguageSettings::loadByEntityTypeBundle('taxonomy_term', $vocabulary->id()),
      ];
    }
    // Set the hierarchy to "multiple parents" by default. This simplifies the
    // vocabulary form and standardizes the term form.
    $form['hierarchy'] = [
      '#type' => 'value',
      '#value' => '0',
    ];

    $form = parent::form($form, $form_state);
    return $this->protectBundleIdElement($form);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $vocabulary = $this->entity;

    // Prevent leading and trailing spaces in vocabulary names.
    $vocabulary->set('name', trim($vocabulary->label()));

    $status = $vocabulary->save();
    $edit_link = $this->entity->link($this->t('Edit'));
    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('Created new vocabulary %name.', ['%name' => $vocabulary->label()]));
        $this->logger('taxonomy')->notice('Created new vocabulary %name.', ['%name' => $vocabulary->label(), 'link' => $edit_link]);
        $form_state->setRedirectUrl($vocabulary->urlInfo('overview-form'));
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('Updated vocabulary %name.', ['%name' => $vocabulary->label()]));
        $this->logger('taxonomy')->notice('Updated vocabulary %name.', ['%name' => $vocabulary->label(), 'link' => $edit_link]);
        $form_state->setRedirectUrl($vocabulary->urlInfo('collection'));
        break;
    }

    $form_state->setValue('vid', $vocabulary->id());
    $form_state->set('vid', $vocabulary->id());
  }

  /**
   * Determines if the vocabulary already exists.
   *
   * @param string $vid
   *   The vocabulary ID.
   *
   * @return bool
   *   TRUE if the vocabulary exists, FALSE otherwise.
   */
  public function exists($vid) {
    $action = $this->vocabularyStorage->load($vid);
    return !empty($action);
  }

}

<?php

/**
 * @file
 * Contains \Drupal\language\Form\LanguageDeleteForm.
 */

namespace Drupal\language\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Url;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Defines a confirmation form for deleting a language entity.
 */
class LanguageDeleteForm extends EntityConfirmFormBase {

  /**
   * The urlGenerator service.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a new LanguageDeleteForm object.
   *
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The url generator service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(UrlGeneratorInterface $url_generator, LanguageManagerInterface $language_manager) {
    $this->urlGenerator = $url_generator;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('url_generator'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the language %language?', array('%language' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('language.admin_overview');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Deleting a language will remove all interface translations associated with it, and content in this language will be set to be language neutral. This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'language_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $langcode = $this->entity->id();

    // Warn and redirect user when attempting to delete the default language.
    if (language_default()->getId() == $langcode) {
      drupal_set_message($this->t('The default language cannot be deleted.'));
      $url = $this->urlGenerator->generateFromPath('admin/config/regional/language', array('absolute' => TRUE));
      return new RedirectResponse($url);
    }

    // Throw a 404 when attempting to delete a non-existing language.
    $languages = $this->languageManager->getLanguages();
    if (!isset($languages[$langcode])) {
      throw new NotFoundHttpException();
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();
    $t_args = array('%language' => $this->entity->label(), '%langcode' => $this->entity->id());
    $this->logger('language')->notice('The %language (%langcode) language has been removed.', $t_args);

    drupal_set_message($this->t('The %language (%langcode) language has been removed.', $t_args));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}

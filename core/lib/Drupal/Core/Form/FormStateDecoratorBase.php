<?php

namespace Drupal\Core\Form;

use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Response;

/**
 * Decorates another form state.
 */
abstract class FormStateDecoratorBase implements FormStateInterface {

  /**
   * The decorated form state.
   *
   * @var \Drupal\Core\Form\FormStateInterface
   */
  protected $decoratedFormState;

  /**
   * {@inheritdoc}
   */
  public function setFormState(array $form_state_additions) {
    $this->decoratedFormState->setFormState($form_state_additions);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setAlwaysProcess($always_process = TRUE) {
    $this->decoratedFormState->setAlwaysProcess($always_process);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAlwaysProcess() {
    return $this->decoratedFormState->getAlwaysProcess();
  }

  /**
   * {@inheritdoc}
   */
  public function setButtons(array $buttons) {
    $this->decoratedFormState->setButtons($buttons);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return $this->decoratedFormState->getButtons();
  }

  /**
   * {@inheritdoc}
   */
  public function setCached($cache = TRUE) {
    $this->decoratedFormState->setCached($cache);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isCached() {
    return $this->decoratedFormState->isCached();
  }

  /**
   * {@inheritdoc}
   */
  public function disableCache() {
    $this->decoratedFormState->disableCache();

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setExecuted() {
    $this->decoratedFormState->setExecuted();

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isExecuted() {
    return $this->decoratedFormState->isExecuted();
  }

  /**
   * {@inheritdoc}
   */
  public function setGroups(array $groups) {
    $this->decoratedFormState->setGroups($groups);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function &getGroups() {
    return $this->decoratedFormState->getGroups();
  }

  /**
   * {@inheritdoc}
   */
  public function setHasFileElement($has_file_element = TRUE) {
    $this->decoratedFormState->setHasFileElement($has_file_element);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasFileElement() {
    return $this->decoratedFormState->hasFileElement();
  }

  /**
   * {@inheritdoc}
   */
  public function setLimitValidationErrors($limit_validation_errors) {
    $this->decoratedFormState->setLimitValidationErrors($limit_validation_errors);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLimitValidationErrors() {
    return $this->decoratedFormState->getLimitValidationErrors();
  }

  /**
   * {@inheritdoc}
   */
  public function setMethod($method) {
    $this->decoratedFormState->setMethod($method);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isMethodType($method_type) {
    return $this->decoratedFormState->isMethodType($method_type);
  }

  /**
   * {@inheritdoc}
   */
  public function setRequestMethod($method) {
    $this->decoratedFormState->setRequestMethod($method);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setValidationEnforced($must_validate = TRUE) {
    $this->decoratedFormState->setValidationEnforced($must_validate);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isValidationEnforced() {
    return $this->decoratedFormState->isValidationEnforced();
  }

  /**
   * {@inheritdoc}
   */
  public function disableRedirect($no_redirect = TRUE) {
    $this->decoratedFormState->disableRedirect($no_redirect);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isRedirectDisabled() {
    return $this->decoratedFormState->isRedirectDisabled();
  }

  /**
   * {@inheritdoc}
   */
  public function setProcessInput($process_input = TRUE) {
    $this->decoratedFormState->setProcessInput($process_input);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isProcessingInput() {
    return $this->decoratedFormState->isProcessingInput();
  }

  /**
   * {@inheritdoc}
   */
  public function setProgrammed($programmed = TRUE) {
    $this->decoratedFormState->setProgrammed($programmed);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isProgrammed() {
    return $this->decoratedFormState->isProgrammed();
  }

  /**
   * {@inheritdoc}
   */
  public function setProgrammedBypassAccessCheck($programmed_bypass_access_check = TRUE) {
    $this->decoratedFormState->setProgrammedBypassAccessCheck($programmed_bypass_access_check);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isBypassingProgrammedAccessChecks() {
    return $this->decoratedFormState->isBypassingProgrammedAccessChecks();
  }

  /**
   * {@inheritdoc}
   */
  public function setRebuildInfo(array $rebuild_info) {
    $this->decoratedFormState->setRebuildInfo($rebuild_info);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRebuildInfo() {
    return $this->decoratedFormState->getRebuildInfo();
  }

  /**
   * {@inheritdoc}
   */
  public function addRebuildInfo($property, $value) {
    $this->decoratedFormState->addRebuildInfo($property, $value);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setStorage(array $storage) {
    $this->decoratedFormState->setStorage($storage);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function &getStorage() {
    return $this->decoratedFormState->getStorage();
  }

  /**
   * {@inheritdoc}
   */
  public function setSubmitHandlers(array $submit_handlers) {
    $this->decoratedFormState->setSubmitHandlers($submit_handlers);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubmitHandlers() {
    return $this->decoratedFormState->getSubmitHandlers();
  }

  /**
   * {@inheritdoc}
   */
  public function setSubmitted() {
    $this->decoratedFormState->setSubmitted();

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isSubmitted() {
    return $this->decoratedFormState->isSubmitted();
  }

  /**
   * {@inheritdoc}
   */
  public function setTemporary(array $temporary) {
    $this->decoratedFormState->setTemporary($temporary);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTemporary() {
    return $this->decoratedFormState->getTemporary();
  }

  /**
   * {@inheritdoc}
   */
  public function &getTemporaryValue($key) {
    return $this->decoratedFormState->getTemporaryValue($key);
  }

  /**
   * {@inheritdoc}
   */
  public function setTemporaryValue($key, $value) {
    $this->decoratedFormState->setTemporaryValue($key, $value);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasTemporaryValue($key) {
    return $this->decoratedFormState->hasTemporaryValue($key);
  }

  /**
   * {@inheritdoc}
   */
  public function setTriggeringElement($triggering_element) {
    $this->decoratedFormState->setTriggeringElement($triggering_element);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function &getTriggeringElement() {
    return $this->decoratedFormState->getTriggeringElement();
  }

  /**
   * {@inheritdoc}
   */
  public function setValidateHandlers(array $validate_handlers) {
    $this->decoratedFormState->setValidateHandlers($validate_handlers);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getValidateHandlers() {
    return $this->decoratedFormState->getValidateHandlers();
  }

  /**
   * {@inheritdoc}
   */
  public function setValidationComplete($validation_complete = TRUE) {
    $this->decoratedFormState->setValidationComplete($validation_complete);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isValidationComplete() {
    return $this->decoratedFormState->isValidationComplete();
  }

  /**
   * {@inheritdoc}
   */
  public function loadInclude($module, $type, $name = NULL) {
    return $this->decoratedFormState->loadInclude($module, $type, $name);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableArray() {
    return $this->decoratedFormState->getCacheableArray();
  }

  /**
   * {@inheritdoc}
   */
  public function setCompleteForm(array &$complete_form) {
    $this->decoratedFormState->setCompleteForm($complete_form);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function &getCompleteForm() {
    return $this->decoratedFormState->getCompleteForm();
  }

  /**
   * {@inheritdoc}
   */
  public function &get($property) {
    return $this->decoratedFormState->get($property);
  }

  /**
   * {@inheritdoc}
   */
  public function set($property, $value) {
    $this->decoratedFormState->set($property, $value);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function has($property) {
    return $this->decoratedFormState->has($property);
  }

  /**
   * {@inheritdoc}
   */
  public function setBuildInfo(array $build_info) {
    $this->decoratedFormState->setBuildInfo($build_info);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBuildInfo() {
    return $this->decoratedFormState->getBuildInfo();
  }

  /**
   * {@inheritdoc}
   */
  public function addBuildInfo($property, $value) {
    $this->decoratedFormState->addBuildInfo($property, $value);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function &getUserInput() {
    return $this->decoratedFormState->getUserInput();
  }

  /**
   * {@inheritdoc}
   */
  public function setUserInput(array $user_input) {
    $this->decoratedFormState->setUserInput($user_input);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function &getValues() {
    return $this->decoratedFormState->getValues();
  }

  /**
   * {@inheritdoc}
   */
  public function &getValue($key, $default = NULL) {
    return $this->decoratedFormState->getValue($key, $default);
  }

  /**
   * {@inheritdoc}
   */
  public function setValues(array $values) {
    $this->decoratedFormState->setValues($values);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($key, $value) {
    $this->decoratedFormState->setValue($key, $value);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function unsetValue($key) {
    $this->decoratedFormState->unsetValue($key);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasValue($key) {
    return $this->decoratedFormState->hasValue($key);
  }

  /**
   * {@inheritdoc}
   */
  public function isValueEmpty($key) {
    return $this->decoratedFormState->isValueEmpty($key);
  }

  /**
   * {@inheritdoc}
   */
  public function setValueForElement(array $element, $value) {
    $this->decoratedFormState->setValueForElement($element, $value);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setResponse(Response $response) {
    $this->decoratedFormState->setResponse($response);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getResponse() {
    return $this->decoratedFormState->getResponse();
  }

  /**
   * {@inheritdoc}
   */
  public function setRedirect($route_name, array $route_parameters = [], array $options = []) {
    $this->decoratedFormState->setRedirect($route_name, $route_parameters, $options);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setRedirectUrl(Url $url) {
    $this->decoratedFormState->setRedirectUrl($url);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRedirect() {
    return $this->decoratedFormState->getRedirect();
  }

  /**
   * {@inheritdoc}
   */
  public static function hasAnyErrors() {
    return FormState::hasAnyErrors();
  }

  /**
   * {@inheritdoc}
   */
  public function setErrorByName($name, $message = '') {
    $this->decoratedFormState->setErrorByName($name, $message);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setError(array &$element, $message = '') {
    $this->decoratedFormState->setError($element, $message);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function clearErrors() {
    $this->decoratedFormState->clearErrors();
  }

  /**
   * {@inheritdoc}
   */
  public function getError(array $element) {
    return $this->decoratedFormState->getError($element);
  }

  /**
   * {@inheritdoc}
   */
  public function getErrors() {
    return $this->decoratedFormState->getErrors();
  }

  /**
   * {@inheritdoc}
   */
  public function setRebuild($rebuild = TRUE) {
    $this->decoratedFormState->setRebuild($rebuild);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isRebuilding() {
    return $this->decoratedFormState->isRebuilding();
  }

  /**
   * {@inheritdoc}
   */
  public function setInvalidToken($invalid_token) {
    $this->decoratedFormState->setInvalidToken($invalid_token);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasInvalidToken() {
    return $this->decoratedFormState->hasInvalidToken();
  }

  /**
   * {@inheritdoc}
   */
  public function prepareCallback($callback) {
    return $this->decoratedFormState->prepareCallback($callback);
  }

  /**
   * {@inheritdoc}
   */
  public function setFormObject(FormInterface $form_object) {
    $this->decoratedFormState->setFormObject($form_object);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormObject() {
    return $this->decoratedFormState->getFormObject();
  }

  /**
   * {@inheritdoc}
   */
  public function getCleanValueKeys() {
    return $this->decoratedFormState->getCleanValueKeys();
  }

  /**
   * {@inheritdoc}
   */
  public function setCleanValueKeys(array $cleanValueKeys) {
    $this->decoratedFormState->setCleanValueKeys($cleanValueKeys);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addCleanValueKey($cleanValueKey) {
    $this->decoratedFormState->addCleanValueKey($cleanValueKey);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function cleanValues() {
    $this->decoratedFormState->cleanValues();

    return $this;
  }

}

<?php

namespace Drupal\Core\Validation;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Translates strings using Drupal's translation system.
 *
 * This class is used by the Symfony validator to translate violation messages.
 */
class DrupalTranslator implements TranslatorInterface {

  /**
   * The locale used for translating.
   *
   * @var string
   */
  protected $locale;

  /**
   * {@inheritdoc}
   */
  public function trans($id, array $parameters = [], $domain = NULL, $locale = NULL) {
    // If a TranslatableMarkup object is passed in as $id, return it since the
    // message has already been translated.
    if ($id instanceof TranslatableMarkup) {
      return $id;
    }

    // Symfony violation messages may separate singular and plural versions
    // with "|".
    $ids = explode('|', $id);
    if ((count($ids) > 1) && isset($parameters['%count%'])) {
      return \Drupal::translation()->formatPlural($parameters['%count%'], $ids[0], $ids[1], $this->processParameters($parameters), $this->getOptions($domain, $locale));
    }

    return new TranslatableMarkup($id, $this->processParameters($parameters), $this->getOptions($domain, $locale));
  }

  /**
   * {@inheritdoc}
   */
  public function transChoice($id, $number, array $parameters = [], $domain = NULL, $locale = NULL) {
    // Violation messages can separated singular and plural versions by "|".
    $ids = explode('|', $id);

    if (!isset($ids[1])) {
      throw new \InvalidArgumentException(sprintf('The message "%s" cannot be pluralized, because it is missing a plural (e.g. "There is one apple|There are @count apples").', $id));
    }

    // Normally, calls to formatPlural() need to use literal strings, like
    // formatPlural($count, '1 item', '@count items')
    // so that the Drupal project POTX string extractor will correctly
    // extract the strings for translation and save them in a format that
    // formatPlural() can work with. However, this is a special case, because
    // Drupal is supporting a constraint message format from Symfony. So
    // although $id looks like a variable here, it is actually coming from a
    // static string in a constraint class that the POTX extractor knows about
    // and has processed to work with formatPlural(), so this specific call to
    // formatPlural() will work correctly.
    return \Drupal::translation()->formatPlural($number, $ids[0], $ids[1], $this->processParameters($parameters), $this->getOptions($domain, $locale));
  }

  /**
   * {@inheritdoc}
   */
  public function setLocale($locale) {
    $this->locale = $locale;
  }

  /**
   * {@inheritdoc}
   */
  public function getLocale() {
    return $this->locale ? $this->locale : \Drupal::languageManager()->getCurrentLanguage()->getId();
  }

  /**
   * Processes the parameters array for use with TranslatableMarkup.
   */
  protected function processParameters(array $parameters) {
    $return = [];
    foreach ($parameters as $key => $value) {
      // We allow the values in the parameters to be safe string objects. This
      // can be useful when we want to use parameter values that are
      // TranslatableMarkup.
      if ($value instanceof MarkupInterface) {
        $value = (string) $value;
      }
      if (is_object($value)) {
        // TranslatableMarkup does not work with objects being passed as
        // replacement strings.
      }
      // Check for symfony replacement patterns in the form "{{ name }}".
      elseif (strpos($key, '{{ ') === 0 && strrpos($key, ' }}') == strlen($key) - 3) {
        // Transform it into a Drupal pattern using the format %name.
        $key = '%' . substr($key, 3, strlen($key) - 6);
        $return[$key] = $value;
      }
      else {
        $return[$key] = $value;
      }
    }
    return $return;
  }

  /**
   * Returns options suitable for use with TranslatableMarkup.
   */
  protected function getOptions($domain = NULL, $locale = NULL) {
    // We do not support domains, so we ignore this parameter.
    // If locale is left NULL, TranslatableMarkup will default to the interface
    // language.
    $locale = $locale ?? $this->locale;
    return ['langcode' => $locale];
  }

}

<?php

/**
 * @file
 * Contains \Drupal\text\Tests\Formatter\TextPlainUnitTest.
 */

namespace Drupal\text\Tests\Formatter;

use Drupal\Component\Utility\String;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Tests the text_plain field formatter.
 *
 * @todo Move assertion helper methods into KernelTestBase.
 * @todo Move field helper methods, $modules, and setUp() into a new
 *   FieldPluginUnitTestBase.
 */
class TextPlainUnitTest extends DrupalUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('entity', 'field', 'text', 'entity_test', 'system', 'filter', 'user');

  /**
   * Contains rendered content.
   *
   * @var string
   */
  protected $content;

  public static function getInfo() {
    return array(
      'name'  => 'Text field text_plain formatter',
      'description'  => "Test the creation of text fields.",
      'group' => 'Field types',
    );
  }

  function setUp() {
    parent::setUp();

    // Configure the theme system.
    $this->installConfig(array('system', 'field'));
    $this->installEntitySchema('entity_test');

    // @todo Add helper methods for all of the following.

    $this->entity_type = 'entity_test';
    if (!isset($this->bundle)) {
      $this->bundle = $this->entity_type;
    }

    $this->field_name = drupal_strtolower($this->randomName());
    $this->field_type = 'text_long';
    $this->field_settings = array();
    $this->instance_settings = array(
      'text_processing' => FALSE,
    );

    $this->formatter_type = 'string';
    $this->formatter_settings = array();

    $this->field = entity_create('field_config', array(
      'name' => $this->field_name,
      'entity_type' => $this->entity_type,
      'type' => $this->field_type,
      'settings' => $this->field_settings,
    ));
    $this->field->save();

    $this->instance = entity_create('field_instance_config', array(
      'field' => $this->field,
      'bundle' => $this->bundle,
      'label' => $this->randomName(),
      'settings' => $this->instance_settings,
    ));
    $this->instance->save();

    $this->view_mode = 'default';
    $this->display = entity_get_display($this->entity_type, $this->bundle, $this->view_mode)
      ->setComponent($this->field_name, array(
        'type' => $this->formatter_type,
        'settings' => $this->formatter_settings,
      ));
    $this->display->save();

    $this->langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED;
  }

  /**
   * Creates an entity of type $this->entity_type and bundle $this->bundle.
   *
   * @param array $values
   *   (optional) Additional values to pass to entity_create().
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The created entity object.
   */
  protected function createEntity($values = array()) {
    $entity_type = \Drupal::entityManager()->getDefinition($this->entity_type);
    $bundle_key = $entity_type->getKey('bundle');
    $entity = entity_create($this->entity_type, $values + array(
      $bundle_key => $this->bundle,
    ));
    return $entity;
  }

  /**
   * Renders fields of a given entity with a given display.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity object with attached fields to render.
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
   *   The display to render the fields in.
   */
  protected function renderEntityFields(ContentEntityInterface $entity, EntityViewDisplayInterface $display) {
    $content = $display->build($entity);
    $this->content = drupal_render($content);
    return $this->content;
  }

  /**
   * Formats an assertion message string.
   *
   * Unlike format_string(),
   * - all replacement tokens are exported via var_export() and sanitized for
   *   output, regardless of token type used (i.e., '@', '!', and '%' do not
   *   have any special meaning).
   * - Replacement token values containing newlines are automatically wrapped
   *   into a PRE element to aid in debugging test failures.
   *
   * @param string $message
   *   The assertion message string containing placeholders.
   * @param array $args
   *   An array of replacement token values to inject into $message.
   *
   * @return string
   *   The $message with exported replacement tokens, sanitized for HTML output.
   *
   * @see \Drupal\Component\Utility\String::checkPlain()
   * @see format_string()
   */
  protected function formatString($message, array $args) {
    array_walk($args, function (&$value) {
      // Export/dump the raw replacement token value.
      $value = var_export($value, TRUE);
      // Sanitize the value for output.
      $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
      // Wrap the value in a PRE element if it contains newlines.
      if (strpos($value, "\n")) {
        $value = '<pre style="white-space: pre-wrap;">' . $value . '</pre>';
      }
    });
    return strtr($message, $args);
  }

  /**
   * Gets the plain-text version of $this->content.
   *
   * @param string $content
   *   A (HTML) string.
   *
   * @return string
   *   The $content with all HTML tags stripped and all HTML entities replaced
   *   with their actual characters.
   */
  protected function getPlainTextContent($content) {
    // Strip all HTML tags.
    $content = strip_tags($content);
    // Decode all HTML entities (e.g., '&nbsp;') into characters.
    $content = html_entity_decode($content, ENT_QUOTES, 'UTF-8');
    return $content;
  }

  /**
   * Asserts that a raw string appears in $this->content.
   *
   * @param string $value
   *   The raw string to look for.
   * @param string $message
   *   (optional) An assertion message. If omitted, a default message is used.
   *   Available placeholders:
   *   - @value: The $value.
   *   - @content: The current value of $this->content.
   * @param array $args
   *   (optional) Additional replacement token map to pass to formatString().
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertRaw($value, $message = NULL, $args = array()) {
    if (!isset($message)) {
      $message = 'Raw string @value found in @content';
    }
    $args += array(
      '@value' => $value,
      '@content' => $this->content,
    );
    return $this->assert(strpos($this->content, $value) !== FALSE, $this->formatString($message, $args));
  }

  /**
   * Asserts that a raw string does not appear in $this->content.
   *
   * @param string $value
   *   The raw string to look for.
   * @param string $message
   *   (optional) An assertion message. If omitted, a default message is used.
   *   Available placeholders:
   *   - @value: The $value.
   *   - @content: The current value of $this->content.
   * @param array $args
   *   (optional) Additional replacement token map to pass to formatString().
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertNoRaw($value, $message = NULL, $args = array()) {
    if (!isset($message)) {
      $message = 'Raw string @value not found in @content';
    }
    $args += array(
      '@value' => $value,
      '@content' => $this->content,
    );
    return $this->assert(strpos($this->content, $value) === FALSE, $this->formatString($message, $args));
  }

  /**
   * Asserts that a text string appears in the text-only version of $this->content.
   *
   * @param string $value
   *   The text string to look for.
   * @param string $message
   *   (optional) An assertion message. If omitted, a default message is used.
   *   Available placeholders:
   *   - @value: The $value.
   *   - @content: The current value of $this->content.
   * @param array $args
   *   (optional) Additional replacement token map to pass to formatString().
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertText($value, $message = NULL, $args = array()) {
    if (!isset($message)) {
      $message = 'Text string @value found in @content';
    }
    $content = $this->getPlainTextContent($this->content);
    $args += array(
      '@value' => $value,
      '@content' => $content,
    );
    return $this->assert(strpos($content, $value) !== FALSE, $this->formatString($message, $args));
  }

  /**
   * Asserts that a text string does not appear in the text-only version of $this->content.
   *
   * @param string $value
   *   The text string to look for.
   * @param string $message
   *   (optional) An assertion message. If omitted, a default message is used.
   *   Available placeholders:
   *   - @value: The $value.
   *   - @content: The current value of $this->content.
   * @param array $args
   *   (optional) Additional replacement token map to pass to formatString().
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertNoText($value, $message = NULL, $args = array()) {
    if (!isset($message)) {
      $message = 'Text string @value not found in @content';
    }
    $content = $this->getPlainTextContent($this->content);
    $args += array(
      '@value' => $value,
      '@content' => $content,
    );
    return $this->assert(strpos($content, $value) === FALSE, $this->formatString($message, $args));
  }

  /**
   * Tests text_plain formatter output.
   */
  function testPlainText() {
    $value = $this->randomString();
    $value .= "\n\n<strong>" . $this->randomString() . '</strong>';
    $value .= "\n\n" . $this->randomString();

    $entity = $this->createEntity(array());
    $entity->{$this->field_name}->value = $value;

    // Verify that all HTML is escaped and newlines are retained.
    $this->renderEntityFields($entity, $this->display);
    $this->assertText($value);
    $this->assertNoRaw($value);
    $this->assertRaw(nl2br(String::checkPlain($value)));
  }

}

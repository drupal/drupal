<?php

namespace Drupal\user\Plugin\migrate\source;

use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;
use Drupal\migrate\Row;

/**
 * Profile field source from database.
 *
 * @MigrateSource(
 *   id = "profile_field",
 *   source_module = "profile"
 * )
 */
class ProfileField extends DrupalSqlBase {

  /**
   * The source table containing profile field info.
   *
   * @var string
   */
  protected $fieldTable;

  /**
   * The source table containing the profile values.
   *
   * @var string
   */
  protected $valueTable;

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->setTableNames();
    return $this->select($this->fieldTable, 'pf')->fields('pf');
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    if ($row->getSourceProperty('type') == 'selection') {
      // Get the current options.
      $current_options = preg_split("/[\r\n]+/", $row->getSourceProperty('options'));
      // Select the list values from the profile_values table to ensure we get
      // them all since they can get out of sync with profile_fields.
      $options = $this->select($this->valueTable, 'pv')
        ->distinct()
        ->fields('pv', ['value'])
        ->condition('fid', $row->getSourceProperty('fid'))
        ->execute()
        ->fetchCol();
      $options = array_merge($current_options, $options);
      // array_combine() takes care of any duplicates options.
      $row->setSourceProperty('options', array_combine($options, $options));
    }

    if ($row->getSourceProperty('type') == 'checkbox') {
      // D6 profile checkboxes values are always 0 or 1 (with no labels), so we
      // need to create two label-less options that will get 0 and 1 for their
      // keys.
      $row->setSourceProperty('options', [NULL, NULL]);
    }

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'fid' => $this->t('Primary Key: Unique profile field ID.'),
      'title' => $this->t('Title of the field shown to the end user.'),
      'name' => $this->t('Internal name of the field used in the form HTML and URLs.'),
      'explanation' => $this->t('Explanation of the field to end users.'),
      'category' => $this->t('Profile category that the field will be grouped under.'),
      'page' => $this->t("Title of page used for browsing by the field's value"),
      'type' => $this->t('Type of form field.'),
      'weight' => $this->t('Weight of field in relation to other profile fields.'),
      'required' => $this->t('Whether the user is required to enter a value. (0 = no, 1 = yes)'),
      'register' => $this->t('Whether the field is visible in the user registration form. (1 = yes, 0 = no)'),
      'visibility' => $this->t('The level of visibility for the field. (0 = hidden, 1 = private, 2 = public on profile but not member list pages, 3 = public on profile and list pages)'),
      'autocomplete' => $this->t('Whether form auto-completion is enabled. (0 = disabled, 1 = enabled)'),
      'options' => $this->t('List of options to be used in a list selection field.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['fid']['type'] = 'integer';
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function checkRequirements() {
    $this->setTableNames();
    if (!$this->getDatabase()->schema()->tableExists($this->fieldTable)) {
      // If we make it to here, the profile module isn't installed.
      throw new RequirementsException('Profile module not enabled on source site');
    }
    parent::checkRequirements();
  }

  /**
   * Helper to set the profile field table names.
   */
  protected function setTableNames() {
    if (empty($this->fieldTable) || empty($this->valueTable)) {
      if ($this->getModuleSchemaVersion('system') >= 7000) {
        $this->fieldTable = 'profile_field';
        $this->valueTable = 'profile_value';
      }
      else {
        $this->fieldTable = 'profile_fields';
        $this->valueTable = 'profile_values';
      }
    }
  }

}

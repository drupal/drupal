<?php

namespace Drupal\user\Plugin\migrate\source\d7;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\d7\FieldableEntity;

/**
 * Drupal 7 user source from database.
 *
 * @MigrateSource(
 *   id = "d7_user"
 * )
 */
class User extends FieldableEntity {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('users', 'u')
      ->fields('u')
      ->condition('u.uid', 0, '>');
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'uid' => $this->t('User ID'),
      'name' => $this->t('Username'),
      'pass' => $this->t('Password'),
      'mail' => $this->t('Email address'),
      'signature' => $this->t('Signature'),
      'signature_format' => $this->t('Signature format'),
      'created' => $this->t('Registered timestamp'),
      'access' => $this->t('Last access timestamp'),
      'login' => $this->t('Last login timestamp'),
      'status' => $this->t('Status'),
      'timezone' => $this->t('Timezone'),
      'language' => $this->t('Language'),
      'picture' => $this->t('Picture'),
      'init' => $this->t('Init'),
      'data' => $this->t('User data'),
      'roles' => $this->t('Roles'),
    ];

    // Profile fields.
    if ($this->moduleExists('profile')) {
      $fields += $this->select('profile_fields', 'pf')
        ->fields('pf', ['name', 'title'])
        ->execute()
        ->fetchAllKeyed();
    }

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $roles = $this->select('users_roles', 'ur')
      ->fields('ur', ['rid'])
      ->condition('ur.uid', $row->getSourceProperty('uid'))
      ->execute()
      ->fetchCol();
    $row->setSourceProperty('roles', $roles);

    $row->setSourceProperty('data', unserialize($row->getSourceProperty('data')));

    // Get Field API field values.
    foreach (array_keys($this->getFields('user')) as $field) {
      $row->setSourceProperty($field, $this->getFieldValues('user', $field, $row->getSourceProperty('uid')));
    }

    // Get profile field values. This code is lifted directly from the D6
    // ProfileFieldValues plugin.
    if ($this->getDatabase()->schema()->tableExists('profile_value')) {
      $query = $this->select('profile_value', 'pv')
        ->fields('pv', ['fid', 'value']);
      $query->leftJoin('profile_field', 'pf', 'pf.fid=pv.fid');
      $query->fields('pf', ['name', 'type']);
      $query->condition('uid', $row->getSourceProperty('uid'));
      $results = $query->execute();

      foreach ($results as $profile_value) {
        if ($profile_value['type'] == 'date') {
          $date = unserialize($profile_value['value']);
          $date = date('Y-m-d', mktime(0, 0, 0, $date['month'], $date['day'], $date['year']));
          $row->setSourceProperty($profile_value['name'], ['value' => $date]);
        }
        elseif ($profile_value['type'] == 'list') {
          // Explode by newline and comma.
          $row->setSourceProperty($profile_value['name'], preg_split("/[\r\n,]+/", $profile_value['value']));
        }
        else {
          $row->setSourceProperty($profile_value['name'], [$profile_value['value']]);
        }
      }
    }

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'uid' => [
        'type' => 'integer',
        'alias' => 'u',
      ],
    ];
  }

}

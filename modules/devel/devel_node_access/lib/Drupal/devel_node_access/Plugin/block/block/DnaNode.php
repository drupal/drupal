<?php
/**
 * @file
 * Contains \Drupal\devel_node_access\Plugin\block\block\DnaNode.
 */

namespace Drupal\devel_node_access\Plugin\block\block;

use Drupal\block\BlockBase;
use Drupal\devel_node_access\DnaBlockBase;
use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Provides the "Devel Node Access" block.
 *
 * @Plugin(
 *   id = "devel_dna_node_block",
 *   admin_label = @Translation("Devel Node Access"),
 *   module = "devel_node_access"
 * )
 */
class DnaNode extends DnaBlockBase {

  /**
   * Overrides \Drupal\block\BlockBase::blockAccess().
   */
  public function blockAccess() {
    return user_access(DNA_ACCESS_VIEW);
  }

  /**
   * Implements \Drupal\block\BlockBase::build().
   */
  public function build() {
    $form_state = array();
    $form_state['build_info']['args'] = array();
    $form_state['build_info']['callback'] = array($this, 'buildForm');
    $form = drupal_build_form('devel_node_access_form', $form_state);
    return $form;
  }

  /**
   * Builds the content of the block.
   *
   * @return array
   *   A renderable array representing the content of the block.
   */
  public function buildForm() {
    global $user;

    $visible_nodes = self::visible_nodes();
    if (count($visible_nodes) == 0) {
      return array();
    }
    else {
      $single_nid = reset($visible_nodes);
    }

    // Find out whether our DnaUser block is active or not.
    $user_block_active = FALSE;
    foreach (block_list($this->entity->get('region')) as $block) {
      if ($block->get('instance')->plugin_id == 'devel_dna_user_block') {
        $user_block_active = TRUE;
      }
    }
    $hint = '';
    if (!$user_block_active) {
      $hint = t('For per-user access permissions enable the <a href="@link">%DNAbU block</a>.', array('@link' => url('admin/structure/block'), '%DNAbU' => t('Devel Node Access by User')));
    }

    $output = array('title' => array(
      '#prefix' => '<h2>',
      '#markup' => t('node_access entries for nodes shown on this page'),
      '#suffix' => '</h2>',
    ));

    // Include rows where nid == 0.
    $nids = array_merge(array(0 => 0), $visible_nodes);
    $query = db_select('node_access', 'na');
    $query
      ->fields('na')
      ->condition('na.nid', $nids, 'IN')
      ->orderBy('na.nid')
      ->orderBy('na.realm')
      ->orderBy('na.gid');
    $nodes = node_load_multiple($nids);

    if (!config('devel_node_access.settings')->get('debug_mode')) {
      $headers = array(t('node'), t('realm'), t('gid'), t('view'), t('update'), t('delete'), t('explained'));
      $rows = array();
      foreach ($query->execute() as $row) {
        $explained = module_invoke_all('node_access_explain', $row);
        $rows[] = array(
          (empty($row->nid) ? '0' : '<a href="#node-' . $row->nid . '">' . self::get_node_title($nodes[$row->nid], TRUE) . '</a>'),
          $row->realm,
          $row->gid,
          $row->grant_view,
          $row->grant_update,
          $row->grant_delete,
          implode('<br />', $explained),
        );
      }
      $output[] = array(
        '#theme'      => 'table',
        '#header'     => $headers,
        '#rows'       => $rows,
        '#attributes' => array('style' => 'text-align: left')
      );

      $hint = t('To see more details enable <a href="@debug_mode">debug mode</a>.', array('@debug_mode' => url('admin/config/development/devel', array('fragment' => 'edit-devel-node-access')))) . (empty($hint) ? '' : ' ' . $hint);
    }
    else {
      $tr = 't';
      $variables = array('!na' => '{node_access}');
      $states = array(
        'default'      => array(t('default'),      'ok',      t('Default record supplied by core in the absence of any other non-empty records; in !na.', $variables)),
        'ok'           => array(t('ok'),           'ok',      t('Highest priority record; in !na.', $variables)),
        'removed'      => array(t('removed'),      '',        t('Was removed in @func; not in !na.', $variables + array('@func' => 'hook_node_access_records_alter()'))),
        'static'       => array(t('static'),       'ok',      t('Non-standard record in !na.', $variables)),
        'unexpected'   => array(t('unexpected'),   'warning', t('The 0/all/0/... record applies to all nodes and all users -- usually it should not be present in !na if any node access module is active!')),
        'ignored'      => array(t('ignored'),      'warning', t('Lower priority record; not in !na and thus ignored.', $variables)),
        'empty'        => array(t('empty'),        'warning', t('Does not grant any access, but could block lower priority records; not in !na.', $variables)),
        'wrong'        => array(t('wrong'),        'error',   t('Is rightfully in !na but at least one access flag is wrong!', $variables)),
        'missing'      => array(t('missing'),      'error',   t("Should be in !na but isn't!", $variables)),
        'removed!'     => array(t('removed!'),     'error',   t('Was removed in @func; should NOT be in !na!', $variables + array('@func' => 'hook_node_access_records_alter()'))),
        'illegitimate' => array(t('illegitimate'), 'error',   t('Should NOT be in !na because of lower priority!', $variables)),
        'alien'        => array(t('alien'),        'error',   t('Should NOT be in !na because of unknown origin!', $variables)),
      );
      $active_states = array('default', 'ok', 'static', 'unexpected', 'wrong', 'illegitimate', 'alien');
      $headers = array(t('node'), t('prio'), t('status'), t('realm'), t('gid'), t('view'), t('update'), t('delete'), t('explained'));
      $headers = self::format_row($headers);
      $active_records = array();
      foreach ($query->execute() as $active_record) {
        $active_records[$active_record->nid][$active_record->realm][$active_record->gid] = $active_record;
      }
      $all_records = $grants_data = $checked_grants = $grants = array();

      foreach (array('view', 'update', 'delete') as $op) {
        $grants[$op] = self::simulate_module_invoke_all('node_grants', $user, $op);
        // Call all hook_node_grants_alter() implementations.
        $grants_data[$op] = self::simulate_node_grants_alter($grants[$op], $user, $op);
      }

      foreach ($nids as $nid) {
        $top_priority = -99999;
        $acquired_records_nid = array();
        if ($node = node_load($nid)) {
          // Check node_access_acquire_grants().
          $records = self::simulate_module_invoke_all('node_access_records', $node);
          // Check drupal_alter('node_access_records').
          $data = self::simulate_node_access_records_alter($records, $node);
          if (!empty($data)) {
            foreach ($data as $data_by_realm) {
              foreach ($data_by_realm as $data_by_realm_gid) {
                if (isset($data_by_realm_gid['current'])) {
                  $record = $data_by_realm_gid['current'];
                }
                elseif (isset($data_by_realm_gid['original'])) {
                  $record = $data_by_realm_gid['original'];
                  $record['#removed'] = 1;
                }
                else {
                  continue;
                }
                $priority = intval(isset($record['priority']) ? $record['priority'] : 0);
                $top_priority = (isset($top_priority) ? max($top_priority, $priority) : $priority);
                $record['priority'] = (isset($record['priority']) ? $priority : '&ndash;&nbsp;');
                $record['history'] = $data_by_realm_gid;
                $acquired_records_nid[$priority][$record['realm']][$record['gid']] = $record + array(
                  '#title'  => self::get_node_title($node),
                  '#module' => (isset($record['#module']) ? $record['#module'] : ''),
                );
              }
            }
            krsort($acquired_records_nid);
          }
          //dpm($acquired_records_nid, "acquired_records_nid =");

          // Check node_access_grants().
          if ($node->nid) {
            foreach (array('view', 'update', 'delete') as $op) {
              $checked_grants[$nid][$op] = array_merge(array('all' => array(0)), $grants[$op]);
            }
          }
        }

        // Check for records in the node_access table that aren't returned by
        // node_access_acquire_grants().

        if (isset($active_records[$nid])) {
          foreach ($active_records[$nid] as $realm => $active_records_realm) {
            foreach ($active_records_realm as $gid => $active_record) {
              $found = FALSE;
              $count_nonempty_records = 0;
              foreach ($acquired_records_nid as $priority => $acquired_records_nid_priority) {
                if (isset($acquired_records_nid_priority[$realm][$gid])) {
                  $found = TRUE;
                }
              }
              // Take the highest priority only.
              // TODO This has changed in D8!
              if ($acquired_records_nid_priority = reset($acquired_records_nid)) {
                foreach ($acquired_records_nid_priority as $acquired_records_nid_priority_realm) {
                  foreach ($acquired_records_nid_priority_realm as $acquired_records_nid_priority_realm_gid) {
                    $count_nonempty_records += (!empty($acquired_records_nid_priority_realm_gid['grant_view']) || !empty($acquired_records_nid_priority_realm_gid['grant_update']) || !empty($acquired_records_nid_priority_realm_gid['grant_delete']));
                  }
                }
              }
              $fixed_record = (array) $active_record;
              if ($count_nonempty_records == 0 && $realm == 'all' && $gid == 0) {
                $fixed_record += array(
                  'priority' => '&ndash;',
                  'state'    => 'default',
                );
              }
              elseif (!$found) {
                $acknowledged = self::simulate_module_invoke_all('node_access_acknowledge', $fixed_record);
                if (empty($acknowledged)) {
                  // No module acknowledged this record, mark it as alien.
                  $fixed_record += array(
                    'priority' => '?',
                    'state'    => 'alien',
                  );
                }
                else {
                  // At least one module acknowledged the record,
                  // attribute it to the first one.
                  $fixed_record += array(
                    'priority' => '&ndash;',
                    'state'    => 'static',
                    '#module'  => reset(array_keys($acknowledged)),
                  );
                }
              }
              else {
                continue;
              }
              $fixed_record += array(
                'nid'    => $nid,
                '#title' => self::get_node_title($node),
              );
              $all_records[] = $fixed_record;
            }
          }
        }

        // Order records and evaluate their status.
        foreach ($acquired_records_nid as $priority => $acquired_records_priority) {
          ksort($acquired_records_priority);
          foreach ($acquired_records_priority as $realm => $acquired_records_realm) {
            ksort($acquired_records_realm);
            foreach ($acquired_records_realm as $gid => $acquired_record) {
              // TODO: Handle priority.
              //if ($priority == $top_priority) {
                if (empty($acquired_record['grant_view']) && empty($acquired_record['grant_update']) && empty($acquired_record['grant_delete'])) {
                  $acquired_record['state'] = 'empty';
                }
                else {
                  if (isset($active_records[$nid][$realm][$gid])) {
                    $acquired_record['state'] = (isset($acquired_record['#removed']) ? 'removed!' : 'ok');
                  }
                  else {
                    $acquired_record['state'] = (isset($acquired_record['#removed']) ? 'removed' : 'missing');
                  }
                  if ($acquired_record['state'] == 'ok') {
                    foreach (array('view', 'update', 'delete') as $op) {
                      $active_record = (array) $active_records[$nid][$realm][$gid];
                      if (empty($acquired_record["grant_$op"]) != empty($active_record["grant_$op"])) {
                        $acquired_record["grant_$op!"] = $active_record["grant_$op"];
                      }
                    }
                  }
                }
              //}
              //else {
              //  $acquired_record['state'] = (isset($active_records[$nid][$realm][$gid]) ? 'illegitimate' : 'ignored');
              //}
              $all_records[] = $acquired_record + array('nid' => $nid);
            }
          }
        }
      }

      // Fill in the table rows.
      $rows = array();
      $error_count = 0;
      foreach ($all_records as $record) {
        $row = new \stdClass();
        $row->nid = $record['nid'];
        $row->title = $record['#title'];
        $row->priority = $record['priority'];
        $row->state = array('data' => $states[$record['state']][0], 'title' => $states[$record['state']][2]);
        $row->realm = $record['realm'];
        $row->gid = $record['gid'];
        $row->grant_view = $record['grant_view'];
        $row->grant_update = $record['grant_update'];
        $row->grant_delete = $record['grant_delete'];
        $row->explained = implode('<br />', module_invoke_all('node_access_explain', $row));
        unset($row->title);
        if ($row->nid == 0 && $row->gid == 0 && $row->realm == 'all' && count($all_records) > 1) {
          $row->state = array('data' => $states['unexpected'][0], 'title' => $states['unexpected'][2]);
          $class = $states['unexpected'][1];
        }
        else {
          $class = $states[$record['state']][1];
        }
        $row = (array) $row;
        foreach (array('view', 'update', 'delete') as $op) {
          $row["grant_$op"] = array('data' => $row["grant_$op"]);
          if ((isset($checked_grants[$record['nid']][$op][$record['realm']]) && in_array($record['gid'], $checked_grants[$record['nid']][$op][$record['realm']]) || ($row['nid'] == 0 && $row['gid'] == 0 && $row['realm'] == 'all')) && !empty($row["grant_$op"]['data']) && in_array($record['state'], $active_states)) {
            $row["grant_$op"]['data'] .= '&prime;';
            $row["grant_$op"]['title'] = t('This entry grants access to this node to this user.');
          }
          if (isset($record["grant_$op!"])) {
            $row["grant_$op"]['data'] = $record["grant_$op!"] . '&gt;' . (!$row["grant_$op"]['data'] ? 0 : $row["grant_$op"]['data']);
            $row["grant_$op"]['class'][] = 'error';
            if ($class == 'ok') {
              $row['state'] = array('data' => $states['wrong'][0], 'title' => $states['wrong'][2]);
              $class = $states['wrong'][1];
            }
          }
        }
        $error_count += ($class == 'error');
        $row['nid'] = array(
          'data'  => '<a href="#node-' . $record['nid'] . '">' . $row['nid'] . '</a>',
          'title' => $record['#title'],
        );
        if (empty($record['#module']) || strpos($record['realm'], $record['#module']) === 0) {
          $row['realm'] = $record['realm'];
        }
        else {
          $row['realm'] = array(
            'data' => '(' . $record['#module'] . '::) ' . $record['realm'],
            'title' => t("The '@module' module fails to adhere to the best practice of naming its realm(s) after itself.", array('@module' => $record['#module'])),
          );
        }

        // Prepend information from the D7 hook_node_access_records_alter().
        $next_style = array();
        if (isset($record['history'])) {
          $history = $record['history'];
          if (($num_changes = count($history['changes']) - empty($history['current'])) > 0) {
            $first_row = TRUE;
            while (isset($history['original']) || $num_changes--) {
              if (isset($history['original'])) {
                $this_record = $history['original'];
                $this_action = '[ Original by ' . $this_record['#module'] . ':';
                unset($history['original']);
              }
              else {
                $change = $history['changes'][0];
                $this_record = $change['record'];
                $this_action = ($first_row ? '[ ' : '') . $change['op'] . ':';
                array_shift($history['changes']);
              }
              $rows[] = array(
                'data'  => array(
                  'data'  => array(
                    'data'    => $this_action,
                    'style'   => array('padding-bottom: 0;'),
                  ),
                ),
                'style' => array_merge(($first_row ? array() : array('border-top-style: dashed;', 'border-top-width: 1px;')), array('border-bottom-style: none;')),
              );
              $next_style = array('border-top-style: none;');
              if (count($history['changes'])) {
                $g = $this_record;
                $rows[] = array(
                  'data'  => array('v', $g['priority'], '', $g['realm'], $g['gid'], $g['grant_view'], $g['grant_update'], $g['grant_delete'], 'v'),
                  'style' => array('border-top-style: none;', 'border-bottom-style: dashed;'),
                );
                $next_style = array('border-top-style: dashed;');
              }
              $first_row = FALSE;
            }
          }
        }

        // Fix up the main row cells with the proper class (needed for Bartik).
        foreach ($row as $key => $value) {
          if (!is_array($value)) {
            $row[$key] = array('data' => $value);
          }
          $row[$key]['class'] = array($class);
        }
        // Add the main row.
        $will_append = empty($history['current']) && !empty($history['changes']);
        $rows[] = array(
          'data'  => array_values($row),
          'class' => array($class),
          'style' => array_merge($next_style, ($will_append ? array('border-bottom-style: none;') : array())),
        );

        // Append information from the D7 hook_node_access_records_alter().
        if ($will_append) {
          $last_change = end($history['changes']);
          $rows[] = array(
            'data'  => array(
              'data'  => array(
                'data'    => $last_change['op'] . ' ]',
                'style' => array('padding-top: 0;'),
              ),
            ),
            'style' => array('border-top-style: none;'),
          );
        }
      }

      foreach ($rows as $i => $row) {
        $rows[$i] = self::format_row($row);
      }

      $output[] = array(
        '#theme'      => 'table',
        '#header'     => $headers,
        '#rows'       => $rows,
        '#attributes' => array(
          'class'       => array('system-status-report'),
          'style'       => 'text-align: left;',
        ),
      );

      $output[] = array(
        '#theme'       => 'form_element',
        '#description' => t('(Some of the table elements provide additional information if you hover your mouse over them.)'),
      );

      if ($error_count > 0) {
        $variables['!Rebuild_permissions'] = '<a href="' . url('admin/reports/status/rebuild') . '">' . $tr('Rebuild permissions') . '</a>';
        $output[] = array(
          '#prefix' => "\n<span class=\"error\">",
          '#markup' => t("You have errors in your !na table! You may be able to fix these for now by running !Rebuild_permissions, but this is likely to destroy the evidence and make it impossible to identify the underlying issues. If you don't fix those, the errors will probably come back again. <br /> DON'T do this just yet if you intend to ask for help with this situation.", $variables),
          '#suffix' => "</span><br />\n",
        );
      }

      // Explain whether access is granted or denied, and why
      // (using code from node_access()).
      $tr = 't';
      array_shift($nids);  // Remove the 0.
      $accounts = array();
      $variables += array(
        '!username' => '<em class="placeholder">' . theme('username', array('account' => $user)) . '</em>',
        '%uid'      => $user->uid,
      );

      if (user_access('bypass node access')) {
        $variables['%bypass_node_access'] = $tr('bypass node access');
        $output[] = array(
          '#markup' => t('!username has the %bypass_node_access permission and thus full access to all nodes.', $variables),
          '#suffix' => '<br />&nbsp;',
        );
      }
      else {
        $variables['!list'] = '<div style="margin-left: 2em">' . self::get_grant_list($grants_data['view']) . '</div>';
        $variables['%access'] = 'view';
        $output[] = array(
          '#prefix' => "\n<div style='text-align: left' title='" . t('These are the grants returned by hook_node_grants() for this user.') . "'>",
          '#markup' => t('!username (user %uid) can use these grants (if they are present above) for %access access: !list', $variables),
          '#suffix' => "</div>\n",
        );
        $accounts[] = $user;
      }

      if (isset($single_nid) && !$user_block_active) {
        // Only for single nodes.
        if (user_is_logged_in()) {
          $accounts[] = user_load(0);  // Anonymous, too.
        }
        foreach ($accounts as $account) {
          $nid_items = array();
          foreach ($nids as $nid) {
            $op_items = array();
            foreach (array('create', 'view', 'update', 'delete') as $op) {
              $explain = self::explain_access($op, node_load($nid), $account);
              $op_items[] = "<div style='width: 5em; display: inline-block'>" . t('%op:', array('%op' => $op)) . ' </div>' . $explain[2];
            }
            $nid_items[] = array(
              '#theme'  => 'item_list',
              '#items'  => $op_items,
              '#type'   => 'ul',
              '#prefix' => t('to node !nid:', array('!nid' => l($nid, 'node/' . $nid))) . "\n<div style='margin-left: 2em'>",
              '#suffix' => '</div>',
            );
          }
          if (count($nid_items) == 1) {
            $account_items = $nid_items[0];
          }
          else {
            $account_items = array(
              '#theme'  => 'item_list',
              '#items'  => $nid_items,
              '#type'   => 'ul',
              '#prefix' => "\n<div style='margin-left: 2em'>",
              '#suffix' => '</div>',
            );
          }
          $variables['!username'] = '<em class="placeholder">' . theme('username', array('account' => $account)) . '</em>';
          $output[] = array(
            '#prefix' => "\n<div style='text-align: left'>",
            '#type'   => 'item',
            'lead-in' => array('#markup' => t("!username has the following access", $variables) . ' '),
            'items'   => $account_items,
            '#suffix' => "\n</div>\n",
          );
        }
      }
    }

    if (!empty($hint)) {
      $output[] = array(
        '#theme'        => 'form_element',
        '#description'  => '(' . $hint . ')',
      );
    }
    return $output;
  }

  /**
   * Helper function to mimic module_invoke_all() and include the name of
   * the responding module(s).
   *
   * @param $hook
   *   The name of the hook.
   *
   * @return array
   *   An array of results.
   *   In the case of scalar results, the array is keyed by the name of the
   *   modules that returned the result (rather than by numeric index), and
   *   in the case of array results, a '#module' key is added.
   */
  private static function simulate_module_invoke_all($hook) {
    $args = func_get_args();
    // Remove $hook from the arguments.
    array_shift($args);
    $return = array();
    foreach (module_implements($hook) as $module) {
      $function = $module . '_' . $hook;
      $result = call_user_func_array($function, $args);
      if (isset($result)) {
        if (is_array($result)) {
          foreach ($result as $key => $value) {
            // Add name of module that returned the value.
            $result[$key]['#module'] = $module;
          }
        }
        else {
          // Build array with result keyed by $module.
          $result = array($module => $result);
        }
        $return = \Drupal\Component\Utility\NestedArray::mergeDeep($return, $result);
      }
    }
    return $return;
  }

  /**
   * Helper function to mimic hook_node_access_records_alter() and trace what
   * each module does with it.
   *
   * @param array $records
   *   An indexed array of NA records, augmented by the '#module' key,
   *   as created by simulate_module_invoke_all('node_access_records').
   *   This array is updated by the hook_node_access_records_alter()
   *   implementations.
   * @param $node
   *   The node that the NA records belong to.
   *
   * @return array
   *   A tree representation of the NA records in $records including their
   *   history:
   *   $data[$realm][$gid]
   *     ['original']  - NA record before processing
   *     ['current']   - NA record after processing (if still present)
   *     ['changes'][]['op']     - change message (add/change/delete by $module)
   *                  ['record'] - NA record after change (unless deleted)
   */
  private static function simulate_node_access_records_alter(&$records, $node) {
    //dpm($records, 'simulate_node_access_records_alter(): records IN');
    $hook = 'node_access_records_alter';

    // Build the initial tree (and check for duplicates).
    $data = self::build_node_access_records_data($records, $node, 'hook_node_access_records()');

    // Simulate drupal_alter('node_access_records', $records, $node).
    foreach (module_implements($hook) as $module) {
      // Call hook_node_access_records_alter() for one module at a time
      // and analyze.
      $function = $module . '_' . $hook;
      $function($records, $node);

      foreach ($records as $i => $record) {
        if (empty($data[$record['realm']][$record['gid']]['current'])) {
          // It's an added record.
          $data[$record['realm']][$record['gid']]['current'] = $record;
          $data[$record['realm']][$record['gid']]['current']['#module'] = $module;
          $data[$record['realm']][$record['gid']]['changes'][] = array(
            'op'     => 'added by ' . $module,
            'record' => $record,
          );
          $records[$i]['#module'] = $module;
        }
        else {
          // It's an existing record, check for changes.
          $view = $update = $delete = FALSE;
          foreach (array('view', 'update', 'delete') as $op) {
            $$op = $record["grant_$op"] - $data[$record['realm']][$record['gid']]['current']["grant_$op"];
          }
          $old_priority = isset($record['priority']) ? $record['priority'] : 0;
          $new_priority = isset($data[$record['realm']][$record['gid']]['current']['priority']) ? $data[$record['realm']][$record['gid']]['current']['priority'] : 0;
          if ($view || $update || $delete || $old_priority != $new_priority) {
            // It was changed.
            $data[$record['realm']][$record['gid']]['current'] = $record;
            $data[$record['realm']][$record['gid']]['current']['#module'] = $module;
            $data[$record['realm']][$record['gid']]['changes'][] = array(
              'op'     => 'altered by ' . $module,
              'record' => $record,
            );
            $records[$i]['#module'] = $module;
          }
        }
        $data[$record['realm']][$record['gid']]['found'] = TRUE;
      }

      // Check for newly introduced duplicates.
      self::build_node_access_records_data($records, $node, 'hook_node_access_records_alter()');

      // Look for records that have disappeared.
      foreach ($data as $realm => $data2) {
        foreach ($data2 as $gid => $data3) {
          if (empty($data[$realm][$gid]['found']) && isset($data[$realm][$gid]['current'])) {
            unset($data[$realm][$gid]['current']);
            $data[$realm][$gid]['changes'][] = array('op' => 'removed by ' . $module);
          }
          else {
            unset($data[$realm][$gid]['found']);
          }
        }
      }
    }
    //dpm($data, 'simulate_node_access_records_alter() returns');
    //dpm($records, 'simulate_node_access_records_alter(): records OUT');
    return $data;
  }

  /**
   * Helper function to build an associative array of node access records and
   * their history. If there are duplicate records, display an error message.
   *
   * @param $records
   *   An indexed array of node access records, augmented by the '#module' key,
   *   as created by simulate_module_invoke_all('node_access_records').
   * @param $node
   *   The node that the NA records belong to.
   * @param $function
   *   The name of the hook that produced the records array, in case we need to
   *   display an error message.
   *
   * @return array
   *   See _devel_node_access_nar_alter() for the description of the result.
   */
  private static function build_node_access_records_data($records, $node, $function) {
    $data = array();
    $duplicates = array();
    foreach ($records as $record) {
      if (empty($data[$record['realm']][$record['gid']])) {
        $data[$record['realm']][$record['gid']] = array('original' => $record, 'current' => $record, 'changes' => array());
      }
      else {
        if (empty($duplicates[$record['realm']][$record['gid']])) {
          $duplicates[$record['realm']][$record['gid']][] = $data[$record['realm']][$record['gid']]['original'];
        }
        $duplicates[$record['realm']][$record['gid']][] = $record;
      }
    }
    if (!empty($duplicates)) {
      // Generate an error message.
      $msg = t('Devel Node Access has detected duplicate records returned from %function:', array('%function' => $function));
      $msg .= '<ul>';
      foreach ($duplicates as $realm => $data_by_realm) {
        foreach ($data_by_realm as $gid => $data_by_realm_gid) {
          $msg .= '<li><ul>';
          foreach ($data_by_realm_gid as $record) {
            $msg .= "<li>$node->nid/$realm/$gid/" . ($record['grant_view'] ? 1 : 0) . ($record['grant_update'] ? 1 : 0) . ($record['grant_delete'] ? 1 : 0) . ' by ' . $record['#module'] . '</li>';
          }
          $msg .= '</ul></li>';
        }
      }
      $msg .= '</ul>';
      drupal_set_message($msg, 'error', FALSE);
    }
    return $data;
  }

  /**
   * Helper function to mimic hook_node_grants_alter() and trace what
   * each module does with it.
   *
   * @param array $grants
   *   An indexed array of grant records, augmented by the '#module' key,
   *   as created by simulate_module_invoke_all('node_grants').
   *   This array is updated by the hook_node_grants_alter()
   *   implementations.
   * @param $account
   *   The user account requesting access to content.
   * @param $op
   *   The operation being performed, 'view', 'update' or 'delete'.
   *
   * @return array
   *   A tree representation of the grant records in $grants including their
   *   history:
   *   $data[$realm][$gid]
   *     ['cur']    - TRUE or FALSE whether the gid is present or not
   *     ['ori'][]  - array of module names that contributed this grant (if any)
   *     ['chg'][]  - array of changes, such as
   *                     - 'added' if module name is a prefix if the $realm,
   *                     - 'added by module' otherwise, or
   *                     - 'removed by module'
   */
  private static function simulate_node_grants_alter(&$grants, $account, $op) {
    //dpm($grants, "simulate_node_grants_alter($account->name, $op): grants IN");
    $hook = 'node_grants_alter';

    // Build the initial structure.
    $data = array();
    foreach ($grants as $realm => $gids) {
      foreach ($gids as $i => $gid) {
        if ($i !== '#module') {
          $data[$realm][$gid]['cur'] = TRUE;
          $data[$realm][$gid]['ori'][] = $gids['#module'];
        }
      }
      unset($grants[$realm]['#module']);
    }

    // Simulate drupal_alter('node_grants', $grants, $account, $op).
    foreach (module_implements($hook) as $module) {
      // Call hook_node_grants_alter() for one module at a time and analyze.
      $function = $module . '_' . $hook;
      $function($grants, $account, $op);

      // Check for new gids.
      foreach ($grants as $realm => $gids) {
        foreach ($gids as $i => $gid) {
          if (empty($data[$realm][$gid]['cur'])) {
            $data[$realm][$gid]['cur'] = TRUE;
            $data[$realm][$gid]['chg'][] = 'added by ' . $module;
          }
        }
      }

      // Check for removed gids.
      foreach ($data as $realm => $gids) {
        foreach  ($gids as $gid => $history) {
          if ($history['cur'] && array_search($gid, $grants[$realm]) === FALSE) {
            $data[$realm][$gid]['cur'] = FALSE;
            $data[$realm][$gid]['chg'][] = 'removed by ' . $module;
          }
        }
      }
    }

    //dpm($data, "simulate_node_grants_alter($account->name, $op) returns");
    //dpm($grants, "simulate_node_grants_alter($account->name, $op): grants OUT");
    return $data;
  }

  /**
   * Helper function to create a list of grants returned by hook_node_grants().
   */
  private static function get_grant_list($grants_data) {
    //dpm($grants_data, "get_grant_list() IN:");
    $grants_data = array_merge(array('all' => array(0 => array('cur' => TRUE, 'ori' => array('all')))), $grants_data);
    $items = array();
    if (count($grants_data)) {
      foreach ($grants_data as $realm => $gids) {
        ksort($gids);
        $gs = array();
        foreach ($gids as $gid => $history) {
          if ($history['cur']) {
            if (isset($history['ori'])) {
              $g = $gid;                     // Original grant, still active.
            }
            else {
              $g = '<u>' . $gid . '</u>';    // New grant, still active.
            }
          }
          else {
            $g = '<del>' . $gid . '</del>';  // Deleted grant.
          }

          $ghs = array();
          if (isset($history['ori']) && strpos($realm, $history['ori'][0]) !== 0) {
            $realm = '(' . $history['ori'][0] . '::) ' . $realm;
          }
          if (isset($history['chg'])) {
            foreach ($history['chg'] as $h) {
              $ghs[] = $h;
            }
          }
          if (!empty($ghs)) {
            $g .= ' (' . implode(', ', $ghs) . ')';
          }
          $gs[] = $g;
        }
        $items[] = $realm . ': ' . implode(', ', $gs);
      }
      if (!empty($items)) {
        return theme('item_list', array('items' => $items, 'type' => 'ul'));
      }
    }
    return '';
  }

  /**
   * Helper function to return a sanitized node title.
   */
  private static function get_node_title($node, $clip_and_decorate = FALSE) {
    if (isset($node)) {
      if (isset($node->title)) {
        $node_title = check_plain(!is_array($node->title) ? $node->title : $node->title[LANGUAGE_NOT_SPECIFIED][0]['value']);
        if ($clip_and_decorate) {
          if (drupal_strlen($node_title) > 20) {
            $node_title = "<span title='node/$node->nid: $node_title'>" . drupal_substr($node_title, 0, 15) . '...</span>';
          }
          $node_title = '<span title="node/' . $node->nid . '">' . $node_title . '</span>';
        }
        return $node_title;
      }
      elseif (isset($node->nid)) {
        return $node->nid;
      }
    }
    return '&mdash;';
  }

  /**
   * Helper function to apply common formatting to a debug-mode table row.
   */
  private static function format_row($row, $may_unpack = TRUE) {
    if ($may_unpack && isset($row['data'])) {
      $row['data'] = self::format_row($row['data'], FALSE);
      $row['class'][] = 'even';
      return $row;
    }
    if (count($row) == 1) {
      if (is_scalar($row['data'])) {
        $row['data'] = array('data' => $row['data']);
      }
      $row['data']['colspan'] = 9;
    }
    else {
      $row = array_values($row);
      foreach (array(0, 1, 4) as $j) {  // node, prio, gid
        if (is_scalar($row[$j])) {
          $row[$j] = array('data' => $row[$j]);
        }
        $row[$j]['style'][] = 'text-align: right;';
      }
    }
    return $row;
  }

}

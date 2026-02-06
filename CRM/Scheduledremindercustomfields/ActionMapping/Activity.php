<?php

/**
 * Extended Activity ActionMapping with Custom Field Support
 */
class CRM_Scheduledremindercustomfields_ActionMapping_Activity extends CRM_Activity_ActionMapping {

  /**
   * Override createQuery to add custom field filtering
   */
  public function createQuery($schedule, $phase, $defaultParams) {
    $query = parent::createQuery($schedule, $phase, $defaultParams);

    // Add custom field filtering if conditions exist
    if (!empty($schedule->custom_field_filter_data)) {
      $this->addCustomFieldFiltering($query, $schedule);
    }

    return $query;
  }

  /**
   * Add custom field filtering specific to activities
   */
  protected function addCustomFieldFiltering($query, $schedule) {
    $customConditions = unserialize($schedule->custom_field_filter_data);

    if (!is_array($customConditions)) {
      return;
    }

    $joinIndex = 1;
    foreach ($customConditions as $condition) {
      if (empty($condition['entity']) || empty($condition['field_id']) || empty($condition['operator'])) {
        continue;
      }

      $fieldInfo = $this->getCustomFieldInfo($condition['field_id']);
      if (!$fieldInfo) {
        continue;
      }

      $entity = $condition['entity'];
      $operator = $condition['operator'];
      $value = CRM_Utils_Array::value('value', $condition);

      // Add appropriate JOIN based on entity
      $joinAlias = $this->addCustomFieldJoin($query, $entity, $fieldInfo, $joinIndex);

      // Add WHERE condition
      $this->addCustomFieldWhere($query, $joinAlias, $fieldInfo, $operator, $value, $joinIndex);

      $joinIndex++;
    }
  }

  /**
   * Add JOIN for custom field table - Activity specific
   */
  protected function addCustomFieldJoin($query, $entity, $fieldInfo, $joinIndex) {
    $tableName = $fieldInfo['table_name'];
    $joinAlias = "custom_field_{$joinIndex}";

    switch ($entity) {
      case 'Contact':
        // For activities, contact could be source, assignee, or target
        $query->join($joinAlias, "LEFT JOIN {$tableName} {$joinAlias} ON e.contact_id = {$joinAlias}.entity_id");
        break;

      case 'Activity':
        $query->join($joinAlias, "LEFT JOIN {$tableName} {$joinAlias} ON e.id = {$joinAlias}.entity_id");
        break;

      case 'Membership':
        // Join memberships for the contact
        $query->join('membership_join', "LEFT JOIN civicrm_membership membership ON e.contact_id = membership.contact_id");
        $query->join($joinAlias, "LEFT JOIN {$tableName} {$joinAlias} ON membership.id = {$joinAlias}.entity_id");
        break;

      case 'Event':
        // Join events through participant records
        $query->join('participant_join', "LEFT JOIN civicrm_participant participant ON e.contact_id = participant.contact_id");
        $query->join('event_join', "LEFT JOIN civicrm_event event ON participant.event_id = event.id");
        $query->join($joinAlias, "LEFT JOIN {$tableName} {$joinAlias} ON event.id = {$joinAlias}.entity_id");
        break;

      default:
        $query->join($joinAlias, "LEFT JOIN {$tableName} {$joinAlias} ON {$entity}.id = {$joinAlias}.entity_id");
        break;
    }

    return $joinAlias;
  }

  /**
   * Add WHERE condition for custom field - same implementation
   */
  protected function addCustomFieldWhere($query, $joinAlias, $fieldInfo, $operator, $value, $joinIndex) {
    $columnName = $fieldInfo['column_name'];

    switch ($operator) {
      case '=':
      case '!=':
      case '>':
      case '<':
      case '>=':
      case '<=':
        $query->where("{$joinAlias}.{$columnName} {$operator} %{$joinIndex}", [
          $joinIndex => [$value, 'String']
        ]);
        break;

      case 'LIKE':
      case 'NOT LIKE':
        $query->where("{$joinAlias}.{$columnName} {$operator} %{$joinIndex}", [
          $joinIndex => ['%' . $value . '%', 'String']
        ]);
        break;

      case 'IS NULL':
        $query->where("{$joinAlias}.{$columnName} IS NULL");
        break;

      case 'IS NOT NULL':
        $query->where("{$joinAlias}.{$columnName} IS NOT NULL");
        break;

      case 'IN':
      case 'NOT IN':
        if (is_array($value) && !empty($value)) {
          $inClause = [];
          $paramIndex = $joinIndex;
          foreach ($value as $val) {
            $inClause[] = "%{$paramIndex}";
            $query->param($paramIndex, $val, 'String');
            $paramIndex += 100;
          }
          $query->where("{$joinAlias}.{$columnName} {$operator} (" . implode(',', $inClause) . ")");
        }
        break;
    }
  }

  /**
   * Get custom field information with caching
   */
  protected function getCustomFieldInfo($fieldId) {
    static $cache = [];

    if (!isset($cache[$fieldId])) {
      try {
        $result = civicrm_api3('CustomField', 'getsingle', [
          'id' => $fieldId,
          'return' => ['table_name', 'column_name', 'data_type', 'html_type']
        ]);
        $cache[$fieldId] = $result;
      }
      catch (Exception $e) {
        $cache[$fieldId] = FALSE;
      }
    }

    return $cache[$fieldId];
  }
}

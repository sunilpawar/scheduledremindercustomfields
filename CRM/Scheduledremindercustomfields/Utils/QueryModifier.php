<?php

/**
 * Query modifier utility class
 */
class CRM_Scheduledremindercustomfields_Utils_QueryModifier {

  /**
   * Apply custom field filters to ActionSchedule query
   */
  public static function applyCustomFieldFilters($query, $customConditions, $mapping, $phase) {
    $joinIndex = 1000; // Start high to avoid conflicts with existing joins

    foreach ($customConditions as $condition) {
      if (empty($condition['entity']) || empty($condition['field_id']) || empty($condition['operator'])) {
        continue;
      }

      // Get custom field information
      $fieldInfo = self::getCustomFieldInfo($condition['field_id']);
      if (!$fieldInfo) {
        continue;
      }

      $entity = $condition['entity'];
      $operator = $condition['operator'];
      $value = CRM_Utils_Array::value('value', $condition);

      // Add JOIN and WHERE clauses
      self::addCustomFieldToQuery($query, $entity, $fieldInfo, $operator, $value, $mapping, $joinIndex);

      $joinIndex++;
    }
  }

  /**
   * Add custom field JOIN and WHERE to query
   */
  private static function addCustomFieldToQuery($query, $entity, $fieldInfo, $operator, $value, $mapping, $joinIndex) {
    $tableName = $fieldInfo['table_name'];
    $columnName = $fieldInfo['column_name'];
    $joinAlias = "cf_{$joinIndex}";

    // Determine JOIN strategy based on mapping type and entity
    $joinClause = self::buildJoinClause($entity, $tableName, $joinAlias, $mapping);

    if ($joinClause) {
      // Add the JOIN
      $query->join($joinAlias, $joinClause);

      // Add WHERE condition
      self::addWhereCondition($query, $joinAlias, $columnName, $operator, $value, $joinIndex);
    }
  }

  /**
   * Build appropriate JOIN clause based on entity and mapping
   */
  private static function buildJoinClause($entity, $tableName, $joinAlias, $mapping) {
    $mappingClass = get_class($mapping);

    switch ($mappingClass) {
      case 'CRM_Member_ActionMapping':
        return self::buildMembershipJoinClause($entity, $tableName, $joinAlias);

      case 'CRM_Event_ActionMapping':
        return self::buildEventJoinClause($entity, $tableName, $joinAlias);

      case 'CRM_Activity_ActionMapping':
        return self::buildActivityJoinClause($entity, $tableName, $joinAlias);

      case 'CRM_Contact_ActionMapping':
        return self::buildContactJoinClause($entity, $tableName, $joinAlias);

      default:
        return "LEFT JOIN {$tableName} {$joinAlias} ON e.id = {$joinAlias}.entity_id";
    }
  }

  /**
   * Build JOIN clause for membership reminders
   */
  private static function buildMembershipJoinClause($entity, $tableName, $joinAlias) {
    switch ($entity) {
      case 'Contact':
        // Join to contact custom fields via membership's contact_id
        return "LEFT JOIN {$tableName} {$joinAlias} ON e.contact_id = {$joinAlias}.entity_id";

      case 'Membership':
        // Join to membership custom fields directly
        return "LEFT JOIN {$tableName} {$joinAlias} ON e.id = {$joinAlias}.entity_id";

      case 'Activity':
        // Join activities related to the membership/contact
        return "LEFT JOIN civicrm_activity act_{$joinAlias} ON e.contact_id = act_{$joinAlias}.source_contact_id
                LEFT JOIN {$tableName} {$joinAlias} ON act_{$joinAlias}.id = {$joinAlias}.entity_id";

      default:
        return NULL;
    }
  }

  /**
   * Build JOIN clause for event reminders
   */
  private static function buildEventJoinClause($entity, $tableName, $joinAlias) {
    switch ($entity) {
      case 'Contact':
        // Join to contact custom fields via participant's contact_id
        return "LEFT JOIN {$tableName} {$joinAlias} ON e.contact_id = {$joinAlias}.entity_id";

      case 'Event':
        // Join to event custom fields via participant's event_id
        return "LEFT JOIN {$tableName} {$joinAlias} ON e.event_id = {$joinAlias}.entity_id";

      case 'Participant':
        // Join to participant custom fields directly
        return "LEFT JOIN {$tableName} {$joinAlias} ON e.id = {$joinAlias}.entity_id";

      case 'Activity':
        // Join activities related to the event/participant
        return "LEFT JOIN civicrm_activity act_{$joinAlias} ON e.contact_id = act_{$joinAlias}.source_contact_id
                LEFT JOIN {$tableName} {$joinAlias} ON act_{$joinAlias}.id = {$joinAlias}.entity_id";

      default:
        return NULL;
    }
  }

  /**
   * Build JOIN clause for activity reminders
   */
  private static function buildActivityJoinClause($entity, $tableName, $joinAlias) {
    switch ($entity) {
      case 'Contact':
        // Join to contact custom fields via activity's contact relationships
        return "LEFT JOIN {$tableName} {$joinAlias} ON e.contact_id = {$joinAlias}.entity_id";

      case 'Activity':
        // Join to activity custom fields directly
        return "LEFT JOIN {$tableName} {$joinAlias} ON e.id = {$joinAlias}.entity_id";

      case 'Membership':
        // Join memberships for the activity contact
        return "LEFT JOIN civicrm_membership mem_{$joinAlias} ON e.contact_id = mem_{$joinAlias}.contact_id
                LEFT JOIN {$tableName} {$joinAlias} ON mem_{$joinAlias}.id = {$joinAlias}.entity_id";

      case 'Event':
        // Join events via participant records for the activity contact
        return "LEFT JOIN civicrm_participant part_{$joinAlias} ON e.contact_id = part_{$joinAlias}.contact_id
                LEFT JOIN civicrm_event evt_{$joinAlias} ON part_{$joinAlias}.event_id = evt_{$joinAlias}.id
                LEFT JOIN {$tableName} {$joinAlias} ON evt_{$joinAlias}.id = {$joinAlias}.entity_id";

      default:
        return NULL;
    }
  }

  /**
   * Build JOIN clause for contact reminders
   */
  private static function buildContactJoinClause($entity, $tableName, $joinAlias) {
    switch ($entity) {
      case 'Contact':
        return "LEFT JOIN {$tableName} {$joinAlias} ON e.id = {$joinAlias}.entity_id";

      default:
        return NULL;
    }
  }

  /**
   * Add WHERE condition to query
   */
  private static function addWhereCondition($query, $joinAlias, $columnName, $operator, $value, $joinIndex) {
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
          $params = [];
          $paramIndex = $joinIndex * 100; // Avoid conflicts

          foreach ($value as $val) {
            $inClause[] = "%{$paramIndex}";
            $params[$paramIndex] = [$val, 'String'];
            $paramIndex++;
          }

          $query->where("{$joinAlias}.{$columnName} {$operator} (" . implode(',', $inClause) . ")", $params);
        }
        break;
    }
  }

  /**
   * Get custom field information with caching
   */
  private static function getCustomFieldInfo($fieldId) {
    static $cache = [];

    if (!isset($cache[$fieldId])) {
      try {
        $result = civicrm_api3('CustomField', 'getsingle', [
          'id' => $fieldId,
          'return' => ['table_name', 'column_name', 'data_type', 'html_type', 'extends']
        ]);
        $cache[$fieldId] = $result;
      }
      catch (Exception $e) {
        CRM_Core_Error::debug_log_message(
          'Scheduled Reminder Custom Fields: Error loading field ' . $fieldId . ': ' . $e->getMessage()
        );
        $cache[$fieldId] = FALSE;
      }
    }

    return $cache[$fieldId];
  }
}

<?php

/**
 * Hook into query building to add custom field conditions
 */
class CRM_Scheduledremindercustomfields_Utils_Query {

  /**
   * Modify ActionSchedule queries to include custom field conditions
   */
  public static function alterActionScheduleQuery(&$query, &$params) {
    if (!empty($GLOBALS['civicrm_scheduled_reminder_custom_conditions'])) {
      $customConditions = $GLOBALS['civicrm_scheduled_reminder_custom_conditions'];

      // Add custom WHERE clause
      if (!empty($customConditions['where_clause'])) {
        $query .= ' AND ' . $customConditions['where_clause'];
      }

      // Add custom parameters
      if (!empty($customConditions['where_params'])) {
        $params = array_merge($params, $customConditions['where_params']);
      }

      // Clear the global variable
      unset($GLOBALS['civicrm_scheduled_reminder_custom_conditions']);
    }
  }


  /**
   * Build JOIN clause for custom field based on entity and mapping
   */
  public static function buildCustomFieldJoin($entity, $tableName, $joinAlias, $mapping, $joinIndex) {
    $mappingClass = get_class($mapping);

    switch ($mappingClass) {
      case 'CRM_Member_ActionMapping':
        return self::buildMembershipJoin($entity, $tableName, $joinAlias);

      case 'CRM_Event_ActionMapping':
        return self::buildEventJoin($entity, $tableName, $joinAlias);

      case 'CRM_Activity_ActionMapping':
        return self::buildActivityJoin($entity, $tableName, $joinAlias);

      default:
        return self::buildGenericJoin($entity, $tableName, $joinAlias);
    }
  }

  /**
   * Build JOIN for membership-based reminders
   */
  private static function buildMembershipJoin($entity, $tableName, $joinAlias) {
    switch ($entity) {
      case 'Contact':
        return "LEFT JOIN {$tableName} {$joinAlias} ON e.contact_id = {$joinAlias}.entity_id";
      case 'Membership':
        return "LEFT JOIN {$tableName} {$joinAlias} ON e.id = {$joinAlias}.entity_id";
      default:
        return NULL;
    }
  }

  /**
   * Build JOIN for event-based reminders
   */
  private static function buildEventJoin($entity, $tableName, $joinAlias) {
    switch ($entity) {
      case 'Contact':
        return "LEFT JOIN {$tableName} {$joinAlias} ON e.contact_id = {$joinAlias}.entity_id";
      case 'Event':
        return "LEFT JOIN {$tableName} {$joinAlias} ON e.event_id = {$joinAlias}.entity_id";
      case 'Participant':
        return "LEFT JOIN {$tableName} {$joinAlias} ON e.id = {$joinAlias}.entity_id";
      default:
        return NULL;
    }
  }

  /**
   * Build JOIN for activity-based reminders
   */
  private static function buildActivityJoin($entity, $tableName, $joinAlias) {
    switch ($entity) {
      case 'Contact':
        return "LEFT JOIN {$tableName} {$joinAlias} ON e.contact_id = {$joinAlias}.entity_id";
      case 'Activity':
        return "LEFT JOIN {$tableName} {$joinAlias} ON e.id = {$joinAlias}.entity_id";
      default:
        return NULL;
    }
  }

  /**
   * Build generic JOIN
   */
  private static function buildGenericJoin($entity, $tableName, $joinAlias) {
    return "LEFT JOIN {$tableName} {$joinAlias} ON e.id = {$joinAlias}.entity_id";
  }

  /**
   * Build WHERE clause for custom field condition
   */
  public static function buildCustomFieldWhere($joinAlias, $columnName, $operator, $value, $joinIndex) {
    $params = [];

    switch ($operator) {
      case '=':
      case '!=':
      case '>':
      case '<':
      case '>=':
      case '<=':
        return [
          'clause' => "{$joinAlias}.{$columnName} {$operator} %{$joinIndex}",
          'params' => [$joinIndex => [$value, 'String']]
        ];

      case 'LIKE':
      case 'NOT LIKE':
        return [
          'clause' => "{$joinAlias}.{$columnName} {$operator} %{$joinIndex}",
          'params' => [$joinIndex => ['%' . $value . '%', 'String']]
        ];

      case 'IS NULL':
        return [
          'clause' => "{$joinAlias}.{$columnName} IS NULL",
          'params' => []
        ];

      case 'IS NOT NULL':
        return [
          'clause' => "{$joinAlias}.{$columnName} IS NOT NULL",
          'params' => []
        ];

      case 'IN':
      case 'NOT IN':
        if (is_array($value) && !empty($value)) {
          $inClause = [];
          $params = [];
          $paramIndex = $joinIndex;

          foreach ($value as $val) {
            $inClause[] = "%{$paramIndex}";
            $params[$paramIndex] = [$val, 'String'];
            $paramIndex += 100;
          }

          return [
            'clause' => "{$joinAlias}.{$columnName} {$operator} (" . implode(',', $inClause) . ")",
            'params' => $params
          ];
        }
        break;
    }

    return NULL;
  }
  
}

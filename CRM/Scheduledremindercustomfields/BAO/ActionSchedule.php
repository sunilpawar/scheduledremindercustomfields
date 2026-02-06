<?php

/**
 * Custom Action Schedule class with extended filtering capabilities
 */
class CRM_Scheduledremindercustomfields_BAO_ActionSchedule extends CRM_Core_BAO_ActionSchedule {

  /**
   * Override the sendMailings method to include custom field filtering
   */
  public static function sendMailings($mappingID = NULL, $now = NULL) {
    // Get all scheduled reminders
    $reminders = self::getScheduledReminders($mappingID);

    foreach ($reminders as $reminder) {
      // Check if this reminder has custom field conditions
      if (!empty($reminder['custom_field_filter_data'])) {
        $customConditions = unserialize($reminder['custom_field_filter_data']);

        // Apply custom field filtering to the recipient query
        $modifiedReminder = self::applyCustomFieldFilter($reminder, $customConditions);

        // Process the modified reminder
        self::processScheduledReminder($modifiedReminder, $now);
      }
      else {
        // Process normally
        self::processScheduledReminder($reminder, $now);
      }
    }
  }

  /**
   * Apply custom field filtering to scheduled reminder
   */
  private static function applyCustomFieldFilter($reminder, $customConditions) {
    if (empty($customConditions) || !is_array($customConditions)) {
      return $reminder;
    }

    // Build additional WHERE clauses for custom field filtering
    $customWhereClause = [];
    $customWhereParams = [];
    $paramIndex = 1000; // Start high to avoid conflicts

    foreach ($customConditions as $condition) {
      if (empty($condition['entity']) || empty($condition['field_id']) || empty($condition['operator'])) {
        continue;
      }

      $entity = $condition['entity'];
      $fieldId = $condition['field_id'];
      $operator = $condition['operator'];
      $value = CRM_Utils_Array::value('value', $condition);

      // Get custom field information
      $customField = self::getCustomFieldInfo($fieldId);
      if (!$customField) {
        continue;
      }

      // Build the custom field column name
      $customTableName = $customField['table_name'];
      $customColumnName = $customField['column_name'];

      // Build WHERE clause based on operator and value
      $whereClause = self::buildCustomFieldWhereClause(
        $customTableName,
        $customColumnName,
        $operator,
        $value,
        $paramIndex,
        $customWhereParams
      );

      if ($whereClause) {
        $customWhereClause[] = $whereClause;
      }

      $paramIndex += 10; // Increment for next condition
    }

    // Add custom WHERE clauses to the reminder configuration
    if (!empty($customWhereClause)) {
      $reminder['custom_where_clause'] = implode(' AND ', $customWhereClause);
      $reminder['custom_where_params'] = $customWhereParams;
    }

    return $reminder;
  }

  /**
   * Build WHERE clause for custom field condition
   */
  private static function buildCustomFieldWhereClause($tableName, $columnName, $operator, $value, &$paramIndex, &$params) {
    $whereClause = '';

    switch ($operator) {
      case '=':
      case '!=':
      case '>':
      case '<':
      case '>=':
      case '<=':
      case 'LIKE':
      case 'NOT LIKE':
        $paramName = 'customFieldValue' . $paramIndex;
        $whereClause = "{$tableName}.{$columnName} {$operator} %{$paramIndex}";
        $params[$paramIndex] = [$value, 'String'];
        $paramIndex++;
        break;

      case 'IS NULL':
        $whereClause = "{$tableName}.{$columnName} IS NULL";
        break;

      case 'IS NOT NULL':
        $whereClause = "{$tableName}.{$columnName} IS NOT NULL";
        break;

      case 'IN':
      case 'NOT IN':
        if (is_array($value) && !empty($value)) {
          $inValues = [];
          foreach ($value as $val) {
            $inValues[] = "%{$paramIndex}";
            $params[$paramIndex] = [trim($val), 'String'];
            $paramIndex++;
          }
          $whereClause = "{$tableName}.{$columnName} {$operator} (" . implode(',', $inValues) . ")";
        }
        break;
    }

    return $whereClause;
  }

  /**
   * Get custom field information
   */
  private static function getCustomFieldInfo($fieldId) {
    static $customFieldCache = [];

    if (!isset($customFieldCache[$fieldId])) {
      try {
        $result = civicrm_api3('CustomField', 'getsingle', [
          'id' => $fieldId,
          'return' => ['table_name', 'column_name', 'data_type', 'html_type'],
        ]);
        $customFieldCache[$fieldId] = $result;
      }
      catch (Exception $e) {
        $customFieldCache[$fieldId] = FALSE;
      }
    }

    return $customFieldCache[$fieldId];
  }

  /**
   * Get scheduled reminders with custom field data
   */
  private static function getScheduledReminders($mappingID = NULL) {
    $params = [
      'is_active' => 1,
      'return' => ['*', 'custom_field_filter_data'],
      'options' => ['limit' => 0],
    ];

    if ($mappingID) {
      $params['mapping_id'] = $mappingID;
    }

    try {
      $result = civicrm_api3('ActionSchedule', 'get', $params);
      return $result['values'];
    }
    catch (Exception $e) {
      CRM_Core_Error::debug_log_message('Error retrieving scheduled reminders: ' . $e->getMessage());
      return [];
    }
  }

  /**
   * Process individual scheduled reminder with custom field filtering
   */
  private static function processScheduledReminder($reminder, $now = NULL) {
    // This would integrate with the core scheduled reminder processing
    // but include the custom WHERE clauses and parameters

    // For now, call the parent method
    // In a full implementation, this would be a more complex override
    // of the core reminder processing logic

    if (!empty($reminder['custom_where_clause'])) {
      // Store custom conditions in global variable for use in query building
      $GLOBALS['civicrm_scheduled_reminder_custom_conditions'] = [
        'where_clause' => $reminder['custom_where_clause'],
        'where_params' => $reminder['custom_where_params'],
      ];
    }

    // Process the reminder (this would need integration with core CiviCRM)
    // parent::sendMailings($reminder['mapping_id'], $now);
  }

  public static function addAdditionalElements($form) {
    // Get all available entities that support scheduled reminders
    $entities = [
      'Contact' => ts('Contact'),
      'Activity' => ts('Activity'),
      'Event' => ts('Event'),
      'Membership' => ts('Membership'),
      'Contribution' => ts('Contribution'),
      'Participant' => ts('Participant')
    ];

    // Add custom field filter section
    $form->add('select', 'custom_field_entity', ts('Custom Field Entity'),
      ['' => ts('- Select Entity -')] + $entities, FALSE,
      ['class' => 'crm-select2', 'id' => 'custom_field_entity']
    );

    // Custom field selection (will be populated via AJAX)
    $form->add('select', 'custom_field_id', ts('Custom Field'),
      ['' => ts('- Select Custom Field -')], FALSE,
      ['class' => 'crm-select2', 'id' => 'custom_field_id']
    );

    // Operator selection
    $operators = [
      '=' => ts('Equals'),
      '!=' => ts('Not Equals'),
      'LIKE' => ts('Contains'),
      'NOT LIKE' => ts('Does Not Contain'),
      '>' => ts('Greater Than'),
      '<' => ts('Less Than'),
      '>=' => ts('Greater Than or Equal'),
      '<=' => ts('Less Than or Equal'),
      'IS NULL' => ts('Is Empty'),
      'IS NOT NULL' => ts('Is Not Empty'),
      'IN' => ts('Is One Of'),
      'NOT IN' => ts('Is Not One Of')
    ];

    $form->add('select', 'custom_field_operator', ts('Operator'),
      ['' => ts('- Select Operator -')] + $operators, FALSE,
      ['class' => 'crm-select2', 'id' => 'custom_field_operator']
    );

    // Value input (dynamic based on field type)
    $form->add('text', 'custom_field_value', ts('Value'),
      ['id' => 'custom_field_value', 'class' => 'form-control']
    );

    // Multiple values input (for IN/NOT IN operators)
    $form->add('text', 'custom_field_values', ts('Values (comma-separated)'),
      ['id' => 'custom_field_values', 'class' => 'form-control', 'style' => 'display:none;']
    );

    // Date picker for date fields
    $form->add('datepicker', 'custom_field_date_value', ts('Date Value'),
      ['id' => 'custom_field_date_value', 'style' => 'display:none;']
    );

    // Multiple custom field conditions support
    $form->add('hidden', 'custom_field_conditions', '');

    // Add/Remove condition buttons
    $form->assign('custom_field_operators', $operators);
    $form->assign('custom_field_entities', $entities);
  }
}

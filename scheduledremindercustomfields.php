<?php

require_once 'scheduledremindercustomfields.civix.php';

use CRM_Scheduledremindercustomfields_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function scheduledremindercustomfields_civicrm_config(&$config): void {
  _scheduledremindercustomfields_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function scheduledremindercustomfields_civicrm_install(): void {
  _scheduledremindercustomfields_civix_civicrm_install();

  // Add custom field filter column to action_schedule table
  CRM_Scheduledremindercustomfields_Utils_Schema::addCustomFieldColumn();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function scheduledremindercustomfields_civicrm_enable(): void {
  _scheduledremindercustomfields_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_buildForm().
 */
function scheduledremindercustomfields_civicrm_buildForm($formName, &$form) {
  if ($formName === 'CRM_Admin_Form_ScheduleReminders') {
    // Add custom field elements to the scheduled reminder form
    CRM_Scheduledremindercustomfields_BAO_ActionSchedule::addAdditionalElements($form);

    // Add JavaScript for dynamic field loading
    CRM_Core_Resources::singleton()->addScriptFile('com.skvare.scheduledremindercustomfields', 'js/scheduled_reminder_custom_fields.js');

    // Add CSS for styling
    CRM_Core_Resources::singleton()->addStyleFile('com.skvare.scheduledremindercustomfields', 'css/scheduled_reminder_custom_fields.css');
  }
}

/**
 * Implements hook_civicrm_alterContent() specifically for ActionSchedule forms
 */
function scheduledremindercustomfields_civicrm_alterContent(&$content, $context, $tplName, &$object) {
  // Handle the scheduled reminder form
  if ($tplName === 'CRM/Admin/Form/ScheduleReminders.tpl') {
    // Inject our custom JavaScript and CSS
    /*
    CRM_Core_Resources::singleton()
      ->addScriptFile('com.skvare.scheduledremindercustomfields', 'js/scheduled_reminder_custom_fields.js')
      ->addStyleFile('com.skvare.scheduledremindercustomfields', 'css/scheduled_reminder_custom_fields.css');
    */
    // Inject custom field section HTML
    //$customFieldSection = CRM_Scheduledremindercustomfields_Utils_HTML::getCustomFieldSectionHTML();
    $customFieldSection = _scheduledremindercustomfields_get_custom_field_html();
    // echo $content;
    // echo '<br/>---------<br/>';
    //echo $customFieldSection;
    //exit;
    // Find insertion point and inject our content
    $insertAfter = '<tr class="crm-scheduleReminder-form-block-active">';
    $content = str_replace($insertAfter, $customFieldSection . $insertAfter,
      $content);
    //echo $content; exit;
    //str_replace('', '', '    ');
  }
}

/**
 * Implements hook_civicrm_alterContent().
 */
function scheduledremindercustomfields_civicrm_alterContent_2(&$content, $context, $tplName, &$object) {
  if ($tplName === 'CRM/Admin/Form/ScheduleReminders.tpl') {
    // Inject our custom field HTML into the form
    $customFieldHTML = _scheduledremindercustomfields_get_custom_field_html();

    // Insert after the recipient section
    $insertPoint = '<div class="crm-accordion-wrapper crm-accordion_title-accordion crm-accordion-closed">';
    $content = str_replace($insertPoint, $customFieldHTML . $insertPoint, $content);
  }
}



/**
 * Generate HTML for custom field section
 */

/**
 * Generate HTML for custom field section
 */
function _scheduledremindercustomfields_get_custom_field_html() {
  return '<tr><td></td><td>
    <div class="crm-accordion-wrapper crm-accordion_title-accordion crm-accordion-closed" id="custom-field-filters">
      <div class="crm-accordion-header">
        <div class="crm-accordion-pointer"></div>
        ' . ts('Custom Field Filters') . '
      </div>
      <div class="crm-accordion-body">
        <div class="crm-section">
          <div class="label">
            <label for="custom_field_entity">' . ts('Filter by Custom Fields') . '</label>
          </div>
          <div class="content">
            <div id="custom-field-conditions-container">
              <div class="custom-field-condition" data-condition-index="0">
                <!-- Logic operator selector (hidden for first condition) -->
                <div class="logic-operator-row" style="display:none; margin-top: 10px; text-align: center;">
                  <select name="logic_operator" class="logic-operator" style="width: 100px;">
                    <option value="AND">' . ts('AND') . '</option>
                    <option value="OR">' . ts('OR') . '</option>
                  </select>
                </div>
                <div class="custom-field-row">
                  <select name="custom_field_entity" id="custom_field_entity" class="custom-field-entity">
                    <option value="">' . ts('- Select Entity -') . '</option>
                  </select>
                  <select name="custom_field_id" id="custom_field_id" class="custom-field-id">
                    <option value="">' . ts('- Select Custom Field -') . '</option>
                  </select>
                  <select name="custom_field_operator" id="custom_field_operator" class="custom-field-operator">
                    <option value="">' . ts('- Select Operator -') . '</option>
                  </select>
                  <input type="text" name="custom_field_value" id="custom_field_value" class="custom-field-value form-control" placeholder="' . ts('Enter value') . '" />
                  <button type="button" class="btn btn-sm btn-danger remove-condition" style="display:none;">
                    <i class="crm-i fa-trash"></i>
                  </button>
                </div>
              </div>
            </div>
            <div class="custom-field-actions">
              <button type="button" id="add-custom-field-condition" class="btn btn-sm btn-primary">
                <i class="crm-i fa-plus"></i> ' . ts('Add Another Condition') . '
              </button>
            </div>
            <div class="help">
              <p>' . ts('Add custom field conditions to further filter recipients. Use AND/OR logic to combine multiple conditions.') . '</p>
            </div>
          </div>
        </div>
      </div>
    </div></td></tr>';
}

/**
 * Implements hook_civicrm_apiWrappers().
 */
function scheduledremindercustomfields_civicrm_apiWrappers(&$wrappers, $apiRequest) {
  // Wrap ActionSchedule API to handle custom field filtering
  if ($apiRequest['entity'] === 'ActionSchedule') {
    $wrappers[] = new CRM_Scheduledremindercustomfields_API_Wrapper_ActionSchedule();
  }
}

/**
 * AJAX callback to get custom fields for an entity
 */
function scheduledremindercustomfields_civicrm_pageRun(&$page) {
  if (get_class($page) === 'CRM_Admin_Page_ScheduleReminders') {
    // Add AJAX endpoint for getting custom fields
    $runner = CRM_Core_Resources::singleton();
    $runner->addVars('scheduledReminderCustomFields', [
      'ajaxUrl' => CRM_Utils_System::url('civicrm/ajax/custom-fields', NULL, TRUE, NULL, FALSE),
    ]);
  }
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
function scheduledremindercustomfields_civicrm_xmlMenu(&$files) {
  $files[] = E::path('xml/Menu/scheduledremindercustomfields.xml');
}
 */

/**
 * Implements hook_civicrm_alterAPIPermissions().
 */
function scheduledremindercustomfields_civicrm_alterAPIPermissions($entity, $action, &$params, &$permissions) {
  // Add permissions for our custom API endpoints
  $permissions['scheduled_reminder_custom_fields']['get_custom_fields'] = ['administer CiviCRM'];
}


/**
 * Hook implementation to replace ActionMapping classes
 */
function scheduledremindercustomfields_civicrm_actionMappingLoad(&$mappings) {
  // Replace core ActionMapping classes with our extended versions
  foreach ($mappings as $key => $mapping) {
    if ($mapping instanceof CRM_Member_ActionMapping) {
      $mappings[$key] = new CRM_Scheduledremindercustomfields_ActionMapping_Member();
    }
    elseif ($mapping instanceof CRM_Event_ActionMapping) {
      $mappings[$key] = new CRM_Scheduledremindercustomfields_ActionMapping_Event();
    }
    elseif ($mapping instanceof CRM_Activity_ActionMapping) {
      $mappings[$key] = new CRM_Scheduledremindercustomfields_ActionMapping_Activity();
    }
  }
}

/**
 * Alternative approach: Hook into existing ActionMapping query building
 * This doesn't require replacing the entire ActionMapping classes
 */
function scheduledremindercustomfields_civicrm_alterActionScheduleQuery($mapping, $phase, $schedule, $query) {
  // Check if this schedule has custom field conditions
  if (empty($schedule->custom_field_filter_data)) {
    return;
  }

  $customConditions = json_decode($schedule->custom_field_filter_data, TRUE);
  if (!is_array($customConditions) || empty($customConditions)) {
    return;
  }

  // Apply custom field filtering
  CRM_Scheduledremindercustomfields_Utils_QueryModifier::applyCustomFieldFilters(
    $query, $customConditions, $mapping, $phase
  );
}

/**
 * Alternative approach: Hook into query building without extending classes
 */
function scheduledremindercustomfields_civicrm_alterActionScheduleQuery_2(&$query, $schedule, $mapping) {
  // This hook would need to be added to CiviCRM core
  // Alternative approach for adding custom field filtering

  if (!empty($schedule->custom_field_filter_data)) {
    $customConditions = json_decode($schedule->custom_field_filter_data, TRUE);

    if (!is_array($customConditions)) {
      return;
    }

    $joinIndex = 1000; // Start high to avoid conflicts
    foreach ($customConditions as $condition) {
      if (empty($condition['entity']) || empty($condition['field_id']) || empty($condition['operator'])) {
        continue;
      }

      try {
        $fieldInfo = civicrm_api3('CustomField', 'getsingle', [
          'id' => $condition['field_id'],
          'return' => ['table_name', 'column_name', 'data_type', 'html_type']
        ]);
      } catch (Exception $e) {
        continue;
      }

      $entity = $condition['entity'];
      $operator = $condition['operator'];
      $value = CRM_Utils_Array::value('value', $condition);

      $tableName = $fieldInfo['table_name'];
      $columnName = $fieldInfo['column_name'];
      $joinAlias = "custom_field_{$joinIndex}";

      // Add JOIN based on entity and mapping type
      $joinClause = CRM_Scheduledremindercustomfields_Utils_Query::buildCustomFieldJoin(
        $entity, $tableName, $joinAlias, $mapping, $joinIndex
      );

      if ($joinClause) {
        $query->join($joinAlias, $joinClause);

        // Add WHERE condition
        $whereClause = CRM_Scheduledremindercustomfields_Utils_Query::buildCustomFieldWhere(
          $joinAlias, $columnName, $operator, $value, $joinIndex
        );

        if ($whereClause) {
          $query->where($whereClause['clause'], $whereClause['params']);
        }
      }

      $joinIndex++;
    }
  }
}


/**
 * Additional hook implementations for integrating with ActionMapping classes
 * Add these to your main extension file (scheduledremindercustomfields.php)
 */

/**
 * Implements hook_civicrm_actionMapping().
 *
 * This hook allows us to replace the core ActionMapping classes with our extended versions
 */
function scheduledremindercustomfields_civicrm_actionMapping(&$actionMappings) {
  // Remove core mappings and replace with our extended versions
  $coreClasses = [
    'CRM_Member_ActionMapping',
    'CRM_Event_ActionMapping',
    'CRM_Activity_ActionMapping'
  ];

  // Remove core mappings
  foreach ($actionMappings as $key => $mapping) {
    $className = get_class($mapping);
    if (in_array($className, $coreClasses)) {
      unset($actionMappings[$key]);
    }
  }

  // Add our extended mappings
  $actionMappings[] = new CRM_Scheduledremindercustomfields_ActionMapping_Member();
  $actionMappings[] = new CRM_Scheduledremindercustomfields_ActionMapping_Event();
  $actionMappings[] = new CRM_Scheduledremindercustomfields_ActionMapping_Activity();
}

/**
 * Implements hook_civicrm_queryObjects().
 * This allows us to modify queries before they're executed
 */
function scheduledremindercustomfields_civicrm_queryObjects(&$queryObjects, $type) {
  if ($type === 'ActionSchedule') {
    // Add our query modifier to the query objects
    $queryObjects[] = new CRM_Scheduledremindercustomfields_Utils_QueryModifier();
  }
}

function scheduledremindercustomfields_civicrm_postProcess($formName, &$form) {
  if ($formName === 'CRM_Admin_Form_ScheduleReminders') {
    // Handle saving of custom field conditions when the form is submitted
    if (!empty($_POST['custom_field_entity']) && !empty($_POST['custom_field_id']) && !empty($_POST['custom_field_operator'])) {
      $conditions = [];
      $conditions[] = [
        'entity' => $_POST["custom_field_entity"],
        'field_id' => $_POST["custom_field_id"],
        'operator' => $_POST["custom_field_operator"],
        'value' => $_POST["custom_field_value"] ?? '',
      ];
      $index = 1;
      while (isset($_POST["custom_field_entity_$index"])) {
        if (!empty($_POST["custom_field_entity_$index"]) && !empty($_POST["custom_field_id_$index"]) && !empty($_POST["custom_field_operator_$index"])) {
          $conditions[] = [
            'entity' => $_POST["custom_field_entity_$index"],
            'field_id' => $_POST["custom_field_id_$index"],
            'operator' => $_POST["custom_field_operator_$index"],
            'value' => $_POST["custom_field_value_$index"] ?? '',
          ];
        }
        $index++;
      }
      // Save to database
      CRM_Core_DAO::executeQuery(
        "UPDATE civicrm_action_schedule SET custom_field_filter_data = %1 WHERE id = %2",
        [
          1 => [json_encode($conditions), 'String'],
          2 => [CRM_Utils_Request::retrieve('id', 'Positive', $form), 'Integer']
        ]
      );
    }
  }
}

/**
 * Implements hook_civicrm_postSave_ACTION_SCHEDULE()
 * This ensures our custom field data is properly saved
 */
function scheduledremindercustomfields_civicrm_postSave_ACTION_SCHEDULE(&$objectName, &$objectRef) {
  // Handle saving of custom field conditions
  if (!empty($_POST['custom_field_conditions'])) {
    $conditions = $_POST['custom_field_conditions'];

    // If it's JSON string, decode it
    if (is_string($conditions)) {
      $conditions = json_decode($conditions, TRUE);
    }

    if (is_array($conditions)) {
      // Save to database
      CRM_Core_DAO::executeQuery(
        "UPDATE civicrm_action_schedule SET custom_field_filter_data = %1 WHERE id = %2",
        [
          1 => [serialize($conditions), 'String'],
          2 => [$objectRef->id, 'Integer']
        ]
      );
    }
  }
}

/**
 * Implements hook_civicrm_uninstall().
 */
function scheduledremindercustomfields_civicrm_uninstall() {
  _scheduledremindercustomfields_civix_civicrm_uninstall();

  // Remove custom field filter column
  CRM_Scheduledremindercustomfields_Utils_Schema::removeCustomFieldColumn();
}

/**
 * Implements hook_civicrm_apiWrappers().
 */
function scheduledremindercustomfields_civicrm_entityTypes(&$entityTypes) {
  $civiVersion = CRM_Utils_System::version();
  $entity = 'CRM_Core_DAO_ActionSchedule';
  if (version_compare($civiVersion, '5.75.0') >= 0) {
    $entity = 'ActionSchedule';
  }
  $entityTypes[$entity]['fields_callback'][]
    = function ($class, &$fields) {
    $fields['custom_field_filter_data'] = [
      'name' => 'custom_field_filter_data',
      'type' => CRM_Utils_Type::T_TEXT,
      'title' => ts('Custom Field Filter Data'),
      'description' => 'Custom Field Filter Data',
      'table_name' => 'civicrm_action_schedule',
      'entity' => 'ActionSchedule',
      'bao' => 'CRM_Core_BAO_ActionSchedule',
      'localizable' => 0,
      'html' => [
        'label' => ts("Custom Field Filter Data"),
      ]
    ];
  };
}
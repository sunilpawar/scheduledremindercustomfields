<?php

/**
 * AJAX endpoint for getting custom fields
 */
class CRM_Scheduledremindercustomfields_Page_AJAX {

  /**
   * Get custom fields for an entity type
   */
  public static function getCustomFields() {
    $entityType = CRM_Utils_Request::retrieve('entity', 'String');

    if (!$entityType) {
      CRM_Utils_JSON::output(['error' => 'Entity type required']);
    }

    try {
      $result = civicrm_api3('CustomField', 'get', [
        'sequential' => 1,
        'is_active' => 1,
        'extends' => $entityType,
        'return' => ['id', 'label', 'data_type', 'html_type', 'custom_group_id'],
        'options' => ['limit' => 0, 'sort' => 'label ASC']
      ]);

      // Get custom group names
      $customGroups = [];
      foreach ($result['values'] as &$field) {
        if (!isset($customGroups[$field['custom_group_id']])) {
          try {
            $group = civicrm_api3('CustomGroup', 'getsingle', [
              'id' => $field['custom_group_id'],
              'return' => 'title'
            ]);
            $customGroups[$field['custom_group_id']] = $group['title'];
          }
          catch (Exception $e) {
            $customGroups[$field['custom_group_id']] = 'Unknown Group';
          }
        }
        $field['group_title'] = $customGroups[$field['custom_group_id']];
      }

      CRM_Utils_JSON::output($result);
    }
    catch (Exception $e) {
      CRM_Utils_JSON::output(['error' => $e->getMessage()]);
    }
  }
}

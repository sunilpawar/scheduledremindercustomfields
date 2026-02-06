<?php

/**
 * Database schema modifications
 * Add this to your upgrader or install script
 */
class CRM_Scheduledremindercustomfields_Utils_Schema {

  /**
   * Add custom field filter data column to action_schedule table
   */
  public static function addCustomFieldColumn() {
    $sql = "ALTER TABLE civicrm_action_schedule
            ADD COLUMN custom_field_filter_data TEXT DEFAULT NULL
            COMMENT 'Serialized custom field filter conditions'";

    // Check if column already exists
    if (!CRM_Core_BAO_SchemaHandler::checkIfFieldExists('civicrm_action_schedule', 'custom_field_filter_data', FALSE)) {
      try {
        CRM_Core_DAO::executeQuery($sql);
        CRM_Core_Error::debug_log_message('Scheduled Reminder Custom Fields: Added custom_field_filter_data column');
      }
      catch (Exception $e) {
        CRM_Core_Error::debug_log_message('Scheduled Reminder Custom Fields: Error adding column: ' . $e->getMessage());
        throw $e;
      }
    }
  }

  /**
   * Remove custom field filter data column
   */
  public static function removeCustomFieldColumn() {
    if (CRM_Core_BAO_SchemaHandler::checkIfFieldExists('civicrm_action_schedule', 'custom_field_filter_data', FALSE)) {
      try {
        $sql = "ALTER TABLE civicrm_action_schedule DROP COLUMN custom_field_filter_data";
        CRM_Core_DAO::executeQuery($sql);
        CRM_Core_Error::debug_log_message('Scheduled Reminder Custom Fields: Removed custom_field_filter_data column');
      }
      catch (Exception $e) {
        CRM_Core_Error::debug_log_message('Scheduled Reminder Custom Fields: Error removing column: ' . $e->getMessage());
        throw $e;
      }
    }
  }
}

<?php

use CRM_Scheduledremindercustomfields_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Scheduledremindercustomfields_Upgrader extends CRM_Extension_Upgrader_Base {

  /**
   * Example: Run an external SQL script when the module is installed.
   */
  public function install() {
    // Add custom field filter column to action_schedule table
    $this->addCustomFieldFilterColumn();

    // Create indexes for better performance
    $this->createIndexes();
  }

  /**
   * Example: Work with entities usually not available during the install step.
   */
  public function postInstall() {
    // Create default custom field mappings if needed
    $this->createDefaultMappings();
  }

  /**
   * Example: Run an external SQL script when the module is uninstalled.
   */
  public function uninstall() {
    // Remove custom field filter column
    $this->removeCustomFieldFilterColumn();

    // Clean up any remaining data
    $this->cleanupExtensionData();
  }

  /**
   * Example: Run a simple query when a module is enabled.
   */
  public function enable() {
    // Ensure column exists (in case of upgrades)
    //$this->addCustomFieldFilterColumn();

    // Refresh action mappings
    // $this->refreshActionMappings();
  }

  /**
   * Example: Run a simple query when a module is disabled.
   */
  public function disable() {
    // Clear any cached data
    $this->clearCaches();
  }

  /**
   * Upgrade to version 1.1: Add performance indexes
   */
  public function upgrade_1001() {
    $this->ctx->log->info('Applying update 1001: Adding performance indexes');
    $this->createIndexes();
    return TRUE;
  }

  /**
   * Upgrade to version 1.2: Add support for additional operators
   */
  public function upgrade_1002() {
    $this->ctx->log->info('Applying update 1002: Enhanced operator support');
    // Any schema changes for new operators would go here
    return TRUE;
  }

  /**
   * Add custom field filter column to civicrm_action_schedule table
   */
  private function addCustomFieldFilterColumn() {
    // Check if column already exists
    if (!CRM_Core_BAO_SchemaHandler::checkIfFieldExists('civicrm_action_schedule', 'custom_field_filter_data', FALSE)) {
      $sql = "ALTER TABLE civicrm_action_schedule
              ADD COLUMN custom_field_filter_data TEXT DEFAULT NULL
              COMMENT 'Serialized custom field filter conditions'";

      try {
        CRM_Core_DAO::executeQuery($sql);
        $this->ctx->log->info('Added custom_field_filter_data column to civicrm_action_schedule');
      }
      catch (Exception $e) {
        $this->ctx->log->error('Failed to add custom_field_filter_data column: ' . $e->getMessage());
        throw $e;
      }
    }
  }

  /**
   * Remove custom field filter column
   */
  private function removeCustomFieldFilterColumn() {
    if (CRM_Core_BAO_SchemaHandler::checkIfFieldExists('civicrm_action_schedule', 'custom_field_filter_data', FALSE)) {
      try {
        $sql = "ALTER TABLE civicrm_action_schedule DROP COLUMN custom_field_filter_data";
        CRM_Core_DAO::executeQuery($sql);
        $this->ctx->log->info('Removed custom_field_filter_data column from civicrm_action_schedule');
      }
      catch (Exception $e) {
        $this->ctx->log->error('Failed to remove custom_field_filter_data column: ' . $e->getMessage());
        // Don't throw on uninstall - log error but continue
      }
    }
  }

  /**
   * Create database indexes for better performance
   */
  private function createIndexes() {
    $indexes = [
      // Index on custom field filter data for faster lookups
      [
        'table' => 'civicrm_action_schedule',
        'name' => 'idx_custom_field_filter_data',
        'sql' => "CREATE INDEX idx_custom_field_filter_data ON civicrm_action_schedule (custom_field_filter_data(100))"
      ],
      // Additional indexes on commonly filtered custom field tables could be added here
    ];

    foreach ($indexes as $index) {
      try {
        // Check if index already exists
        if (!CRM_Core_BAO_SchemaHandler::checkIfIndexExists($index['table'], $index['name'])) {
          CRM_Core_DAO::executeQuery($index['sql']);
          //$this->ctx->log->info("Created index {$index['name']} on {$index['table']}");
        }
        else {
          //$this->ctx->log->info("Index {$index['name']} already exists on
          // {$index['table']}");
        }
      }
      catch (Exception $e) {
        // $this->ctx->log->warning("Failed to create index {$index['name']}: ". $e->getMessage());
        // Continue with other indexes
      }
    }
  }

  /**
   * Create default custom field mappings
   */
  private function createDefaultMappings() {
    // This could create some default configurations or examples
    // For now, just log that we're setting up defaults
    //$this->ctx->log->info('Setting up default custom field mappings');

    // Example: Create a sample scheduled reminder with custom field filtering
    // This would be helpful for demonstrating the functionality
    // $this->createSampleReminder();
  }

  /**
   * Create a sample scheduled reminder to demonstrate functionality
   */
  private function createSampleReminder() {
    try {
      // Check if we already have a sample reminder
      $existing = civicrm_api3('ActionSchedule', 'get', [
        'title' => 'Sample: Custom Field Demo Reminder',
      ]);

      if ($existing['count'] == 0) {
        // Create sample reminder
        $result = civicrm_api3('ActionSchedule', 'create', [
          'title' => 'Sample: Custom Field Demo Reminder',
          'mapping_id' => 1, // Membership mapping
          'entity_value' => 'membership_type_id',
          'entity_status' => '1,2', // New, Current
          'start_action_date' => 'end_date',
          'start_action_offset' => 30,
          'start_action_unit' => 'day',
          'start_action_condition' => 'before',
          'subject' => 'Sample Custom Field Reminder',
          'body_text' => 'This is a sample reminder created by the Custom Field extension.',
          'is_active' => 0, // Inactive by default
          'custom_field_filter_data' => serialize([
            [
              'entity' => 'Contact',
              'field_id' => '1', // This would need to be a real custom field ID
              'operator' => '=',
              'value' => 'Sample Value'
            ]
          ])
        ]);

        $this->ctx->log->info('Created sample reminder with ID: ' . $result['id']);
      }
    }
    catch (Exception $e) {
      $this->ctx->log->warning('Could not create sample reminder: ' . $e->getMessage());
      // Don't fail installation if sample creation fails
    }
  }

  /**
   * Refresh action mappings to ensure our custom mappings are loaded
   */
  private function refreshActionMappings() {
    // Clear any cached mappings
    if (method_exists('CRM_Core_BAO_ActionSchedule', 'getMappings')) {
      // Clear static cache if it exists
      $reflectionClass = new ReflectionClass('CRM_Core_BAO_ActionSchedule');
      if ($reflectionClass->hasProperty('_mappings')) {
        $mappingsProperty = $reflectionClass->getProperty('_mappings');
        $mappingsProperty->setAccessible(TRUE);
        $mappingsProperty->setValue(NULL);
      }
    }

    $this->ctx->log->info('Refreshed action schedule mappings');
  }

  /**
   * Clear relevant caches
   */
  private function clearCaches() {
    // Clear CiviCRM caches that might be affected
    $caches = [
      'metadata',
      'fields',
      'menu',
      'js_strings',
    ];

    foreach ($caches as $cache) {
      try {
        civicrm_api3('System', 'flush', ['triggers' => 0, 'session' => 0]);
        $this->ctx->log->info("Cleared {$cache} cache");
      }
      catch (Exception $e) {
        $this->ctx->log->warning("Failed to clear {$cache} cache: " . $e->getMessage());
      }
    }
  }

  /**
   * Clean up any extension-specific data
   */
  private function cleanupExtensionData() {
    try {
      // Remove any sample reminders we created
      $sampleReminders = civicrm_api3('ActionSchedule', 'get', [
        'title' => ['LIKE' => 'Sample: Custom Field%'],
      ]);

      foreach ($sampleReminders['values'] as $reminder) {
        civicrm_api3('ActionSchedule', 'delete', ['id' => $reminder['id']]);
        $this->ctx->log->info('Removed sample reminder: ' . $reminder['title']);
      }

      // Clear any cached custom field information
      $this->clearCaches();

    }
    catch (Exception $e) {
      $this->ctx->log->warning('Error during cleanup: ' . $e->getMessage());
      // Don't fail uninstall process
    }
  }

  /**
   * Example: Migration for a specific custom field or entity change
   *
   * public function upgrade_4300() {
   * $this->ctx->log->info('Applying update 4300: Migrating existing custom field filters');
   *
   * // Example migration logic for changing how custom field conditions are stored
   * $this->migrateCustomFieldConditions();
   *
   * return TRUE;
   * }
   */

  /**
   * Migrate custom field conditions to new format (example)
   */
  private function migrateCustomFieldConditions() {
    // This is an example of how you might migrate data if the format changes
    $dao = CRM_Core_DAO::executeQuery(
      "SELECT id, custom_field_filter_data FROM civicrm_action_schedule
       WHERE custom_field_filter_data IS NOT NULL"
    );

    while ($dao->fetch()) {
      try {
        $conditions = unserialize($dao->custom_field_filter_data);

        // Perform any necessary data transformation
        $migratedConditions = $this->transformConditions($conditions);

        // Update with migrated data
        CRM_Core_DAO::executeQuery(
          "UPDATE civicrm_action_schedule SET custom_field_filter_data = %1 WHERE id = %2",
          [
            1 => [serialize($migratedConditions), 'String'],
            2 => [$dao->id, 'Integer']
          ]
        );

        $this->ctx->log->info("Migrated conditions for reminder ID: {$dao->id}");

      }
      catch (Exception $e) {
        $this->ctx->log->warning("Failed to migrate conditions for reminder ID {$dao->id}: " . $e->getMessage());
      }
    }
  }

  /**
   * Transform conditions for migration (example)
   */
  private function transformConditions($conditions) {
    // Example transformation logic
    if (!is_array($conditions)) {
      return $conditions;
    }

    foreach ($conditions as &$condition) {
      // Example: Add new fields or transform existing ones
      if (!isset($condition['version'])) {
        $condition['version'] = '1.0';
      }

      // Example: Convert old operator names to new ones
      if (isset($condition['operator'])) {
        $operatorMap = [
          'equals' => '=',
          'not_equals' => '!=',
          'contains' => 'LIKE',
          'not_contains' => 'NOT LIKE'
        ];

        if (isset($operatorMap[$condition['operator']])) {
          $condition['operator'] = $operatorMap[$condition['operator']];
        }
      }
    }

    return $conditions;
  }
}

<?php

/**
 * API Wrapper for ActionSchedule to handle custom field filtering
 */
class CRM_Scheduledremindercustomfields_API_Wrapper_ActionSchedule implements API_Wrapper {

  /**
   * Interface for interpreting api input
   */
  public function fromApiInput($apiRequest) {
    // Process custom field conditions when creating/updating scheduled reminders
    if (in_array($apiRequest['action'], ['create', 'update']) &&
      !empty($apiRequest['params']['custom_field_conditions'])) {

      $conditions = $apiRequest['params']['custom_field_conditions'];
      if (is_string($conditions)) {
        $conditions = json_decode($conditions, TRUE);
      }

      if (is_array($conditions) && !empty($conditions)) {
        // Store custom field conditions in a custom table or as serialized data
        $apiRequest['params']['custom_field_filter_data'] = serialize($conditions);
      }

      // Remove the temporary parameter
      unset($apiRequest['params']['custom_field_conditions']);
    }

    return $apiRequest;
  }

  /**
   * Interface for interpreting api output
   */
  public function toApiOutput($apiRequest, $result) {
    // When retrieving scheduled reminders, decode custom field conditions
    if ($apiRequest['action'] === 'get' && !empty($result['values'])) {
      foreach ($result['values'] as &$reminder) {
        if (!empty($reminder['custom_field_filter_data'])) {
          $reminder['custom_field_conditions'] = unserialize($reminder['custom_field_filter_data']);
        }
      }
    }

    return $result;
  }
}


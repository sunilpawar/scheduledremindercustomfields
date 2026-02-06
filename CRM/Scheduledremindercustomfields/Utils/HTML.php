<?php

/**
 * HTML utility class for generating custom field sections
 */
class CRM_Scheduledremindercustomfields_Utils_HTML {

  /**
   * Generate HTML for custom field section
   */
  public static function getCustomFieldSectionHTML() {
    return '<tr><td></td><td>
    <div class="crm-accordion-wrapper crm-accordion_title-accordion crm-accordion-closed" id="custom-field-filters">
      <div class="crm-accordion-header">
        <div class="crm-accordion-pointer"></div>
        <i class="crm-i fa-filter"></i>
        ' . ts('Custom Field Filters') . '
        <span class="custom-field-status" style="margin-left: auto; font-size: 12px; color: #666;">
          <span id="condition-count">0</span> ' . ts('conditions active') . '
        </span>
      </div>
      <div class="crm-accordion-body">
        <div class="help">
          <p><i class="crm-i fa-info-circle"></i> ' .
      ts('Add custom field conditions to further filter recipients. Multiple conditions are combined with AND logic.') .
      '</p>
        </div>

        <div id="custom-field-conditions-container">
          <!-- Dynamic conditions will be added here -->
        </div>

        <div class="custom-field-actions">
          <button type="button" id="add-custom-field-condition" class="btn btn-sm btn-primary">
            <i class="crm-i fa-plus"></i> ' . ts('Add Custom Field Condition') . '
          </button>
          <button type="button" id="preview-recipients" class="btn btn-sm btn-success" style="margin-left: 10px;">
            <i class="crm-i fa-eye"></i> ' . ts('Preview Recipients') . '
          </button>
        </div>

        <div id="recipient-preview" style="display: none; margin-top: 15px;">
          <div class="preview-header">
            <strong>' . ts('Recipient Preview') . '</strong>
            <span id="recipient-count" style="margin-left: 10px; color: #666;"></span>
          </div>
          <div class="preview-content" style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #f9f9f9;">
            <!-- Preview content will be loaded here -->
          </div>
        </div>

        <input type="hidden" name="custom_field_conditions" id="custom_field_conditions" value="" />
      </div>
    </div></td></tr>';
  }
}

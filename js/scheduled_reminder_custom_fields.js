/**
 * JavaScript for Scheduled Reminder Custom Fields Extension
 */
(function($, CRM) {
  'use strict';

  var customFieldConditions = [];
  var conditionCounter = 0;

  // Initialize the custom field functionality
  function initCustomFields() {
    bindEventHandlers();
    populateEntityOptions();
  }

  // Bind event handlers
  function bindEventHandlers() {
    // Entity selection change
    $(document).on('change', '.custom-field-entity', function() {
      var $row = $(this).closest('.custom-field-condition');
      var entityType = $(this).val();
      loadCustomFields(entityType, $row);
    });

    // Custom field selection change
    $(document).on('change', '.custom-field-id', function() {
      var $row = $(this).closest('.custom-field-condition');
      var fieldId = $(this).val();
      updateOperatorsAndValue(fieldId, $row);
    });

    // Operator change
    $(document).on('change', '.custom-field-operator', function() {
      var $row = $(this).closest('.custom-field-condition');
      var operator = $(this).val();
      toggleValueFields(operator, $row);
    });

    // Add condition button
    $(document).on('click', '#add-custom-field-condition', function() {
      console.log('Adding new custom field condition');
      addCustomFieldCondition();
    });

    // Remove condition button
    $(document).on('click', '.remove-condition', function() {
      $(this).closest('.custom-field-condition').remove();
      updateRemoveButtons();
    });

    // Form submission
    $('form').on('submit', function() {
      collectCustomFieldConditions();
    });
  }

  // Populate entity options based on selected entity in main form
  function populateEntityOptions() {
    var entities = {
      'Contact': 'Contact',
      'Activity': 'Activity',
      'Event': 'Event',
      'Membership': 'Membership',
      'Contribution': 'Contribution',
      'Participant': 'Participant'
    };
    var $entitySelects = $('select.custom-field-entity');
    $entitySelects.each(function() {
      var $select = $(this);
      $select.empty().append('<option value="">' + '- Select Entity -' + '</option>');

      $.each(entities, function(key, value) {
        //$select.append('<option value="' + key + '">' + value + '</option>');
        $($entitySelects).append(
          $('<option></option>')
            .attr('value', key)
            .text(value)
        );
      });

      //$select.select2('destroy').select2();
    });
  }

  // Load custom fields for selected entity
  function loadCustomFields(entityType, $row) {
    if (!entityType) {
      $row.find('select.custom-field-id').empty().append('<option value="">' + '- Select Custom Field -' + '</option>');
      return;
    }

    var $fieldSelect = $row.find('select.custom-field-id');
    $fieldSelect.empty().append('<option value="">' + 'Loading...' + '</option>');

    // AJAX call to get custom fields
    CRM.api3('CustomField', 'get', {
      sequential: 1,
      is_active: 1,
      extends: entityType,
      options: {limit: 0, sort: "label ASC"}
    }).done(function(result) {
      $fieldSelect.empty().append('<option value="">' + '- Select Custom Field -' + '</option>');

      $.each(result.values, function(index, field) {
        var optionText = field.label;
        if (field.custom_group_id) {
          // Get custom group name for context
          CRM.api3('CustomGroup', 'getvalue', {
            sequential: 1,
            return: "title",
            id: field.custom_group_id
          }).done(function(groupTitle) {
            optionText = groupTitle + ': ' + field.label;
            $fieldSelect.append('<option value="' + field.id + '" data-type="' + field.data_type + '" data-html-type="' + field.html_type + '">' + optionText + '</option>');
          });
        } else {
          $fieldSelect.append('<option value="' + field.id + '" data-type="' + field.data_type + '" data-html-type="' + field.html_type + '">' + optionText + '</option>');
        }
      });

      //$fieldSelect.select2('destroy').select2();
    }).fail(function() {
      $fieldSelect.empty().append('<option value="">' + 'Error loading fields' + '</option>');
    });
  }

  // Update operators and value field based on custom field type
  function updateOperatorsAndValue(fieldId, $row) {
    if (!fieldId) {
      return;
    }

    var $fieldOption = $row.find('select.custom-field-id option:selected');
    var dataType = $fieldOption.data('type');
    var htmlType = $fieldOption.data('html-type');

    var $operatorSelect = $row.find('select.custom-field-operator');
    var operators = getOperatorsForFieldType(dataType, htmlType);

    $operatorSelect.empty().append('<option value="">' + '- Select Operator -' + '</option>');
    $.each(operators, function(key, value) {
      $operatorSelect.append('<option value="' + key + '">' + value + '</option>');
    });

    //$operatorSelect.select2('destroy').select2();

    // Setup appropriate input field
    setupValueField(dataType, htmlType, $row, fieldId);
  }

  // Get appropriate operators for field type
  function getOperatorsForFieldType(dataType, htmlType) {
    var operators = {
      '=': 'Equals',
      '!=': 'Not Equals',
      'IS NULL': 'Is Empty',
      'IS NOT NULL': 'Is Not Empty'
    };

    if (dataType === 'String' || dataType === 'Memo') {
      operators['LIKE'] = 'Contains';
      operators['NOT LIKE'] = 'Does Not Contain';
    }

    if (dataType === 'Int' || dataType === 'Float' || dataType === 'Money' || dataType === 'Date') {
      operators['>'] = 'Greater Than';
      operators['<'] = 'Less Than';
      operators['>='] = 'Greater Than or Equal';
      operators['<='] = 'Less Than or Equal';
    }

    if (htmlType === 'Select' || htmlType === 'CheckBox' || htmlType === 'Multi-Select') {
      operators['IN'] = 'Is One Of';
      operators['NOT IN'] = 'Is Not One Of';
    }

    return operators;
  }

  // Setup value input field based on field type
  function setupValueField(dataType, htmlType, $row, fieldId) {
    var $valueField = $row.find('.custom-field-value');
    var $valuesField = $row.find('.custom-field-values');
    var $dateField = $row.find('.custom-field-date-value');

    // Hide all value fields first
    $valueField.hide();
    $valuesField.hide();
    $dateField.hide();

    if (dataType === 'Date') {
      $dateField.show();
    } else if (htmlType === 'Select' || htmlType === 'Multi-Select' || htmlType === 'CheckBox') {
      // Load option values for select fields
      loadFieldOptions(fieldId, $row);
      $valueField.show();
    } else {
      $valueField.show();
    }
  }

  // Load option values for select/checkbox fields
  function loadFieldOptions(fieldId, $row) {
    CRM.api3('OptionValue', 'get', {
      sequential: 1,
      option_group_id: fieldId,
      is_active: 1
    }).done(function(result) {
      var $valueField = $row.find('.custom-field-value');
      var options = [];

      $.each(result.values, function(index, option) {
        options.push({id: option.value, text: option.label});
      });

      $valueField.select2({
        data: options,
        placeholder: 'Select value(s)',
        allowClear: true,
        multiple: true
      });
    });
  }

  // Toggle value fields based on operator
  function toggleValueFields(operator, $row) {
    var $valueField = $row.find('.custom-field-value');
    var $valuesField = $row.find('.custom-field-values');
    var $dateField = $row.find('.custom-field-date-value');

    if (operator === 'IS NULL' || operator === 'IS NOT NULL') {
      $valueField.hide();
      $valuesField.hide();
      $dateField.hide();
    } else if (operator === 'IN' || operator === 'NOT IN') {
      $valueField.hide();
      $valuesField.show();
      $dateField.hide();
    } else {
      // Show the appropriate field based on data type
      var $fieldOption = $row.find('.custom-field-id option:selected');
      var dataType = $fieldOption.data('type');

      if (dataType === 'Date') {
        $valueField.hide();
        $valuesField.hide();
        $dateField.show();
      } else {
        $valueField.show();
        $valuesField.hide();
        $dateField.hide();
      }
    }
  }

  // Add new custom field condition
  function addCustomFieldCondition() {
    conditionCounter++;
    var $container = $('#custom-field-conditions-container');
    var $newCondition = $container.find('.custom-field-condition:first').clone();

    // Update IDs and clear values
    $newCondition.attr('data-condition-index', conditionCounter);
    $newCondition.find('select, input').each(function() {
      var $field = $(this);
      var oldId = $field.attr('id');
      if (oldId) {
        $field.attr('id', oldId + '_' + conditionCounter);
      }
      $field.val('').trigger('change');
    });

    $newCondition.find('.remove-condition').show();
    $container.append($newCondition);

    // Reinitialize select2
    $newCondition.find('select').select2();
    populateEntityOptions();
    updateRemoveButtons();
  }

  // Update visibility of remove buttons
  function updateRemoveButtons() {
    var $conditions = $('.custom-field-condition');
    if ($conditions.length > 1) {
      $('.remove-condition').show();
    } else {
      $('.remove-condition').hide();
    }
  }

  // Collect all custom field conditions before form submission
  function collectCustomFieldConditions() {
    var conditions = [];

    $('.custom-field-condition').each(function() {
      var $condition = $(this);
      var entity = $condition.find('.custom-field-entity').val();
      var fieldId = $condition.find('.custom-field-id').val();
      var operator = $condition.find('.custom-field-operator').val();
      var value = $condition.find('.custom-field-value').val();
      var values = $condition.find('.custom-field-values').val();
      var dateValue = $condition.find('.custom-field-date-value').val();

      if (entity && fieldId && operator) {
        var conditionData = {
          entity: entity,
          field_id: fieldId,
          operator: operator
        };

        if (operator === 'IN' || operator === 'NOT IN') {
          conditionData.value = values ? values.split(',') : [];
        } else if (operator !== 'IS NULL' && operator !== 'IS NOT NULL') {
          conditionData.value = dateValue || value;
        }

        conditions.push(conditionData);
      }
    });

    $('input[name="custom_field_conditions"]').val(JSON.stringify(conditions));
  }

  // Initialize when document is ready
  $(document).ready(function() {
    console.log('Scheduled Reminder Custom Fields JavaScript initialized');
    // Only initialize if we're on the scheduled reminders form
    setTimeout(function(){
      if ($('#ScheduleReminders').length > 0) {
        console.log('Initializing Scheduled Reminder Custom Fields');
        initCustomFields();
      }
    }, 5000);
  });
})(CRM.$, CRM);
console.log('Scheduled Reminder Custom Fields JavaScript loaded');

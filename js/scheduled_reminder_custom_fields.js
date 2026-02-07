/**
 * JavaScript for Scheduled Reminder Custom Fields Extension
 */
(function($, CRM) {
  'use strict';

  var customFieldConditions = [];
  var conditionCounter = 0;
  var isInitialized = false;

  // Initialize the custom field functionality
  function initCustomFields() {
    if (isInitialized) {
      return;
    }
    isInitialized = true;

    console.log('Initializing Scheduled Reminder Custom Fields');
    bindEventHandlers();
    populateEntityOptions();

    // Load existing conditions in edit mode
    loadExistingConditions();
  }

  /**
   * Load existing conditions from the database (edit mode)
   */
  function loadExistingConditions() {
    // Check if we have existing conditions from PHP
    if (CRM.vars && CRM.vars.scheduledReminderCustomFields &&
      CRM.vars.scheduledReminderCustomFields.existingConditions) {

      var conditions = CRM.vars.scheduledReminderCustomFields.existingConditions;
      console.log('Loading existing conditions:', conditions);

      if (conditions && conditions.length > 0) {
        // Clear the default empty condition
        $('#custom-field-conditions-container').empty();

        // Add each condition
        $.each(conditions, function (index, condition) {
          if (index === 0) {
            // For first condition, create it
            addCustomFieldCondition(condition);
          } else {
            // For subsequent conditions, add them
            addCustomFieldCondition(condition);
          }
        });
      }
    }
  }

  /**
   * Add new custom field condition with optional data
   */
  function addCustomFieldCondition(conditionData) {
    conditionCounter++;
    console.log('Adding new condition, current counter:', conditionCounter, 'with data:', conditionData);
    var $container = $('#custom-field-conditions-container');

    // Create new condition HTML
    var conditionHtml = createConditionHtml(conditionCounter);
    var $newCondition = $(conditionHtml);

    $container.append($newCondition);

    // Initialize select2 for the new condition
    //$newCondition.find('select').select2();

    // Populate entity options
    populateEntityOptionsForElement($newCondition.find('.custom-field-entity'));

    // If condition data is provided, populate the fields
    if (conditionData) {
      populateConditionFields($newCondition, conditionData);
    }

    updateRemoveButtons();
    updateLogicOperators();
  }

  /**
   * Create HTML for a new condition
   */
  function createConditionHtml(index) {
    return `
      <div class="custom-field-condition" data-condition-index="${index}">
        <div class="logic-operator-row" style="display:none; margin-top: 10px; text-align: center;">
          <select name="logic_operator_${index}" class="logic-operator" style="width: 100px;">
            <option value="AND">AND</option>
            <option value="OR">OR</option>
          </select>
        </div>
        <div class="custom-field-row">
          <select name="custom_field_entity_${index}" id="custom_field_entity_${index}" 
                  class="custom-field-entity" style="width: 200px;">
            <option value="">- Select Entity -</option>
          </select>
          <select name="custom_field_id_${index}" id="custom_field_id_${index}" 
                  class="custom-field-id" style="width: 250px;">
            <option value="">- Select Custom Field -</option>
          </select>
          <select name="custom_field_operator_${index}" id="custom_field_operator_${index}" 
                  class="custom-field-operator" style="width: 150px;">
            <option value="">- Select Operator -</option>
          </select>
          <input type="text" name="custom_field_value_${index}" id="custom_field_value_${index}" 
                 class="custom-field-value form-control" placeholder="Enter value" style="width: 200px;" />
          <button type="button" class="btn btn-sm btn-danger remove-condition">
            <i class="crm-i fa-trash"></i>
          </button>
        </div>
      </div>
    `;
  }

  /**
   * Populate condition fields with existing data
   */
  function populateConditionFields($condition, conditionData) {
    // Set entity
    var $entitySelect = $condition.find('.custom-field-entity');
    $entitySelect.val(conditionData.entity).trigger('change');

    // Wait for entity to be set, then load and set custom field
    setTimeout(function () {
      loadCustomFields(conditionData.entity, $condition, function () {
        // Set custom field
        var $fieldSelect = $condition.find('.custom-field-id');
        $fieldSelect.val(conditionData.field_id).trigger('change');

        // logical operator set value
        //var $fieldOperatorSelect = $condition.find('.logic-operator');
        //$fieldOperatorSelect.val(conditionData.logic_operator).trigger('change');

        // Wait for field to be set, then set operator and value
        setTimeout(function () {
          updateOperatorsAndValue(conditionData.field_id, $condition, function () {
            // Set operator
            var $operatorSelect = $condition.find('.custom-field-operator');
            $operatorSelect.val(conditionData.operator).trigger('change');

            // Set value
            setTimeout(function () {
              toggleValueFields(conditionData.operator, $condition);

              if (conditionData.operator !== 'IS NULL' && conditionData.operator !== 'IS NOT NULL') {
                if (conditionData.operator === 'IN' || conditionData.operator === 'NOT IN') {
                  var valueString = Array.isArray(conditionData.value)
                    ? conditionData.value.join(',')
                    : conditionData.value;
                  $condition.find('.custom-field-values').val(valueString);
                } else {
                  $condition.find('.custom-field-value').val(conditionData.value);
                }
              }

              // Set logic operator if present
              if (conditionData.logic_operator) {
                $condition.find('.logic-operator').val(conditionData.logic_operator).trigger('change');
              }
            }, 300);
          });
        }, 300);
      });
    }, 300);
  }

  // Bind event handlers
  function bindEventHandlers() {
    // Entity selection change
    $(document).on('change', '.custom-field-entity', function () {
      var $row = $(this).closest('.custom-field-condition');
      var entityType = $(this).val();
      loadCustomFields(entityType, $row);
    });

    // Custom field selection change
    $(document).on('change', '.custom-field-id', function () {
      var $row = $(this).closest('.custom-field-condition');
      var fieldId = $(this).val();
      updateOperatorsAndValue(fieldId, $row);
    });

    // Operator change
    $(document).on('change', '.custom-field-operator', function () {
      var $row = $(this).closest('.custom-field-condition');
      var operator = $(this).val();
      toggleValueFields(operator, $row);
    });

    // Add condition button
    $(document).on('click', '#add-custom-field-condition', function () {
      console.log('Adding new custom field condition');
      addCustomFieldCondition();
    });

    // Remove condition button
    $(document).on('click', '.remove-condition', function () {
      $(this).closest('.custom-field-condition').remove();
      updateRemoveButtons();
      updateLogicOperators();
    });

    // Form submission
    $('form').on('submit', function () {
      collectCustomFieldConditions();
    });
  }

  /**
   * Populate entity options for a specific select element
   */
  function populateEntityOptionsForElement($select) {
    var entities = {
      'Contact': 'Contact',
      'Activity': 'Activity',
      'Event': 'Event',
      'Membership': 'Membership',
      'Contribution': 'Contribution',
      'Participant': 'Participant'
    };

    $select.empty().append('<option value="">- Select Entity -</option>');

    $.each(entities, function (key, value) {
      $select.append(
        $('<option></option>')
          .attr('value', key)
          .text(value)
      );
    });
  }

  // Populate entity options based on selected entity in main form
  function populateEntityOptions() {
    var $entitySelects = $('select.custom-field-entity');
    $entitySelects.each(function () {
      populateEntityOptionsForElement($(this));
    });
  }

  /**
   * Load custom fields for selected entity with optional callback
   */
  function loadCustomFields(entityType, $row, callback) {
    if (!entityType) {
      $row.find('select.custom-field-id').empty()
        .append('<option value="">- Select Custom Field -</option>');
      if (callback) callback();
      return;
    }

    var $fieldSelect = $row.find('select.custom-field-id');
    $fieldSelect.empty().append('<option value="">Loading...</option>');

    // AJAX call to get custom fields
    var groupMap = {};
    CRM.api3('CustomGroup', 'get', {
      sequential: 1,
      extends: entityType,
      return: "title"
    }).done(function (groupResult) {
      $.each(groupResult.values, function (index, group) {
        groupMap[group.id] = group.title;
      });
      // Store group titles for later use
      $fieldSelect.data('groupMap', groupMap);
      // AJAX call to get custom fields
      CRM.api3('CustomField', 'get', {
        sequential: 1,
        is_active: 1,
        custom_group_id: {"IN": Object.keys(groupMap)},
        options: {limit: 0, sort: "label ASC"}
      }).done(function (result) {
        $fieldSelect.empty().append('<option value="">- Select Custom Field -</option>');

        var fieldsAdded = 0;
        var totalFields = result.values.length;

        if (totalFields === 0) {
          if (callback) callback();
          return;
        }

        $.each(result.values, function (index, field) {
          var optionText = field.label;

          if (field.custom_group_id && groupMap[field.custom_group_id]) {
            optionText = groupMap[field.custom_group_id] + ': ' + field.label;
          }

          $fieldSelect.append(
            '<option value="' + field.id + '" ' +
            'data-type="' + field.data_type + '" ' +
            'data-html-type="' + field.html_type + '">' +
            optionText + '</option>'
          );

          fieldsAdded++;
          if (fieldsAdded === totalFields && callback) {
            callback();
          }
        });
      })
    }).fail(function () {
      $fieldSelect.empty().append('<option value="">Error loading fields</option>');
      if (callback) callback();
    });
  }

  /**
   * Update operators and value field based on custom field type with optional callback
   */
  function updateOperatorsAndValue(fieldId, $row, callback) {
    if (!fieldId) {
      if (callback) callback();
      return;
    }

    var $fieldOption = $row.find('select.custom-field-id option:selected');
    var dataType = $fieldOption.data('type');
    var htmlType = $fieldOption.data('html-type');

    var $operatorSelect = $row.find('select.custom-field-operator');
    var operators = getOperatorsForFieldType(dataType, htmlType);

    $operatorSelect.empty().append('<option value="">- Select Operator -</option>');
    $.each(operators, function (key, value) {
      $operatorSelect.append('<option value="' + key + '">' + value + '</option>');
    });

    // Setup appropriate input field
    setupValueField(dataType, htmlType, $row, fieldId);

    if (callback) callback();
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
    if ($valuesField.length) $valuesField.hide();
    if ($dateField.length) $dateField.hide();

    if (dataType === 'Date') {
      if ($dateField.length) $dateField.show();
      else $valueField.show();
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
    }).done(function (result) {
      var $valueField = $row.find('.custom-field-value');
      var options = [];

      $.each(result.values, function (index, option) {
        options.push({id: option.value, text: option.label});
      });
      /*
      $valueField.select2({
        data: options,
        placeholder: 'Select value(s)',
        allowClear: true,
        multiple: true
      });
       */
    });
  }

  // Toggle value fields based on operator
  function toggleValueFields(operator, $row) {
    var $valueField = $row.find('.custom-field-value');
    var $valuesField = $row.find('.custom-field-values');
    var $dateField = $row.find('.custom-field-date-value');

    if (operator === 'IS NULL' || operator === 'IS NOT NULL') {
      $valueField.hide();
      if ($valuesField.length) $valuesField.hide();
      if ($dateField.length) $dateField.hide();
    } else if (operator === 'IN' || operator === 'NOT IN') {
      $valueField.hide();
      if ($valuesField.length) $valuesField.show();
      if ($dateField.length) $dateField.hide();
    } else {
      // Show the appropriate field based on data type
      var $fieldOption = $row.find('.custom-field-id option:selected');
      var dataType = $fieldOption.data('type');

      if (dataType === 'Date') {
        $valueField.hide();
        if ($valuesField.length) $valuesField.hide();
        if ($dateField.length) $dateField.show();
        else $valueField.show();
      } else {
        $valueField.show();
        if ($valuesField.length) $valuesField.hide();
        if ($dateField.length) $dateField.hide();
      }
    }
  }

  /**
   * Update visibility of logic operators
   */
  function updateLogicOperators() {
    var $conditions = $('.custom-field-condition');
    $conditions.each(function (index) {
      if (index === 0) {
        $(this).find('.logic-operator-row').hide();
      } else {
        $(this).find('.logic-operator-row').show();
      }
    });
  }

  /**
   * Update visibility of remove buttons
   */
  function updateRemoveButtons() {
    var $conditions = $('.custom-field-condition');
    if ($conditions.length > 1) {
      $('.remove-condition').show();
    } else {
      $('.remove-condition').first().hide();
    }
  }

  /**
   * Collect all custom field conditions before form submission
   */
  function collectCustomFieldConditions() {
    var conditions = [];

    $('.custom-field-condition').each(function (index) {
      var $condition = $(this);
      var entity = $condition.find('.custom-field-entity').val();
      var fieldId = $condition.find('.custom-field-id').val();
      var operator = $condition.find('.custom-field-operator').val();
      var value = $condition.find('.custom-field-value').val();
      var values = $condition.find('.custom-field-values').val();
      var dateValue = $condition.find('.custom-field-date-value').val();
      var logicOperator = index > 0 ? $condition.find('.logic-operator').val() : null;

      if (entity && fieldId && operator) {
        var conditionData = {
          entity: entity,
          field_id: fieldId,
          operator: operator
        };

        // Add logic operator for conditions after the first
        if (index > 0 && logicOperator) {
          conditionData.logic = logicOperator;
        }

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
    }, 1000);
  });

})(CRM.$, CRM);
console.log('Scheduled Reminder Custom Fields JavaScript loaded');

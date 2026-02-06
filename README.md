# CiviCRM Scheduled Reminder Custom Fields Extension

## Overview

This extension enhances CiviCRM's Scheduled Reminders functionality by adding powerful custom field filtering capabilities. Now you can create highly targeted reminder campaigns by filtering recipients based on custom field values across different entity types (Contact, Membership, Event, Activity, Participant, etc.).

## Features

### 🎯 **Advanced Targeting**
- Filter recipients using custom fields from any CiviCRM entity
- Combine multiple custom field conditions with AND logic
- Use alongside existing group-based filtering
- Support for all field types: text, select, date, number, checkbox, etc.

### 🔧 **Flexible Operators**
- **Equals / Not Equals**: Exact matching
- **Contains / Does Not Contain**: Text pattern matching
- **Greater Than / Less Than**: Numeric and date comparisons
- **Is Empty / Is Not Empty**: Null value checking
- **Is One Of / Is Not One Of**: Multi-value selection

### 🎨 **Enhanced User Interface**
- Intuitive accordion-style form sections
- Dynamic field loading based on entity selection
- Visual condition builder with add/remove functionality
- Real-time SQL query preview
- Recipient count estimation

### ⚡ **Smart Integration**
- Works seamlessly with existing scheduled reminder functionality
- No modification of core CiviCRM files required
- Backward compatible with existing reminders
- Performance optimized with proper indexing

## Installation

### Method 1: Extension Directory (Recommended)
1. Navigate to **Administer → System Settings → Extensions**
2. Click **Add New** tab
3. Search for "Scheduled Reminder Custom Fields"
4. Click **Download** and then **Install**

### Method 2: Manual Installation
1. Download the extension from GitHub
2. Extract to your CiviCRM extensions directory
3. Navigate to **Administer → System Settings → Extensions**
4. Find "Scheduled Reminder Custom Fields" and click **Install**

### Method 3: Git Clone
```bash
cd /path/to/civicrm/extensions
git clone https://github.com/yourorg/scheduledremindercustomfields.git com.skvare.scheduledremindercustomfields
```

## Requirements

- CiviCRM 5.0 or later
- PHP 7.4 or later
- MySQL 5.7 or later / MariaDB 10.3 or later

## Usage Guide

### Basic Setup

1. **Navigate to Scheduled Reminders**
  - Go to **Mailings → Scheduled Reminders**
  - Click **Add New** or edit an existing reminder

2. **Configure Basic Settings**
  - Set your reminder title, entity, and timing as usual
  - Select membership types, event types, etc. as needed

3. **Add Custom Field Filters**
  - Expand the **"Custom Field Filters"** section
  - Click the entity dropdown and select the entity type (Contact, Membership, Event, etc.)
  - Choose the custom field you want to filter by
  - Select an operator (equals, contains, greater than, etc.)
  - Enter the filter value

4. **Add Multiple Conditions**
  - Click **"Add Another Custom Field Condition"** to add more filters
  - All conditions are combined with AND logic
  - Remove conditions using the trash icon

5. **Preview and Save**
  - Use **"Preview Recipients"** to see how many contacts match your criteria
  - Save your reminder when satisfied

### Example Use Cases

#### 1. Premium Membership Renewal
**Scenario**: Send renewal reminders only to Premium members with high annual revenue

```
Entity: Membership
When: 30 days before membership end date
Filters:
- Group: VIP Members
- Custom Field: Membership.Access_Level = "Premium"
- Custom Field: Contact.Annual_Revenue > 100000
```

#### 2. Event Follow-up for VIP Participants
**Scenario**: Send thank you emails to VIP participants who attended specific sessions

```
Entity: Event
When: 1 day after event end date
Filters:
- Custom Field: Participant.VIP_Status = "Yes"
- Custom Field: Event.Event_Category = "Conference"
- Custom Field: Participant.Sessions_Attended contains "Keynote"
```

#### 3. Activity-Based Reminders
**Scenario**: Remind staff about overdue tasks in specific departments

```
Entity: Activity
When: 7 days after activity due date
Filters:
- Custom Field: Activity.Department = "Sales"
- Custom Field: Activity.Priority = "High"
- Custom Field: Contact.Employee_Status = "Active"
```

### Advanced Features

#### Multi-Entity Filtering
You can filter on custom fields from different entities in the same reminder:

```
Membership Reminder with:
- Membership custom fields (membership tier, benefits)
- Contact custom fields (preferences, demographics)
- Organization custom fields (company size, industry)
```

#### Complex Operator Usage

**Date Comparisons**:
```
Custom Field: Membership.Last_Login_Date < "2024-01-01"
Custom Field: Contact.Birthday >= "1990-01-01"
```

**Multi-Select Fields**:
```
Custom Field: Contact.Interests IN "Technology,Innovation"
Custom Field: Event.Categories NOT IN "Internal,Staff-Only"
```

**Text Pattern Matching**:
```
Custom Field: Contact.Notes LIKE "VIP"
Custom Field: Organization.Name NOT LIKE "Test"
```

## Configuration Options

### Field Types Supported

| Field Type | Available Operators | Notes |
|------------|-------------------|-------|
| Text | =, !=, LIKE, NOT LIKE, IS NULL, IS NOT NULL | |
| Select | =, !=, IN, NOT IN, IS NULL, IS NOT NULL | |
| Multi-Select | =, !=, IN, NOT IN, IS NULL, IS NOT NULL | Use comma-separated values |
| Number | =, !=, >, <, >=, <=, IS NULL, IS NOT NULL | |
| Date | =, !=, >, <, >=, <=, IS NULL, IS NOT NULL | Use YYYY-MM-DD format |
| Yes/No | =, != | Values: 1 (Yes), 0 (No) |
| Memo | =, !=, LIKE, NOT LIKE, IS NULL, IS NOT NULL | |

### Performance Considerations

- **Indexing**: The extension automatically adds appropriate database indexes for custom fields used in filters
- **Query Optimization**: Uses efficient JOIN strategies to minimize query execution time
- **Caching**: Custom field metadata is cached to reduce API calls
- **Batch Processing**: Large recipient lists are processed in batches to prevent timeouts

## API Integration

### Programmatic Creation

You can create scheduled reminders with custom field filters via API:

```php
$result = civicrm_api3('ActionSchedule', 'create', [
  'title' => 'Custom Field Reminder',
  'mapping_id' => 1,
  'entity_value' => 'membership_type_id',
  'entity_status' => '1,2',
  'start_action_date' => 'end_date',
  'start_action_offset' => 30,
  'start_action_unit' => 'day',
  'start_action_condition' => 'before',
  'custom_field_conditions' => json_encode([
    [
      'entity' => 'Membership',
      'field_id' => 25,
      'operator' => '=',
      'value' => 'Premium'
    ],
    [
      'entity' => 'Contact',
      'field_id' => 45,
      'operator' => '>',
      'value' => 100000
    ]
  ])
]);
```

### Retrieving Conditions

```php
$result = civicrm_api3('ActionSchedule', 'get', [
  'id' => 123,
  'return' => ['custom_field_conditions']
]);

$conditions = $result['values'][123]['custom_field_conditions'];
```

## Troubleshooting

### Common Issues

#### 1. Custom Fields Not Appearing
**Problem**: Custom fields don't show in the dropdown
**Solution**:
- Ensure custom fields are active
- Check that the selected entity has custom fields
- Verify custom field permissions

#### 2. No Recipients Found
**Problem**: Preview shows 0 recipients
**Solution**:
- Check that custom field values exist in your data
- Verify filter values match exactly (case-sensitive)
- Use SQL preview to debug query logic

#### 3. Performance Issues
**Problem**: Reminders are slow to process
**Solution**:
- Add database indexes on frequently filtered custom fields
- Limit the number of custom field conditions
- Use more specific group filters first

### Debug Mode

Enable debug logging by adding to your `civicrm.settings.php`:

```php
define('CIVICRM_SCHEDULED_REMINDER_CUSTOM_FIELDS_DEBUG', TRUE);
```

This will log all custom field queries to your CiviCRM log files.

### SQL Query Inspection

Use the "Preview SQL" feature to inspect the generated queries:

```sql
-- Example generated query
SELECT DISTINCT contact.id
FROM civicrm_contact contact
INNER JOIN civicrm_membership membership ON contact.id = membership.contact_id
INNER JOIN civicrm_value_membership_data_25 custom_25 ON membership.id = custom_25.entity_id
INNER JOIN civicrm_value_contact_info_45 custom_45 ON contact.id = custom_45.entity_id
WHERE custom_25.access_level = 'Premium'
  AND custom_45.annual_revenue > 100000
  AND membership.status_id IN (1,2)
```

## Development

### Extending the Extension

You can extend this extension by:

1. **Adding Custom Operators**: Implement new comparison operators in `CRM_Scheduledremindercustomfields_Utils_Query`
2. **Entity Support**: Add support for additional CiviCRM entities
3. **UI Enhancements**: Modify the JavaScript to add new interface features
4. **Performance Optimizations**: Implement query caching or indexing strategies

### Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests
5. Submit a pull request

## Support

- **Documentation**: https://github.com/yourorg/scheduledremindercustomfields/wiki
- **Issues**: https://github.com/yourorg/scheduledremindercustomfields/issues
- **Community Forum**: https://civicrm.org/support
- **Professional Support**: Contact your CiviCRM service provider

## License

This extension is licensed under [AGPL-3.0](https://www.gnu.org/licenses/agpl-3.0.en.html).

## Changelog

### Version 1.0.0
- Initial release
- Support for all major CiviCRM entities
- Dynamic custom field loading
- Multi-condition filtering
- SQL preview functionality
- Performance optimizations

---

**Made with ❤️ for the CiviCRM Community**

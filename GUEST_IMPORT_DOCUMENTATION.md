# Guest Import Feature Documentation

## Overview
The Guest Import feature allows bulk import of guests from Excel (.xlsx, .xls) or CSV files into the GMS system.

## Usage

### 1. Access Import Function
- Navigate to **Guest Registry** (`/gms/guests`)
- Click the **Import** button in the header (next to Export)

### 2. Download Template (Optional)
- Click **Download Template** in the import modal
- This provides a CSV template with all supported columns and example data

### 3. Prepare Your File
Ensure your file includes the required columns:

#### Required Columns:
- `first_name` and `last_name` (or just `name`)
- `email`
- `nationality` (2-letter country code, e.g., QA, US, GB)
- `tier` (T1, T2, T3, T4, or T5)

#### Optional Columns:
- `title` - Job title or role
- `guest_type` - Either "local" or "international" (defaults to "local")
- `qid` - Qatar ID for local guests
- `phone` - Contact phone number
- `group_id` - Group identifier
- `host` - Host name
- `hotel` - Hotel name
- `dietary_notes` - Special dietary requirements
- `notes` - General notes
- `status_id` - Invitation status: "invited", "confirmed", "pending", or "declined" (defaults to "invited")
- `companions` - Number of companions
- `companion_list` - Comma-separated list of companion names with relations (e.g., "John Doe (Spouse), Jane Doe (Aide)")
- `facilities` - Comma-separated list of facilities/services

### 4. Import Process
1. Click **Select File** and choose your Excel or CSV file
2. The system validates:
   - File type (must be .xlsx, .xls, or .csv)
   - File size (maximum 10MB)
3. Click **Import** to start the import
4. The system will:
   - Validate each row
   - Skip invalid rows
   - Import valid rows
   - Auto-generate unique GMS reference numbers (G001, G002, etc.)
   - Associate guests with the current active event

### 5. Import Results
After import completes, you'll see a success message showing:
- Number of guests imported successfully
- Number of rows skipped due to validation errors

The guest list automatically refreshes to show the newly imported guests.

## File Format Examples

### CSV Example:
```csv
name,first_name,last_name,title,guest_type,nationality,email,tier,status_id
Sheikh Ahmed Al-Thani,Ahmed,Al-Thani,Minister of Sports,local,QA,ahmed@example.qa,T1,invited
,Jane,Smith,CEO TechCorp,international,US,jane.smith@techcorp.com,T2,confirmed
```

### Excel Example:
Create an Excel file with the same column structure as above.

## Validation Rules

### Email Format
- Must be a valid email format
- Maximum 255 characters
- Not required but recommended

### Nationality
- Must be a 2-letter country code (ISO 3166-1 alpha-2)
- Examples: QA (Qatar), US (United States), GB (United Kingdom)

### Guest Type
- Must be either "local" or "international"
- Defaults to "local" if not specified

### Tier
- Must be one of: T1, T2, T3, T4, T5
- Corresponds to service levels in the system

### Status
- Must be one of: "invited", "confirmed", "pending", "declined"
- Defaults to "invited" if not specified

### Phone
- Maximum 40 characters
- No specific format validation

### Names
- `first_name` and `last_name` are required unless `name` is provided
- If only `name` is provided, it will be split automatically
- Maximum 120 characters each

## Error Handling

### Validation Errors
If a row fails validation, it will be skipped and counted in the "skipped" total. Common validation errors:
- Missing required fields (name, email, tier)
- Invalid email format
- Invalid guest_type value
- Invalid status_id value
- Invalid tier value

### File Upload Errors
- **Invalid file type**: Only .xlsx, .xls, and .csv files are accepted
- **File too large**: Maximum file size is 10MB
- **No file selected**: You must select a file before clicking Import

## Technical Details

### Backend Implementation
- **Controller**: `App\Http\Controllers\Gms\GmsGuestController@import`
- **Import Class**: `App\Imports\GuestsImport`
- **Route**: `POST /gms/guests/import`
- **Package**: Laravel Excel (maatwebsite/excel)

### Frontend Implementation
- **Page**: `resources/js/Pages/Gms/Guests/Index.vue`
- **Modal**: Uses `GmsModal` component
- **File Upload**: Native HTML5 file input with validation

### Database
- Guests are stored in the `guests` table
- Each guest is associated with the current active event (`event_id`)
- Reference numbers (GMS-ID) are auto-generated sequentially per event

## Tips & Best Practices

1. **Test with Small Batches**: Start with a small file (5-10 rows) to ensure your format is correct
2. **Use the Template**: Download and modify the provided template to avoid formatting issues
3. **Check Country Codes**: Use correct 2-letter ISO codes for nationalities
4. **Verify Tiers**: Ensure tier values (T1-T5) exist in your system
5. **Clean Data**: Remove empty rows and extra columns before importing
6. **UTF-8 Encoding**: Use UTF-8 encoding for CSV files to support international characters
7. **Event Selection**: Make sure the correct event is selected before importing

## Troubleshooting

### Import Button Disabled
- Ensure you've selected a valid file
- Check that the file is under 10MB
- Verify the file extension is .xlsx, .xls, or .csv

### Some Guests Not Imported
- Check the success message for the number of skipped rows
- Verify required fields are present in skipped rows
- Ensure tier values match existing service levels
- Check for invalid email formats or country codes

### No Event Selected Error
- Ensure an event is selected in the sidebar event selector
- If no events exist, create one in Events management first

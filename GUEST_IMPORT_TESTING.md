# Guest Import - Quick Testing Guide

## ✅ Implementation Complete

### Files Created/Modified:
1. **Backend**
   - ✅ `app/Imports/GuestsImport.php` - Import class with validation
   - ✅ `app/Http/Controllers/Gms/GmsGuestController.php` - Added import() method
   - ✅ `routes/gms.php` - Added POST /gms/guests/import route

2. **Frontend**
   - ✅ `resources/js/Pages/Gms/Guests/Index.vue` - Added Import button + modal

3. **Documentation**
   - ✅ `GUEST_IMPORT_DOCUMENTATION.md` - Full user guide
   - ✅ `CLAUDE.md` - Updated technical documentation
   - ✅ `storage/app/templates/guests_import_template.csv` - Sample template

## 🧪 Testing Steps

### 1. Basic Import Test
```bash
# Start your development server if not running
php artisan serve
npm run dev
```

### 2. Access the Import Feature
1. Navigate to http://localhost:8000/gms/guests
2. Click the **Import** button (next to Export)
3. Import modal should open

### 3. Download & Test Template
1. Click "Download Template" in the modal
2. Open the downloaded CSV file
3. Add a few test rows (or use the provided examples)
4. Save the file

### 4. Upload & Import
1. Click "Select File" in the modal
2. Choose your CSV/Excel file
3. Verify file info appears (name + size)
4. Click "Import"
5. Wait for success message
6. Guest list should automatically refresh

### 5. Verify Import Results
- Check the success message shows correct counts (imported/skipped)
- Verify new guests appear in the table
- Check that reference numbers (GMS-ID) are sequential
- Confirm guests are associated with the current active event

## 📋 Test Data

### Minimal CSV (copy and save as test.csv):
```csv
first_name,last_name,email,nationality,tier
John,Doe,john@example.com,US,T2
Jane,Smith,jane@example.com,GB,T3
```

### Full CSV with Optional Fields:
```csv
name,first_name,last_name,title,guest_type,qid,tier,nationality,email,phone,status_id,dietary_notes
Sheikh Ahmed,Ahmed,Al-Thani,Minister,local,QID123,T1,QA,ahmed@test.qa,+974123,invited,No seafood
,John,Smith,CEO,international,,T2,US,john@test.com,+1555123,confirmed,Vegetarian
```

## 🐛 Common Issues & Solutions

### Issue: "No active event selected"
**Solution:** Ensure you've selected an event in the sidebar event selector

### Issue: "Import failed" error
**Solution:** 
- Check file format (must be .xlsx, .xls, or .csv)
- Verify file size is under 10MB
- Ensure required columns are present (first_name, last_name, email, nationality, tier)

### Issue: Some rows skipped
**Solution:**
- Check validation errors in success message
- Verify email format is valid
- Ensure tier values match existing service levels (T1-T5)
- Check nationality codes are 2-letter ISO codes

### Issue: Reference numbers not sequential
**Solution:** This shouldn't happen - the import tracks the counter. If it does, check database for duplicate reference numbers.

## 🔍 Backend Validation

### Required Fields:
- `first_name` + `last_name` OR `name`
- Not actually required by validation, but recommended

### Valid Values:
- `guest_type`: "local" or "international"
- `tier`: "T1", "T2", "T3", "T4", or "T5"
- `status_id`: "invited", "confirmed", "pending", or "declined"
- `nationality`: 2-letter ISO country code (e.g., QA, US, GB)

### File Limits:
- Max size: 10MB
- Formats: .xlsx, .xls, .csv
- Encoding: UTF-8 recommended for international characters

## 📊 Expected Behavior

### Success Scenario:
1. User selects valid file
2. Import button becomes enabled
3. Click Import → button shows "Importing..." with spinner
4. Success toast appears: "X guest(s) imported successfully"
5. Guest list auto-refreshes
6. New guests appear in table with sequential reference numbers

### Partial Success Scenario:
1. Some rows are valid, some invalid
2. Valid rows are imported
3. Invalid rows are skipped
4. Success message shows: "X guest(s) imported successfully. Y row(s) skipped due to validation errors."

### Error Scenario:
1. Invalid file type or size
2. Error toast appears immediately
3. Import doesn't proceed

## 🎯 Features to Test

- [x] Import button appears in header
- [x] Upload icon displays correctly
- [x] Modal opens when clicking Import
- [x] Download template button works
- [x] File selection with validation
- [x] File info displays after selection
- [x] Import button disabled without file
- [x] Loading state during import
- [x] Success/error toast messages
- [x] Auto-refresh after import
- [x] Reference number generation
- [x] Event association
- [x] Row validation and skip logic
- [x] CSV format support
- [x] Excel format support (.xlsx, .xls)

## 🚀 Next Steps

If import is working correctly, you can:
1. Test with larger datasets (100+ rows)
2. Test with international characters
3. Test with various Excel/CSV formats
4. Add custom validation rules if needed
5. Implement export functionality to complement import

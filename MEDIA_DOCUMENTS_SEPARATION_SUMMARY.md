# Media and Documents Separation Summary

## Overview
This update separates the Media Library and Documents module to handle different file types:
- **Media Library** (`/admin/media`): Images only (JPG, PNG, GIF, WebP, ICO)
- **Documents** (`/app/documents`): Files only (PDF, DOC, XLS, PPT, CSV, TXT, ZIP, RAR) - no images

## Changes Made

### 1. Media Library (`/admin/media`)
**File: `app/Livewire/Admin/MediaLibrary.php`**
- Restricted `ALLOWED_EXTENSIONS` to image formats only: `jpg`, `jpeg`, `png`, `gif`, `webp`, `ico`
- Restricted `ALLOWED_MIME_TYPES` to image MIME types only
- Removed `$filterType` property (no longer needed, always shows images)
- Updated query to use `->images()` scope to filter only image types
- Removed document-related extensions and MIME types

**File: `resources/views/livewire/admin/media-library.blade.php`**
- Updated upload section title to "Upload Images"
- Changed file input `accept` attribute to `image/*` only
- Updated supported formats text to show only image formats
- Removed filter type dropdown (images/documents/all)
- Changed filter placeholder to "Search images..."
- Added "Copy Link" button to each media item with toast notification
- Updated empty state message to "No images found"

### 2. Documents Module (`/app/documents`)
**File: `app/Services/DocumentService.php`**
- Removed image extensions: `png`, `jpg`, `jpeg`, `gif`
- Removed image MIME types from `ALLOWED_MIME_TYPES`
- Added `zip` and `rar` archive formats
- Added comment clarifying "Documents only - no images allowed"

**File: `app/Livewire/Documents/Index.php`**
- Added filter to exclude images from query using `->whereNotIn('mime_type', [...])` 
- Filters out all common image MIME types (jpeg, png, gif, webp, svg, ico, etc.)
- Added comment explaining the filter purpose

**File: `resources/views/livewire/documents/index.blade.php`**
- Updated page description to clarify "(non-images)"
- Added "Copy Link" button to each document with toast notification
- Maintains existing security with authorized download URLs

**File: `resources/views/livewire/documents/form.blade.php`**
- Added supported formats information: "PDF, DOC, XLS, PPT, CSV, TXT, ZIP"
- Added warning note: "Note: For images, please use the Media Library"

## Features Added

### Copy Link Functionality
Both Media Library and Documents now have a "Copy Link" button that:
- Uses `navigator.clipboard.writeText()` to copy the file/image URL
- Shows a toast notification confirming the link was copied
- Uses secure, permission-protected download URLs:
  - Media: `route('app.media.download', $item->id)`
  - Documents: `route('app.documents.download', $doc->id)`
- Maintains all existing security permissions and access controls

## Security Considerations

✅ **All existing security is maintained:**
- Download URLs still require authentication
- Permission checks (`media.view`, `documents.download`) are enforced
- Branch isolation is preserved
- User ownership validation remains in place
- Path traversal protections are unchanged
- HTML payload detection is still active

## MediaPicker Component

The `MediaPicker` component already supports proper type-scoping through the `accept-mode` parameter:
- `accept-mode="image"` - Shows and accepts only images
- `accept-mode="file"` - Shows and accepts only documents  
- `accept-mode="mixed"` - Shows and accepts both (default)

**Existing implementations are already configured correctly:**
- Settings > Branding (logo/favicon): Uses `accept-mode="image"` ✓
- Other forms should be reviewed to ensure proper `accept-mode` is set

## Testing Recommendations

1. **Media Library (`/admin/media`)**
   - ✓ Verify only images can be uploaded
   - ✓ Verify only images are displayed in the grid
   - ✓ Test "Copy Link" button functionality
   - ✓ Verify link works and respects permissions

2. **Documents (`/app/documents`)**
   - ✓ Verify only non-image files can be uploaded
   - ✓ Verify no images appear in the documents list
   - ✓ Test uploading PDF, DOC, XLS files
   - ✓ Test "Copy Link" button functionality
   - ✓ Verify appropriate error message when uploading images

3. **MediaPicker Component**
   - ✓ Test in settings (logo/favicon) with `accept-mode="image"`
   - ✓ Verify type filtering works correctly
   - ✓ Test that users cannot bypass type restrictions

## User Experience Improvements

1. **Clear Separation**: Users now understand where to upload images vs documents
2. **Better Guidance**: Forms indicate which file types are accepted
3. **Quick Access**: Copy link feature makes it easy to share files/images
4. **Visual Feedback**: Toast notifications confirm actions
5. **Consistent Interface**: Both pages maintain similar UI patterns

## Migration Notes

**For existing data:**
- Existing images in documents will be filtered out automatically
- Existing documents in media will be filtered out automatically  
- No database migration needed - filters are query-based
- Users can manually move files between modules if needed

**Recommendation:** Consider adding a data migration script to:
1. Move images from Documents to Media
2. Update references in related records
3. Clean up any orphaned records

## Related Files

### Modified Files
1. `app/Livewire/Admin/MediaLibrary.php`
2. `app/Livewire/Documents/Index.php`
3. `app/Services/DocumentService.php`
4. `resources/views/livewire/admin/media-library.blade.php`
5. `resources/views/livewire/documents/index.blade.php`
6. `resources/views/livewire/documents/form.blade.php`

### Unchanged (Reference Only)
- `app/Livewire/Components/MediaPicker.php` - Already properly configured
- `app/Models/Media.php` - Scopes work correctly
- `app/Models/Document.php` - No changes needed
- Security middleware and controllers - All preserved

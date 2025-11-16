# S3 Support Implementation Summary

## Overview

This document summarizes the changes made to add Amazon S3 and S3-compatible storage support to the EasyBackup plugin.

## New Features

1. **S3 Storage Integration** - Backups can now be automatically uploaded to S3 after creation
2. **Dual Storage Support** - Backups are stored both locally and in S3 (if enabled)
3. **S3 Download Support** - Download backups directly from S3 without requiring local storage
4. **S3 Delete Support** - Delete backups from S3 through the UI
5. **Unified Backup Listing** - View backups from both local and S3 storage in a single list
6. **Automatic Cleanup** - Old backup cleanup works for both local and S3 backups
7. **S3-Compatible Services** - Support for MinIO, DigitalOcean Spaces, Wasabi, Backblaze B2, etc.

## Files Added

### 1. `Service/S3StorageService.php`
New service class that handles all S3 operations using Flysystem:
- Upload files to S3
- Download files from S3
- List backups in S3
- Delete files from S3
- Check file existence
- Get file metadata (size, last modified)

### 2. `S3_SETUP.md`
Comprehensive documentation for S3 setup including:
- Installation instructions
- Environment variable configuration
- Examples for different S3 providers
- Troubleshooting guide
- Security best practices
- IAM permissions reference

### 3. `.env.example`
Example environment file showing:
- Required S3 variables
- Optional endpoint configuration
- Examples for popular S3-compatible services

### 4. `CHANGES_S3.md`
This file - summary of all S3-related changes

## Files Modified

### 1. `composer.json`
**Added dependencies:**
```json
"require": {
    "league/flysystem": "^3.0",
    "league/flysystem-aws-s3-v3": "^3.0"
}
```

### 2. `Configuration/EasyBackupConfiguration.php`
**Added methods:**
- `getS3Path(): string` - Returns the configured S3 path prefix
- `isS3Enabled(): bool` - Checks if S3 is properly configured

### 3. `DependencyInjection/Configuration.php`
**Added configuration:**
- `setting_s3_path` - Configuration option for S3 path prefix

### 4. `EventSubscriber/SystemConfigurationSubscriber.php`
**Added UI configuration:**
- New "S3 Path" field in system settings under EasyBackup Config
- Help text for S3 path configuration

### 5. `Service/EasyBackupService.php`
**Major changes:**
- Added `S3StorageService` dependency injection
- Modified `__construct()` to accept and initialize S3 storage service
- Updated `createBackup()` to upload backups to S3 after local creation
- Modified `getExistingBackups()` to list backups from both local and S3 storage
- Added location indicator ('local' or 's3') to backup metadata
- Updated `deleteOldBackups()` to handle deletion from both local and S3 storage

### 6. `Controller/EasyBackupController.php`
**Major changes:**
- Added `S3StorageService` dependency injection
- Modified `__construct()` to accept and initialize S3 storage service
- Updated `downloadAction()` to support downloads from S3
  - Added `location` query parameter to specify download source
  - Handles both local and S3 downloads
- Updated `deleteAction()` to support deletion from S3
  - Added `location` query parameter to specify deletion target
  - Handles both local and S3 deletions

### 7. `Readme.md`
**Added sections:**
- Feature list highlighting S3 support
- S3 storage configuration section
- Links to S3_SETUP.md for detailed instructions

## Environment Variables

The following environment variables control S3 functionality:

| Variable | Required | Description |
|----------|----------|-------------|
| `S3_ACCESS_KEY` | Yes | AWS Access Key ID or equivalent |
| `S3_SECRET_KEY` | Yes | AWS Secret Access Key or equivalent |
| `S3_BUCKET` | Yes | S3 bucket name |
| `S3_REGION` | No | AWS region (default: us-east-1) |
| `S3_ENDPOINT` | No | Custom endpoint for S3-compatible services |

## Configuration

### In Kimai Settings
Navigate to **System → Settings → EasyBackup Config** and set:
- **S3 Path**: The path prefix in your S3 bucket (e.g., `kimai/backups`)

### S3 Enablement Logic
S3 is considered "enabled" when ALL of the following are true:
1. `S3_ACCESS_KEY` environment variable is set
2. `S3_SECRET_KEY` environment variable is set
3. `S3_BUCKET` environment variable is set
4. "S3 Path" is configured in Kimai settings

## Workflow Changes

### Backup Creation (Manual or Cronjob)
1. Create local backup (unchanged)
2. Zip the backup (unchanged)
3. **NEW:** If S3 is enabled, upload ZIP to S3
4. Clean up temporary files (unchanged)
5. **NEW:** Delete old backups from both local and S3 if configured

### Backup Listing
1. Scan local backup directory (unchanged)
2. **NEW:** If S3 is enabled, fetch S3 backup list
3. **NEW:** Merge and sort backups by timestamp
4. Display with location indicator

### Backup Download
1. **NEW:** Check `location` parameter
2. If `location=s3`, download from S3
3. If `location=local` or not specified, download from local filesystem

### Backup Deletion
1. **NEW:** Check `location` parameter
2. If `location=s3`, delete from S3
3. If `location=local` or not specified, delete from local filesystem

## API Changes

### EasyBackupService
```php
// Constructor now requires S3StorageService
public function __construct(
    string $dataDirectory,
    EasyBackupConfiguration $configuration,
    S3StorageService $s3Storage  // NEW
)

// Backup metadata now includes location
getExistingBackups(): array
// Returns: [['name' => '...', 'size' => ..., 'filemtime' => ..., 'location' => 'local|s3'], ...]
```

### EasyBackupController
```php
// Constructor now requires S3StorageService
public function __construct(
    string $dataDirectory,
    EasyBackupConfiguration $configuration,
    EasyBackupService $easyBackupService,
    S3StorageService $s3Storage  // NEW
)

// Download and delete actions now accept location parameter
downloadAction(Request $request): Response  // ?location=local|s3
deleteAction(Request $request): Response    // ?location=local|s3
```

## Backward Compatibility

All changes are **100% backward compatible**:
- Plugin works without S3 configuration (local storage only)
- Existing functionality unchanged when S3 is not configured
- No breaking changes to existing APIs
- Existing local backups remain accessible

## Security Considerations

1. **Credentials stored in environment variables** - Not in database or code
2. **No hardcoded credentials** - All values from environment
3. **Follows 12-factor app methodology** - Configuration via environment
4. **IAM permissions documented** - Minimum required permissions specified
5. **HTTPS recommended** - For S3-compatible services

## Testing Recommendations

1. Test with S3 disabled (local only)
2. Test with S3 enabled (dual storage)
3. Test download from local
4. Test download from S3
5. Test delete from local
6. Test delete from S3
7. Test with different S3 providers (AWS, MinIO, DigitalOcean)
8. Test automatic cleanup with mixed local/S3 backups
9. Test error handling (invalid credentials, network issues)
10. Test with large backup files

## Future Enhancements (Not Implemented)

Potential future improvements:
1. **Restore from S3** - Currently requires download first
2. **S3-only mode** - Option to skip local storage
3. **Encryption at rest** - Client-side encryption before upload
4. **Multipart uploads** - For large files
5. **Transfer acceleration** - For faster uploads
6. **Lifecycle policies** - Automatic archival to Glacier
7. **Backup verification** - Checksum validation
8. **Incremental backups** - Only backup changed files
9. **Compression options** - Different compression levels
10. **UI indicators** - Show upload/download progress

## Dependencies

### New Composer Packages
- `league/flysystem` (^3.0) - Filesystem abstraction
- `league/flysystem-aws-s3-v3` (^3.0) - S3 adapter for Flysystem

### PHP Extensions Required
- No new PHP extensions required (AWS SDK handles dependencies)

## Deployment Notes

1. Run `composer install` or `composer update` after pulling changes
2. Add S3 environment variables to `.env` file
3. Restart PHP-FPM/web server to load new environment variables
4. Configure S3 Path in Kimai admin panel
5. Test with a manual backup creation
6. Check logs in `var/easy_backup/easybackup.log` for S3 operations

## Support & Troubleshooting

Common issues and solutions are documented in `S3_SETUP.md` under the "Troubleshooting" section.

For debugging:
- Check `var/easy_backup/easybackup.log` for detailed S3 operation logs
- All S3 operations are logged with INFO/ERROR level
- S3 errors include exception messages for troubleshooting

## Credits

- Original EasyBackup plugin by Maximilian Groß
- S3 support implementation uses The PHP League's Flysystem
- AWS SDK v3 for PHP by Amazon Web Services
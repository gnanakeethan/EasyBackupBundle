# S3 Storage Support for EasyBackup

This plugin now supports storing backups in Amazon S3 or S3-compatible storage services (e.g., MinIO, DigitalOcean Spaces, Wasabi).

## Features

- Automatic backup upload to S3 after local backup creation
- Download backups from S3
- Delete backups from S3
- List backups from both local storage and S3
- Automatic cleanup of old backups (respects the backup amount max setting)
- Support for custom S3-compatible endpoints

## Prerequisites

1. PHP 8.1 or higher
2. Composer installed
3. An S3 bucket or S3-compatible storage account

## Installation

### 1. Install Dependencies

After installing the plugin, run:

```bash
cd /var/www/kimai
composer require league/flysystem:^3.0 league/flysystem-aws-s3-v3:^3.0
```

### 2. Configure Environment Variables

Add the following environment variables to your `.env` file:

```bash
# Required S3 credentials (from environment)
S3_ACCESS_KEY=your-access-key-here
S3_SECRET_KEY=your-secret-key-here
S3_BUCKET=your-bucket-name
S3_REGION=us-east-1

# Optional: For S3-compatible services (MinIO, DigitalOcean Spaces, etc.)
S3_ENDPOINT=https://your-custom-endpoint.com
```

### 3. Configure S3 Path in Kimai Settings

1. Log in to Kimai as an administrator
2. Navigate to **System** → **Settings** → **EasyBackup Config**
3. Find the **S3 Path** field
4. Enter the path prefix where backups should be stored in your S3 bucket (e.g., `kimai/backups` or leave empty for root)
5. Save the settings

## Environment Variables Reference

| Variable | Required | Description | Example |
|----------|----------|-------------|---------|
| `S3_ACCESS_KEY` | Yes | AWS Access Key ID or equivalent | `AKIAIOSFODNN7EXAMPLE` |
| `S3_SECRET_KEY` | Yes | AWS Secret Access Key or equivalent | `wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY` |
| `S3_BUCKET` | Yes | S3 bucket name | `my-kimai-backups` |
| `S3_REGION` | No | AWS region (default: us-east-1) | `eu-west-1` |
| `S3_ENDPOINT` | No | Custom endpoint for S3-compatible services | `https://nyc3.digitaloceanspaces.com` |

## Configuration Examples

### Amazon S3

```bash
S3_ACCESS_KEY=AKIAIOSFODNN7EXAMPLE
S3_SECRET_KEY=wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY
S3_BUCKET=my-kimai-backups
S3_REGION=us-east-1
```

### DigitalOcean Spaces

```bash
S3_ACCESS_KEY=your-spaces-access-key
S3_SECRET_KEY=your-spaces-secret-key
S3_BUCKET=my-space-name
S3_REGION=nyc3
S3_ENDPOINT=https://nyc3.digitaloceanspaces.com
```

### MinIO (Self-hosted)

```bash
S3_ACCESS_KEY=minioadmin
S3_SECRET_KEY=minioadmin
S3_BUCKET=kimai-backups
S3_REGION=us-east-1
S3_ENDPOINT=http://localhost:9000
```

### Wasabi

```bash
S3_ACCESS_KEY=your-wasabi-access-key
S3_SECRET_KEY=your-wasabi-secret-key
S3_BUCKET=my-wasabi-bucket
S3_REGION=us-west-1
S3_ENDPOINT=https://s3.us-west-1.wasabisys.com
```

## How It Works

### Backup Creation

When you create a backup (manually or via cronjob):

1. The backup is created locally in the configured backup directory
2. The backup is zipped
3. If S3 is enabled and configured, the zip file is automatically uploaded to S3
4. Local and S3 backups are listed in the UI

### Backup Listing

The backup list shows all backups from both local storage and S3, with an indicator showing the location of each backup.

### Downloading Backups

- Local backups are downloaded directly from the server
- S3 backups are downloaded from S3 on-demand
- The download URL includes a `location` parameter to specify the source

### Deleting Backups

- You can delete backups from either local storage or S3
- Automatic cleanup (based on backup amount max setting) works for both local and S3 backups

### Restoring Backups

Currently, restoring from S3 backups requires downloading them first. The restore functionality works with local backups.

## Troubleshooting

### S3 Upload Fails

1. Check that your credentials are correct in the `.env` file
2. Verify that the S3 bucket exists
3. Ensure the IAM user/access key has permissions to upload files to the bucket
4. Check the `easybackup.log` file in your backup directory for detailed error messages

### Required IAM Permissions (AWS S3)

Your IAM user needs the following permissions:

```json
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Effect": "Allow",
            "Action": [
                "s3:PutObject",
                "s3:GetObject",
                "s3:DeleteObject",
                "s3:ListBucket"
            ],
            "Resource": [
                "arn:aws:s3:::your-bucket-name/*",
                "arn:aws:s3:::your-bucket-name"
            ]
        }
    ]
}
```

### Backups Not Showing from S3

1. Verify the S3 Path configuration in Kimai settings matches your bucket structure
2. Check that the environment variables are loaded correctly (restart PHP-FPM/web server after changing `.env`)
3. Ensure the S3 bucket has objects with the correct naming pattern: `YYYY-MM-DD_HHMMSS.zip`

### Permission Errors

Ensure your web server can read the `.env` file and that the credentials are not exposed publicly.

## Security Best Practices

1. **Never commit credentials** to version control
2. Use environment variables for all sensitive information
3. Restrict IAM permissions to the minimum required
4. Enable bucket versioning for additional backup protection
5. Consider enabling S3 bucket encryption
6. Use HTTPS endpoints for S3-compatible services
7. Regularly rotate access keys

## Performance Considerations

- Large backups may take time to upload to S3
- Download speeds depend on your server's internet connection and S3 region
- Consider setting up S3 lifecycle policies to automatically archive old backups to Glacier for cost savings

## Migration from Local-Only Storage

If you have existing local backups:

1. Configure S3 as described above
2. New backups will be stored in both local and S3
3. Existing local backups remain accessible
4. You can manually delete old local backups if desired

## Cost Optimization

- Use S3 lifecycle policies to move old backups to cheaper storage classes
- Enable S3 Intelligent-Tiering for automatic cost optimization
- Consider using S3-compatible services like Wasabi or Backblaze B2 for lower costs
- Set an appropriate backup retention policy using the "Backup Amount Max" setting

## Support

For issues specific to S3 support, please check:
- The `easybackup.log` file in your backup directory
- Kimai's main log files
- PHP error logs

For general plugin support, visit the [EasyBackup GitHub repository](https://github.com/mxgross/EasyBackupBundle).
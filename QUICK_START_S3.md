# Quick Start Guide - S3 Support

This guide will help you enable S3 storage for EasyBackup in 5 minutes.

## Prerequisites

- EasyBackup plugin already installed
- Composer access on your server
- An S3 bucket or S3-compatible storage account
- Access credentials (Access Key and Secret Key)

## Step 1: Install Dependencies (2 minutes)

SSH into your server and run:

```bash
cd /var/www/kimai  # or your Kimai installation path
composer require league/flysystem:^3.0 league/flysystem-aws-s3-v3:^3.0
```

## Step 2: Configure Environment Variables (2 minutes)

Edit your Kimai `.env` file:

```bash
nano /var/www/kimai/.env
```

Add these lines at the end (replace with your actual values):

```bash
# S3 Configuration
S3_ACCESS_KEY=your-access-key-here
S3_SECRET_KEY=your-secret-key-here
S3_BUCKET=your-bucket-name
S3_REGION=us-east-1
```

**For S3-compatible services (MinIO, DigitalOcean Spaces, etc.), also add:**

```bash
S3_ENDPOINT=https://your-endpoint-url.com
```

Save and exit (Ctrl+X, then Y, then Enter)

## Step 3: Restart PHP-FPM (30 seconds)

Restart your PHP service to load the new environment variables:

```bash
# For Ubuntu/Debian with PHP-FPM
sudo systemctl restart php8.1-fpm  # adjust version as needed

# For other setups
sudo service php-fpm restart
# OR
sudo systemctl restart nginx  # if using nginx
sudo systemctl restart apache2  # if using apache
```

## Step 4: Configure S3 Path in Kimai (1 minute)

1. Log in to Kimai as an administrator
2. Navigate to: **System** → **Settings**
3. Find the **EasyBackup Config** section
4. Locate the **S3 Path** field
5. Enter your desired path (e.g., `kimai/backups` or leave empty for root)
6. Click **Save**

## Step 5: Test It! (30 seconds)

1. Go to **EasyBackup** in the Kimai menu
2. Click **Create Backup**
3. Wait for the backup to complete
4. Check the backup list - you should see backups with location indicators
5. Check logs at `var/easy_backup/easybackup.log` for S3 upload confirmation

## Verify S3 Upload

Check your S3 bucket directly to confirm the backup file was uploaded. The filename will match the format: `YYYY-MM-DD_HHMMSS.zip`

## Common Configuration Examples

### AWS S3
```bash
S3_ACCESS_KEY=AKIAIOSFODNN7EXAMPLE
S3_SECRET_KEY=wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY
S3_BUCKET=my-kimai-backups
S3_REGION=us-east-1
```

### DigitalOcean Spaces
```bash
S3_ACCESS_KEY=your-spaces-key
S3_SECRET_KEY=your-spaces-secret
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
S3_ENDPOINT=http://minio.example.com:9000
```

## Troubleshooting

### Backup created but not uploaded to S3

**Check:**
1. All 4 environment variables are set (ACCESS_KEY, SECRET_KEY, BUCKET, and S3 Path in settings)
2. PHP-FPM was restarted after adding variables
3. Check `var/easy_backup/easybackup.log` for error messages

### "Failed to upload to S3" error

**Check:**
1. Credentials are correct
2. Bucket exists
3. IAM user has proper permissions (see detailed permissions in S3_SETUP.md)
4. Network connectivity to S3

### Variables not loaded

**Solution:**
```bash
# Verify variables are loaded
php -r "var_dump(getenv('S3_ACCESS_KEY'));"

# If false, check .env file location and restart PHP-FPM
sudo systemctl restart php8.1-fpm
```

## Next Steps

- **Set up automatic backups**: See main README for cronjob setup
- **Configure backup retention**: Set "Backup Amount Max" in settings
- **Review security**: See S3_SETUP.md for IAM permissions and best practices
- **Cost optimization**: Consider S3 lifecycle policies for old backups

## Need More Help?

- **Detailed S3 Setup**: See [S3_SETUP.md](S3_SETUP.md)
- **Full Documentation**: See [Readme.md](Readme.md)
- **Common Issues**: See "Troubleshooting" section in S3_SETUP.md
- **GitHub Issues**: https://github.com/mxgross/EasyBackupBundle/issues

## Success! 🎉

Your backups are now being stored in S3! Each backup will be:
- ✅ Created locally for immediate access
- ✅ Uploaded to S3 for off-site storage
- ✅ Downloadable from either location
- ✅ Automatically cleaned up based on your retention settings
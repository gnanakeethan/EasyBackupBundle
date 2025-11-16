<?php

/*
 * This file is part of the EasyBackupBundle.
 * All rights reserved by Maximilian Groß (www.maximiliangross.de).
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\EasyBackupBundle\Service;

use Aws\S3\S3Client;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use Symfony\Component\Security\Core\Exception\RuntimeException;

/**
 * Service for handling S3 storage operations
 */
class S3StorageService
{
  private ?Filesystem $filesystem = null;
  private string $s3Path;
  private bool $enabled = false;

  public function __construct()
  {
    $this->initialize();
  }

  /**
   * Initialize S3 connection from environment variables
   */
  private function initialize(): void
  {
    $accessKey = $_ENV["S3_ACCESS_KEY"] ?? ($_SERVER["S3_ACCESS_KEY"] ?? null);
    $secretKey = $_ENV["S3_SECRET_KEY"] ?? ($_SERVER["S3_SECRET_KEY"] ?? null);
    $region = $_ENV["S3_REGION"] ?? ($_SERVER["S3_REGION"] ?? "us-east-1");
    $bucket = $_ENV["S3_BUCKET"] ?? ($_SERVER["S3_BUCKET"] ?? null);
    $endpoint = $_ENV["S3_ENDPOINT"] ?? ($_SERVER["S3_ENDPOINT"] ?? null);

    // If credentials are not set, S3 is not enabled
    if (empty($accessKey) || empty($secretKey) || empty($bucket)) {
      $this->enabled = false;
      return;
    }

    try {
      $clientConfig = [
        "credentials" => [
          "key" => $accessKey,
          "secret" => $secretKey,
        ],
        "region" => $region,
        "version" => "latest",
      ];

      // Support for custom S3-compatible endpoints (e.g., MinIO, DigitalOcean Spaces)
      if (!empty($endpoint)) {
        $clientConfig["endpoint"] = $endpoint;
        $clientConfig["use_path_style_endpoint"] = true;
      }

      $client = new S3Client($clientConfig);

      $adapter = new AwsS3V3Adapter($client, $bucket);

      $this->filesystem = new Filesystem($adapter);
      $this->enabled = true;
    } catch (\Exception $e) {
      $this->enabled = false;
      throw new RuntimeException(
        "Failed to initialize S3 connection: " . $e->getMessage(),
      );
    }
  }

  /**
   * Check if S3 is enabled and configured
   */
  public function isEnabled(): bool
  {
    return $this->enabled;
  }

  /**
   * Set the S3 path prefix from configuration
   */
  public function setS3Path(string $path): void
  {
    // Normalize path - remove leading/trailing slashes
    $this->s3Path = trim($path, "/");
    if (!empty($this->s3Path)) {
      $this->s3Path .= "/";
    }
    $this->enabled = true;
  }

  /**
   * Get the S3 path prefix
   */
  public function getS3Path(): string
  {
    return $this->s3Path ?? "";
  }

  /**
   * Upload a file to S3
   *
   * @param string $localFilePath The local file path
   * @param string $fileName The filename to use in S3
   * @throws FilesystemException
   */
  public function uploadFile(string $localFilePath, string $fileName): void
  {
    if (!$this->enabled || $this->filesystem === null) {
      throw new RuntimeException("S3 storage is not enabled or configured");
    }

    if (!file_exists($localFilePath)) {
      throw new RuntimeException("Local file not found: $localFilePath");
    }

    $s3Key = $this->getS3Path() . $fileName;
    $stream = fopen($localFilePath, "r");

    if ($stream === false) {
      throw new RuntimeException("Failed to open file: $localFilePath");
    }

    try {
      $this->filesystem->writeStream($s3Key, $stream);
    } finally {
      if (is_resource($stream)) {
        fclose($stream);
      }
    }
  }

  /**
   * Download a file from S3
   *
   * @param string $fileName The filename in S3
   * @return string The file contents
   * @throws FilesystemException
   */
  public function downloadFile(string $fileName): string
  {
    if (!$this->enabled || $this->filesystem === null) {
      throw new RuntimeException("S3 storage is not enabled or configured");
    }

    $s3Key = $this->getS3Path() . $fileName;

    return $this->filesystem->read($s3Key);
  }

  /**
   * Check if a file exists in S3
   *
   * @param string $fileName The filename to check
   * @return bool
   */
  public function fileExists(string $fileName): bool
  {
    if (!$this->enabled || $this->filesystem === null) {
      return false;
    }

    try {
      $s3Key = $this->getS3Path() . $fileName;
      return $this->filesystem->fileExists($s3Key);
    } catch (FilesystemException $e) {
      return false;
    }
  }

  /**
   * Delete a file from S3
   *
   * @param string $fileName The filename to delete
   * @throws FilesystemException
   */
  public function deleteFile(string $fileName): void
  {
    if (!$this->enabled || $this->filesystem === null) {
      throw new RuntimeException("S3 storage is not enabled or configured");
    }

    $s3Key = $this->getS3Path() . $fileName;
    $this->filesystem->delete($s3Key);
  }

  /**
   * List all backup files in S3
   *
   * @return array Array of file information
   */
  public function listBackups(): array
  {
    if (!$this->enabled || $this->filesystem === null) {
      return [];
    }

    try {
      $listing = $this->filesystem->listContents($this->getS3Path(), false);
      $backups = [];

      foreach ($listing as $item) {
        if ($item->isFile()) {
          $path = $item->path();
          $fileName = basename($path);

          // Only include files matching the backup naming pattern
          if (preg_match('/^\d{4}-\d{2}-\d{2}_\d{6}\.zip$/', $fileName)) {
            $backups[] = [
              "name" => $fileName,
              "size" => round($item->fileSize() / 1048576, 3), // Convert to MB
              "filemtime" => $item->lastModified(),
            ];
          }
        }
      }

      // Sort by timestamp descending
      usort($backups, function ($a, $b) {
        return $b["filemtime"] <=> $a["filemtime"];
      });

      return $backups;
    } catch (FilesystemException $e) {
      throw new RuntimeException(
        "Failed to list S3 backups: " . $e->getMessage(),
      );
    }
  }

  /**
   * Get file size from S3
   *
   * @param string $fileName The filename
   * @return int File size in bytes
   * @throws FilesystemException
   */
  public function getFileSize(string $fileName): int
  {
    if (!$this->enabled || $this->filesystem === null) {
      throw new RuntimeException("S3 storage is not enabled or configured");
    }

    $s3Key = $this->getS3Path() . $fileName;
    return $this->filesystem->fileSize($s3Key);
  }

  /**
   * Get last modified timestamp from S3
   *
   * @param string $fileName The filename
   * @return int Unix timestamp
   * @throws FilesystemException
   */
  public function getLastModified(string $fileName): int
  {
    if (!$this->enabled || $this->filesystem === null) {
      throw new RuntimeException("S3 storage is not enabled or configured");
    }

    $s3Key = $this->getS3Path() . $fileName;
    return $this->filesystem->lastModified($s3Key);
  }
}

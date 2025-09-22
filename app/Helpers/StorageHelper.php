<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class StorageHelper
{
  /**
   * Upload a file to a specific disk.
   *
   * @param mixed  $file      UploadedFile instance or path string
   * @param string $filePath  Path + filename to save
   * @param string $disk      Disk to use (public, attachments, zips, s3, etc.)
   * @return string           File path or URL
   */
  public static function uploadFile($file, $filePath, $disk = 'public')
  {
    try {
      $storage = Storage::disk($disk);

      if (is_string($file)) {
        // If $file is a local path
        $storage->put($filePath, file_get_contents($file));
      } else {
        // If $file is an UploadedFile instance
        $storage->put($filePath, file_get_contents($file->getRealPath()));
      }

      $storage->setVisibility($filePath, 'public');

      Log::info("File uploaded to disk '{$disk}' at path: {$filePath}");

      // For S3 or cloud disks, return full URL
      return $storage->url($filePath);
    } catch (\Exception $e) {
      Log::error("File upload failed on disk '{$disk}': " . $e->getMessage());
      throw $e;
    }
  }

  /**
   * Delete a file from a specific disk.
   *
   * @param string $path
   * @param string $fileName
   * @param string $disk
   * @return bool
   */
  public static function deleteFile($path, $fileName, $disk = 'public')
  {
    $storage = Storage::disk($disk);
    $filePath = rtrim($path, '/') . '/' . $fileName;

    if ($storage->exists($filePath)) {
      $storage->delete($filePath);
      Log::info("File deleted from disk '{$disk}' at path: {$filePath}");
      return true;
    }

    return false;
  }

  /**
   * Get the public URL of a file from a specific disk.
   *
   * @param string $path
   * @param string $fileName
   * @param string $disk
   * @return string
   */
  public static function getFileUrl($path, $fileName, $disk = 'public')
  {
    $storage = Storage::disk($disk);
    $filePath = rtrim($path, '/') . '/' . $fileName;
    return $storage->url($filePath);
  }

  /**
   * Store logo files (company_logo, company_brand_logo) either locally or on S3.
   *
   * @param mixed  $file       UploadedFile instance
   * @param string $configKey  'company_logo' or 'company_brand_logo'
   * @param string $disk       Disk to save file ('public' or 's3')
   * @return array
   */
  public static function storeLogoFile($file, $configKey, $disk = 'public')
  {
    $validationRules = [
      'company_logo' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
      'company_brand_logo' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
    ];

    $fileNames = [
      'company_logo' => 'logo.png',
      'company_brand_logo' => 'logo-icon.png',
    ];

    // Validate file
    $validator = Validator::make(
      [$configKey => $file],
      [$configKey => $validationRules[$configKey]]
    );

    if ($validator->fails()) {
      return [
        'success' => false,
        'path' => '',
      ];
    }

    // Determine file path
    $fileName = $fileNames[$configKey];
    $filePath = 'logos/' . $fileName;

    try {
      if ($disk === 'public') {
        // Save locally in public/logos
        $destinationPath = public_path('logos');
        if (!is_dir($destinationPath)) {
          mkdir($destinationPath, 0755, true);
        }

        $existingFile = $destinationPath . '/' . $fileName;
        if (file_exists($existingFile)) {
          unlink($existingFile);
        }

        $file->move($destinationPath, $fileName);
        $url = asset('logos/' . $fileName);
      } else {
        // Save to S3 or other disk
        $url = self::uploadFile($file, $filePath, $disk);
      }

      return [
        'success' => true,
        'path' => $url,
      ];
    } catch (\Exception $e) {
      Log::error("Failed to store {$configKey}: " . $e->getMessage());
      return [
        'success' => false,
        'path' => '',
      ];
    }
  }
}
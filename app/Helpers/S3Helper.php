<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class S3Helper
{
  // protected $disk;

  public function __construct()
  {
    // $this->disk = Storage::disk('s3');
  }

  public static function uploadFile($file, $file_path)
  {
    try {
      $disk = Storage::disk('s3');
      Log::info("Saving file to path: " . $file_path);

      // Upload the file to S3
      $disk->put($file_path, file_get_contents($file));
      Log::info("File successfully uploaded to S3 at path: " . $file_path);

      // Optionally, set the file visibility
      $disk->setVisibility($file_path, 'public');

      return $file_path;
    } catch (\Exception $e) {
      Log::error("File upload failed: " . $e->getMessage());
      throw $e;
    }
  }

  public static function deleteFile($path, $fileName)
  {
    $disk = Storage::disk('s3');

    $filePath = $path . $fileName;

    if ($disk->exists($filePath)) {
      $disk->delete($filePath);
      return true;
    }

    return false;
  }

  public static function getFileUrl($path, $fileName)
  {
    $disk = Storage::disk('s3');

    $filePath =  $fileName;
    // $filePath = $path . $fileName;

    return $disk->url($filePath);
  }


  public static function storeFile($file, $config_key)
  {
    // Validation rules
    $validationRules = [
      'company_logo' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
      'company_brand_logo' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
    ];

    // File names for specific keys
    $fileNames = [
      'company_logo' => 'logo.png',
      'company_brand_logo' => 'logo-icon.png',
    ];

    // Validate the file (manually since it's passed directly)
    $validator = Validator::make(
      [$config_key => $file],
      [$config_key => $validationRules[$config_key]]
    );

    if ($validator->fails()) {
      return [
        'success' => false,
        'path' => '',
      ];
    }

    $destinationPath = public_path('logos');
    $fileName = $fileNames[$config_key];

    // Ensure directory exists
    if (!is_dir($destinationPath)) {
      mkdir($destinationPath, 0755, true);
    }

    // Delete existing file if it exists
    $existingFile = $destinationPath . '/' . $fileName;
    if (file_exists($existingFile)) {
      unlink($existingFile);
    }

    // Move the new file
    $file->move($destinationPath, $fileName);

    return [
      'success' => true,
      'path' => 'logos/' . $fileName,
    ];
  }
}
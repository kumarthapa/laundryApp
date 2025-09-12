<?php

namespace App\Traits;
use File;
use Storage;

use Log;

trait FilesProcessing {
  public function makeDirectory($path){
    // Check if the directory already exists.
    if (!File::isDirectory($path)) {

        // Create the directory.
        File::makeDirectory($path);
    }
  }

  public function downloadAsset($url)
  {
      // $asset = Asset::find($id);
      $assetPath = Storage::disk('s3')->url($url);

      header("Cache-Control: public");
      header("Content-Description: File Transfer");
      header("Content-Disposition: attachment; filename=" . basename($assetPath));
      header("Content-Type: jpeg");

      return readfile($assetPath);
  }

  function downloadFileFromUrl($url, $storageName, $subfolder = '') {

    // $assetPath = Storage::disk('s3')->url(urldecode($url));

    header("Cache-Control: public");
    header("Content-Description: File Transfer");
    header("Content-Disposition: attachment; filename=" . basename($url));
    header("Content-Type: jpeg");

    Log::info("assetPath ---> ".json_encode($url));
    return readfile($url);

    //To add subdirectory to the filename
    // $addSubFolder = '';

    // if($subfolder){
    //   //Check if the directory exists
    //   $path =  Storage::disk( $storageName)->path($subfolder);
    //   $this->makeDirectory($path);
    //   $addSubFolder = $subfolder.'/';
    // }
    // // Get the current date and time.
    // $dateTime = now();

    // // Generate a unique filename.
    // $filename = $addSubFolder.$dateTime->format('YmdHis') . '_' . basename($url);
    // Log::info("filename -->".json_encode($filename));
    // Storage::disk($storageName)->put( $filename, file_get_contents($url));

    // // Get the file url to download
    // return Storage::disk($storageName)->url($filename);
  }
}

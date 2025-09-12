<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\RestController;
use Illuminate\Http\Request;
use App\Traits\FilesProcessing;
use Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class FileController extends RestController
{
    use FilesProcessing; // Use the trait here.

    public function downloadFile($filenames)
    {
      $arr_files = json_decode($filenames);
      if (!$arr_files) {
          return response()->json(['error' => 'No files provided'], 400);
      }

      else if(isset($arr_files) && count($arr_files) < 2) {
        $filename = $arr_files[0];
        $disk = Storage::disk('s3');

        if (!$disk->exists($filename)) {
            abort(404, 'File not found');
        }

        return new StreamedResponse(function () use ($disk, $filename) {
            $stream = $disk->readStream($filename);
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => $disk->mimeType($filename),
            'Content-Disposition' => 'attachment; filename="' . basename($filename) . '"',
        ]);
      }

      else {
        // Create a temporary zip file
        $zipFileName = storage_path('app/public/download.zip');
        $zip = new ZipArchive;

        if ($zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            foreach ($arr_files as $filename) {
                $disk = Storage::disk('s3');

                if ($disk->exists($filename)) {
                    $fileContents = $disk->get($filename);
                    $zip->addFromString(basename($filename), $fileContents);
                }
            }
            $zip->close();
        } else {
            return response()->json(['error' => 'Could not create ZIP file'], 500);
        }

        return response()->download($zipFileName)->deleteFileAfterSend(true);
      }
    }
}

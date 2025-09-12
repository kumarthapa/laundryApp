<?php

namespace App\Models;

use App\Helpers\UtilityHelper;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class Documents extends AppModel
{
  use HasFactory;

  protected $table = 'documents';
  protected $primaryKey = 'id';
  protected $fillable = [
    'code',
    'document_name',
    'document_type',
    'document_number',
    'type',
    'path',
    'document_start',
    'document_expiry',
    'status',
    'is_renewed',
    'old_docs_id'
  ];

  public $timestamps = true;


  protected static function boot()
  {
    parent::boot();

    static::retrieved(function ($document) {
      $paths = UtilityHelper::loadDocumentPath($document, $document->type . '/');
      $document->path = $paths;
      // $document->paths = $paths;
    });
  }



  /**
   * @param Request $request
   * @param array $post_data
   * @param string $code
   * @param string $folder
   * @param string $code
   */
  public function uploadAndSaveDocs(Request $request, $post_data, $code, $folder, $docs_names, $old_docs_id = 0)
  {
    // accept=".jpg,.jpeg,.png,.pdf"
    try {
      $success = false;
      foreach ($docs_names as $doc) {
        $field = $doc['docs_type'] . '_images';
        if ($request->hasFile($field)) {
          // Validate all files for this document type
          $validator = Validator::make($request->all(), [
            $field . '.*' => 'required|file|mimes:jpg,jpeg,png,pdf,docx|max:5048', // max in KB
          ]);

          if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->all()], 422);
          }
          $images = $request->file($field);
          $_imgs = [];
          // Upload each image
          if (isset($doc['docs_type']) && $doc['docs_type'] == 'agreement_format') {
            $up_images = UtilityHelper::uploadImagesWithOriginalName(
              $images,
              $folder . '/',
              $code . '/' . $doc['docs_type'] . '/',
              $doc['docs_type'] . '_'
            );
          } else {
            $up_images = UtilityHelper::uploadImages(
              $images,
              $folder . '/',
              $code . '/' . $doc['docs_type'] . '/',
              $doc['docs_type'] . '_'
            );
          }
          $_data = [
            'code' => $code,
            'document_name' => $doc['name'],
            'document_type' => $doc['docs_type'],
            'document_number' => $post_data[$doc['docs_type'] . '_number'] ?? null,
            'path' => json_encode($up_images),
            'status' => 'active',
            'type' => $folder,
            'is_renewed' => $old_docs_id ? 1 : 0,
            'old_docs_id' => $old_docs_id ?: null,
          ];

          if (!empty($doc['start_date']) && !empty($post_data[$doc['docs_type'] . '_start'])) {
            $_data['document_start'] = $post_data[$doc['docs_type'] . '_start'];
          }

          if (!empty($doc['has_expiry']) && !empty($post_data[$doc['docs_type'] . '_expiry'])) {
            $_data['document_expiry'] = $post_data[$doc['docs_type'] . '_expiry'];
          }
          Log::info("Saved Document Paths: " . json_encode($up_images));
          Log::info("Document create: " . json_encode($_data));
          $saved = Documents::create($_data);
          Log::info("Document saved: " . $saved);

          // Log user activity
          $this->UserActivityLog($request, [
            'module' => $_data['type'],
            'activity_type' => 'upload',
            'message' => 'Upload document: ' . $_data['document_name'],
            'application' => 'web',
            'data' => $_data
          ]);

          $success = true;
        }
      }

      return $success;
    } catch (Exception $e) {
      Log::error("Error in uploadAndSaveDocs: " . $e->getMessage() . " at line " . $e->getLine());
      return false;
    }
  }
}
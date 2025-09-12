<?php

namespace App\Helpers;

use App\Models\settings\Configsetting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
#use CustomHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\DocumentExpiryNotification;
use Carbon\Carbon;
use App\Models\user_management\Permission;
use App\Models\user_management\GrantsPermission;
use App\Models\user_management\Role;
use File;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\Facades\Image;

class UtilityHelper
{
    public  static function checksDocumentsExpiry($documents, $expiryType = 'expiredDocs')
    {
        $nearExpiryDocs = [];
        $expiredDocs = [];
        foreach ($documents as $document) {
            if ($document->document_expiry) {
                $expiryDate = Carbon::parse($document->document_expiry);

                // Check if the document is already expired
                if (!$expiryDate->isPast()) {
                    $expiredDocs[$document->code][$document->document_name] = $document->document_name;
                }
                // Check if the document will expire within the next 2 days
                $twoDaysFromNow = Carbon::now()->addDays(2);
                if ($expiryDate->isFuture() && $expiryDate->lte($twoDaysFromNow)) {
                    $nearExpiryDocs[$document->code][$document->document_name] = $document->document_name;
                }
            }
        }
        if ($expiryType == 'nearExpiryDocs') {
            return $nearExpiryDocs;
        } else {
            return $expiredDocs;
        }
        return [];

        // You can now send notifications or handle the near expiry and expired documents
        // if (!empty($nearExpiryDocs)) {
        //     // Handle near expiry documents
        //     // For example, send an email notification
        //     Mail::to('admin@example.com')->send(new DocumentExpiryNotification($nearExpiryDocs));
        // }

        // if (!empty($expiredDocs)) {
        //     // Handle expired documents
        //     // For example, send an email notification
        //     Mail::to('admin@example.com')->send(new DocumentExpiryNotification($expiredDocs));
        // }
    }
}
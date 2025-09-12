<?php

namespace App\Helpers;

use App\Models\settings\Configsetting;
use Illuminate\Support\Facades\Log;

class ConfigHelper
{

  public static function getConfigValueInArray($key = '')
  {
    if ($key) {
      $value = Configsetting::where('key', $key)->first();
      if (isset($value->value)) {
        $value_array = json_decode($value->value, true);
        return $value_array ?? [];
      }
    }
    return [];
  }
  public static function updateConfigValue($key = '', $value = '')
  {
    if ($key && $value) {
      $data['value'] = $value;
      Configsetting::where('key', $key)->update($data);
    }
  }

  public static function getIndianStates()
  {
    $indianStates = [
      'Arunachal Pradesh' => 'Arunachal Pradesh',
      'Assam' => 'Assam',
      'Bihar' => 'Bihar',
      'Chhattisgarh' => 'Chhattisgarh',
      'Goa' => 'Goa',
      'Gujarat' => 'Gujarat',
      'Haryana' => 'Haryana',
      'Himachal Pradesh' => 'Himachal Pradesh',
      'Jammu and Kashmir' => 'Jammu and Kashmir',
      'Jharkhand' => 'Jharkhand',
      'Karnataka' => 'Karnataka',
      'Kerala' => 'Kerala',
      'Madhya Pradesh' => 'Madhya Pradesh',
      'Maharashtra' => 'Maharashtra',
      'Manipur' => 'Manipur',
      'Meghalaya' => 'Meghalaya',
      'Mizoram' => 'Mizoram',
      'Nagaland' => 'Nagaland',
      'Odisha' => 'Odisha',
      'Punjab' => 'Punjab',
      'Rajasthan' => 'Rajasthan',
      'Sikkim' => 'Sikkim',
      'Tamil Nadu' => 'Tamil Nadu',
      'Telangana' => 'Telangana',
      'Tripura' => 'Tripura',
      'Uttar Pradesh' => 'Uttar Pradesh',
      'Uttarakhand' => 'Uttarakhand',
      'West Bengal' => 'West Bengal',
      'Andaman and Nicobar Islands' => 'Andaman and Nicobar Islands',
      'Chandigarh' => 'Chandigarh',
      'Dadra and Nagar Haveli' => 'Dadra and Nagar Haveli',
      'Daman and Diu' => 'Daman and Diu',
      'Lakshadweep' => 'Lakshadweep',
      'National Capital Territory of Delhi' => 'National Capital Territory of Delhi',
      'Puducherry' => 'Puducherry'
    ];
    return $indianStates;
  }
  public static function getIndianStatesWithGSTCodes()
  {
    $indianStates = [
      'JK-01' => 'Jammu and Kashmir',
      'HP-02' => 'Himachal Pradesh',
      'PB-03' => 'Punjab',
      'CH-04' => 'Chandigarh',
      'UK-05' => 'Uttarakhand',
      'HR-06' => 'Haryana',
      'DL-07' => 'Delhi',
      'RJ-08' => 'Rajasthan',
      'UP-09' => 'Uttar Pradesh',
      'BR-10' => 'Bihar',
      'SK-11' => 'Sikkim',
      'AR-12' => 'Arunachal Pradesh',
      'NL-13' => 'Nagaland',
      'MN-14' => 'Manipur',
      'MZ-15' => 'Mizoram',
      'TR-16' => 'Tripura',
      'ML-17' => 'Meghalaya',
      'AS-18' => 'Assam',
      'WB-19' => 'West Bengal',
      'JH-20' => 'Jharkhand',
      'OD-21' => 'Odisha',
      'CG-22' => 'Chhattisgarh',
      'MP-23' => 'Madhya Pradesh',
      'GJ-24' => 'Gujarat',
      'DD-25' => 'Daman and Diu',
      'DN-26' => 'Dadra and Nagar Haveli',
      'MH-27' => 'Maharashtra',
      // 'AP-28' => 'Andhra Pradesh (Before Telangana Split)',
      'KA-29' => 'Karnataka',
      'GA-30' => 'Goa',
      'LD-31' => 'Lakshadweep',
      'KL-32' => 'Kerala',
      'TN-33' => 'Tamil Nadu',
      'PY-34' => 'Puducherry',
      'AN-35' => 'Andaman and Nicobar Islands',
      'TG-36' => 'Telangana',
      'AP-37' => 'Andhra Pradesh (New)',
      'LA-38' => 'Ladakh',
    ];

    return $indianStates;
  }
  public static function getIndianStatesName($state_code)
  {
    $statesWithCodes = self::getIndianStatesWithGSTCodes();

    return array_key_exists($state_code, $statesWithCodes) ? $statesWithCodes[$state_code] : '';
  }


  public static function getSMPTDetails()
  {
    $config = [
      'host' => 'sandbox.smtp.mailtrap.io',
      'port' => '587',
      'encryption' => 'tls',
      'username' => '0783188463375b',
      'password' => '854b7e6ab32a96',
      'from_name' => 'Sleep Company',
      'from_address' => 'sleep@company.com'
    ];
    return $config;
  }
  
}

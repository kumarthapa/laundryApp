<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportController extends Controller
{
  //


  public function import(Request $request)
  {

    // Log::debug($request->file('file_path'));
    $validator = Validator::make($request->all(), [
      'file' => 'required|file|mimes:csv,xls,xlsx,txt',
    ]);
    $file = $request->file('file');



    $extension = strtolower($file->getClientOriginalExtension());


    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => __('items_lang.items_excel_import_failed'),
      ]);
    }

    if ($request->file('file')->isValid()) {
      if ($extension == 'csv') {
        // It's a CSV file
        $csv_data = $this->parse_csv_file($file->path());
        // Log::info("I am csv data");
        // Log::info($csv_data);
      } elseif ($extension == "xls" || $extension == "xlsx") {

        $csv_data = $this->parse_excel_file($file->path());
        // Log::info("I am xls data");
        // Log::info($csv_data);
      }

      if ($csv_data) {
        $header = [];
        $body = [];

        $failCodes = [];

        foreach ($csv_data as $row => $row_data) {

          $data = $this->xss_clean($row_data);
          $data = array_map('trim', $data);
          $header = array_map('trim', $header);
          if (count($header) != 0) {
            $data = array_combine($header, $data);
          }
          if ($extension == "xls" || $extension == "xlsx") {

            if ($row == 0) {
              $header = $row_data;
              continue;
            }
          }
          $body[] = $data;
        }
        return response()->json(['body' => $body, 'header' => $header, 'csv_data' => $csv_data, 'success' => true, 'message' => 'donee!']);
      } else {

        return response()->json(array('success' => FALSE, 'message' => __('items_lang.items_excel_import_nodata_wrongformat')));
      }
    }
  }

  public static function parse_csv_file($csvfile)
  {
    $csv = array();
    $rowcount = 0;
    if (($handle = fopen($csvfile, "r")) !== FALSE) {
      $max_line_length = defined('MAX_LINE_LENGTH') ? 'MAX_LINE_LENGTH' : 10000;
      $header = fgetcsv($handle, $max_line_length);
      foreach ($header as $c => $_cols) {
        if ($c)
          $header[$c] = strtolower(str_replace(" ", "_", $_cols));
      }
      $header_colcount = count($header);
      while (($row = fgetcsv($handle, $max_line_length)) !== FALSE) {
        $row_colcount = count($row);
        if ($row_colcount == $header_colcount) {
          $entry = array_combine($header, $row);
          $csv[] = $entry;
        } else {
          //error_log("csvreader: Invalid number of columns at line " . ($rowcount + 2) . " (row " . ($rowcount + 1) . "). Expected=$header_colcount Got=$row_colcount");
          return null;
        }
        $rowcount++;
      }
      //echo "Totally $rowcount rows found\n";
      fclose($handle);
    } else {
      //error_log("csvreader: Could not read CSV \"$csvfile\"");
      return null;
    }
    return $csv;
  }

  public static function parse_excel_file($excelfile)
  {
    $excel = array();
    $rowcount = 0;

    try {
      $spreadsheet = IOFactory::load($excelfile);

      $worksheet = $spreadsheet->getActiveSheet();

      foreach ($worksheet->getRowIterator() as $row) {
        $entry = array();
        foreach ($row->getCellIterator() as $cell) {
          $entry[] = $cell->getValue();
        }
        $excel[] = $entry;
        $rowcount++;
      }
    } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
      // Handle the exception (e.g., file not found or invalid format)
      return null;
    }

    return $excel;
  }
}

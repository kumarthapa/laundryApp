<?php

namespace App\Helpers;

use PhpOffice\PhpSpreadsheet\IOFactory;

class FileImportHelper
{


  public static function getFileData($file)
  {

    $extension = strtolower($file->getClientOriginalExtension());

    if (!$file->isValid()) {
      return null;
    }

    if ($extension == 'csv') {
      // It's a CSV file
      $csv_data = FileImportHelper::parse_csv_file($file->path());
    } elseif ($extension == "xls" || $extension == "xlsx") {
      $csv_data = FileImportHelper::parse_excel_file($file->path());
    }

    if ($csv_data) {
      $header = [];
      $body = [];
      foreach ($csv_data as $row => $row_data) {
        $data = $row_data;
        $data = array_map('trim', $data);
        $header = array_map('trim', $header);
        if (count($header) != 0) {
          $data = array_combine($header, $data);
        }
        if (($extension == "xls" || $extension == "xlsx") && $row == 0 && isset($row_data)) {
          $header = $row_data;
          continue;
        }
        $body[] = $data;
      }
      return ['header' => $header, 'body' => $body];
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

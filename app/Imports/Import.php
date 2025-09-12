<?php

namespace App\Imports;

use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Row;

class Import implements OnEachRow, WithHeadingRow
{
  protected $headers = [];
  protected $data = [];

  public function onRow(Row $row)
  {
    Log::info("row:" . json_encode($row->toArray()));
    if (empty($this->headers)) {
      // Get headers on the first row
      $this->headers = array_keys($row->toArray());
    }
    // Push each row's data as an object
    // $this->data[] = (object)$row->toArray();

    Log::info("row:" . json_encode($row->toArray()));
    // Ensure the row is not empty before processing
    if (array_filter($row->toArray())) {
      // Store each row's data as an object
      $this->data[] = (object)$row->toArray();
    }
  }

  public function getHeaders()
  {
    return $this->headers;
  }

  public function getData()
  {
    return $this->data;
  }
}

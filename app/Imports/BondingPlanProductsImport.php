<?php

namespace App\Imports;

use App\Models\Products\Inventory;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class BondingPlanProductsImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        $product = Inventory::create([
            'sku'            => $row['sku'] ?? null,
            'product_name'   => $row['product_name'] ?? '',
            'size'           => $row['size'] ?? '',
            'reference_code' => $row['reference_code'] ?? '',
            'date'           => now()->toDateString(),
            'month'          => now()->format('m'),
            'year'           => now()->format('Y'),
            'serial_no'      => strtoupper(uniqid('SN')),
            'contractor'     => 'DEFAULT',
            'model'          => 'DEFAULT',
            'rfid_tag'       => strtoupper(uniqid('RFID')),
            'rfid_code'        => 'TEMP', // will update after ID
        ]);

        // Update qa_code after ID is available
        $product->update([
            'rfid_code' => $this->generateQaCode($product->id)
        ]);

        return $product;
    }

    private function generateQaCode($id)
    {
        return 'QA-' . date('Y') . date('m') . str_pad($id, 5, '0', STR_PAD_LEFT);
    }
}
<?php

namespace App\Exports;

use DB;

use App\Inventory;
use App\PurchaseRequestItems;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class InventoryExport implements FromArray, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function array(): array
    {
      $inventory = Inventory::select(
          'i_mspecs',
          DB::raw('IF(count(*) > 0,group_concat(i_code),i_code) as code'),
          DB::raw('IF(count(*) > 0,group_concat(i_partnumber),i_partnumber) as partnum'),
          DB::raw('IF(count(*) > 0,SUM(i_quantity),i_quantity) as quantity'),
          DB::raw('IF(count(*) > 0,"",i_unit) as unit'),
          DB::raw('IF(count(*) > 0,0,i_min) as min'),
          DB::raw('IF(count(*) > 0,0,i_max) as max')
        )
        ->groupBy('i_mspecs')
        ->orderBy('i_quantity','desc')
        ->get()
        ->map(function($item,$key) {

        $price = PurchaseRequestItems::has('pr.prpricing')
          ->where('pri_mspecs', $item->i_mspecs)
          ->latest('id')
          ->first();
        return array(
          'mspecs' => $item->i_mspecs,
          'partnum' => $item->partnum,
          'code' => $item->code,
          'unit' => $item->unit,
          'quantity' => $item->quantity,
          'price' => $price->pri_unitprice,
          'min' => $item->min,
          'max' => $item->max,
          'locations' => $item->locations()->get()->pluck('loc_description')->join(','),
          'priceSupplier' => $price->pr->prpricing->supplier->sd_supplier_name,
          'amount' => number_format(intval($item->quantity) * $price->pri_unitprice,2)  
        );
      })->toArray();

        return $inventory;
    }

    public function headings(): array
    {
      return [
        'MATERIAL SPECIFICATION',
        'PART NUMBER',
        'CODE',
        'UNIT',
        'QUANTITY',
        'UNIT PRICE',
        'MIN',
        'MAX',
        'LOCATIONS',
        'SUPPLIER(FOR PRICE)',
        'AMOUNT'
      ];
     
    }
}

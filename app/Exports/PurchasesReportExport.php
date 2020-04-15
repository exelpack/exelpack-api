<?php

namespace App\Exports;

use App\PurchaseOrderSupplierItems;
use App\Supplier;
use Carbon\Carbon;
use DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PurchasesReportExport implements FromArray, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    protected $date = array();
    public function __construct($date1, $date2)
    {
      $this->date[0] = $date1;
      $this->date[1] = $date2;
    }

    public function getSupplierTotal($id, $date){
      $date1 = Carbon::parse($date[0]);
      $date2 = Carbon::parse($date[1]);
      return PurchaseOrderSupplierItems::whereHas('spo.prprice.supplier', function($q) use ($id){
        $q->where('id', $id);
      })
      ->get()
      ->map(function($item) use ($date1, $date2) {
        $totalPhp = 0;
        $totalUsd = 0;
        $currency =  $item->spo->prprice()->first()->prsd_currency;
        $totalReceived = $item->invoice()
          ->whereBetween('ssi_date', [$date1, $date2])
          ->sum('ssi_receivedquantity');

        if(strtoupper($currency) == 'PHP')
          $totalPhp+= $item->spoi_unitprice * $totalReceived;
        else
          $totalUsd+= ($item->spoi_unitprice * $totalReceived) * 47;

        return array(
          'totalPhp' => $totalPhp,
          'totalUsd' => $totalUsd
        );
      })
      ->toArray();
    }

    public function array(): array
    {
      $invoice = DB::table('psms_supplierinvoice')->select(
        DB::raw('sum(ssi_receivedquantity) as totalReceivedQty'),
        'ssi_poitem_id'
      )->groupBy('ssi_poitem_id');

      $date = $this->date;
      $supplierTotal = Supplier::all()
        ->map(function($supplier) use($date) {

          $total = $this->getSupplierTotal($supplier->id, $date);
          return array(
            'supplierName' => $supplier->sd_supplier_name,
            'totalPhp' => array_sum(array_column($total, 'totalPhp')),
            'totalUsd' => array_sum(array_column($total, 'totalUsd')),
          );
        })
        ->sortByDesc('totalPhp')
        ->values()
        ->toArray();

      return $supplierTotal;
    }

    public function headings():array
    {
      return [
        'SUPPLIER',
        'TOTAL PURCHASED IN PHP',
        'TOTAL PURCHASED IN USD',
      ];
    }
}

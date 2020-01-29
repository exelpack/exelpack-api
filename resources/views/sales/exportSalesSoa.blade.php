<?php

use Carbon\Carbon;

$total = 0;

$aging_0 = 0;
$aging_60 = 0;
$aging_90 = 0;
$aging_120 = 0;
$aging_190 = 0;
$aging_280 = 0;


$due_total = 0;

if(strtolower($currency) === 'usd'){
  $currency_sign = '$';
  $decimal = 4;
}else{
  $currency_sign = 'PHP';
  $decimal = 2;
}

$terms = array();

for($i = 0; $i <= 5; $i++){
    $val = 0;

    if($i === 0){
        $val = $pt - 1;
    }else{

        $val = $pt + (40 * $i);
        $val--;

    }

    array_push($terms,$val);
}


?>

<style>
  .bg-col{
    border: 1px solid #2d3436;
  }

  .head{
    text-align: center;
    background: #A8BF77;
  }


</style>

<table cellpadding="10" cellspacing="10">
  <tr>
    <td>DATE</td>
    <td>:</td>
    <td colspan="3">{{ date('M d, Y') }}</td>
  </tr>
  <tr>
    <td>TO</td>
    <td>:</td>
    <td  colspan="3"> {{ $customername }}</td>
  </tr> 
  <tr>
    <td>ATTN</td>
    <td>:</td>
    <td  colspan="3">Accounting Department</td>
  </tr> 
  <tr>
    <td>FROM</td>
    <td>:</td>
    <td  colspan="3">Exelpack Corporation</td>
  </tr>
  <tr>
    <td></td>
    <td></td>
    <td  colspan="3">Blk 2 Lot 2 Filinvest Technology Park</td>
  </tr>
</table>


<table  style="text-align:center">
  <thead>
    <tr>
      <td class="head" colspan="8">STATEMENT OF ACCOUNT</td> 
    </tr>
    <tr>
      <td class="head" colspan="8">FOR THE MONTH OF {{ date('F Y') }} </td> 
    </tr>
  </thead>
</table>


<table border="1" style="text-align:center">
  <thead>
    <tr>
      <th style="background: #8E8E8E; color:#ffffff;">Delivery Date</th>
      <th style="background: #8E8E8E; color:#ffffff;">SI NO.</th>
      <th style="background: #8E8E8E; color:#ffffff;">DR NO.</th>
      <th style="background: #8E8E8E; color:#ffffff;">PO NO.</th>
      <th style="background: #8E8E8E; color:#ffffff;">PART NUMBER</th>
      <th style="background: #8E8E8E; color:#ffffff;">DUE DATE</th>
      <th style="background: #8E8E8E; color:#ffffff;">Sum of Amount</th>
      <th style="background: #8E8E8E; color:#ffffff;">No. of days from date delivered</th>
    </tr>
  </thead>
  <tbody>
    @foreach($data as $row)

    @php

      $datenow = Carbon::now();
      $delivered = new Carbon($row->sales->s_deliverydate);
      $diff = $delivered->diffInDays($datenow);
      $due = $delivered->addDays($pt)->format('Y-m-d');

      $row_amount = $row->sitem_totalamount;

      $total+= $row_amount;

      if($diff > $terms[4])
        $aging_280+= $row_amount;
      else if($diff > $terms[3]) 
        $aging_190+= $row_amount;
      else if($diff > $terms[2]) 
        $aging_120+= $row_amount; 
      else if($diff > $terms[1]) 
        $aging_90+= $row_amount; 
      else if($diff > $terms[0]) 
        $aging_60+= $row_amount; 
      else if($diff <= $terms[0])
        $aging_0+= $row_amount;

      if($diff >= $pt)
        $due_total+= $row_amount;

    @endphp
      <tr>
        <td  style="background: #B0B0B0; color:#ffffff;" class="bg-col">
          {{ $row->sales->s_deliverydate }}</td>
        <td  style="background: #E5E5E5;" class="bg-col">{{ $row->sales->s_invoicenum }}</td>
        <td class="bg-col">{{ $row->sitem_drnum }}</td>
        <td  style="background: #E5E5E5;" class="bg-col">{{ $row->sitem_ponum }}</td>
        <td class="bg-col"  style="text-align:left" >{{ $row->sitem_partnum }}</td>
        <td class="bg-col">{{ $due }}</td>
        <td style="text-align:right" class="bg-col">{{ number_format($row_amount,$decimal) }}</td>
        <td class="bg-col">{{ $diff }}</td>
      </tr>
    @endforeach
    <tr>
      <td class="bg-col" colspan="8"></td>
    </tr>
    <tr>
      <td class="bg-col"  style="text-align:left" colspan="6">Grand Total</td>
      <td style="text-align:right" class="bg-col">{{ $currency_sign }} {{ number_format($total,$decimal) }}</td>
      <td class="bg-col"></td>
    </tr>
  </tbody> 
</table>

<table cellpadding="10" cellspacing="10">
  <tr>
    <td>Aging analysis:</td>
  </tr>
  <tr>
    <td>Payment Terms :  {{ $pt }}</td>
  </tr>
</table>

<table style="text-align:center">
  <thead>
    <tr>
      <td class="bg-col" rowspan="2">No. of Days from Date Delivered</td>
      <td class="bg-col" rowspan="2">0-{{ $terms[0] }}</td>
      <td class="bg-col" colspan="6" style="text-align: center">PAST DUE</td>
    </tr>   
    <tr>
      <td class="bg-col">{{ $terms[0] + 1 }}-{{ $terms[1] }}</td>
      <td class="bg-col">{{ $terms[1] + 1 }}-{{ $terms[2] }}</td>
      <td class="bg-col">{{ $terms[2] + 1 }}-{{ $terms[3] }}</td>
      <td class="bg-col">{{ $terms[3] + 1 }}-{{ $terms[4] }}</td>
      <td class="bg-col">{{ $terms[4] + 1 }}-{{ $terms[5] }} UP</td>
      <td class="bg-col">TOTAL DUE FOR PAYMENT</td>
    </tr>

  </thead>
  <tbody>
    <tr>
      <td class="bg-col">Amount</td>
      <td class="bg-col">{{ $aging_0 }}</td>
      <td class="bg-col">{{ $aging_60 }}</td>
      <td class="bg-col">{{ $aging_90 }}</td>
      <td class="bg-col">{{ $aging_120 }}</td>
      <td class="bg-col">{{ $aging_190 }}</td>
      <td class="bg-col">{{ $aging_280 }}</td>
      <td class="bg-col">{{ $currency_sign }} {{ number_format($due_total,$decimal) }}</td>
    </tr>
  </tbody>
</table>

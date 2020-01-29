<?php

use Carbon\Carbon;

$total_php = 0;
$total_usd = 0;

$aging_0_php = 0;
$aging_60_php = 0;
$aging_90_php = 0;
$aging_120_php = 0;
$aging_190_php = 0;
$aging_280_php = 0;

$aging_0_usd = 0;
$aging_60_usd = 0;
$aging_90_usd = 0;
$aging_120_usd = 0;
$aging_190_usd = 0;
$aging_280_usd = 0;


$due_total_php = 0;
$due_total_usd = 0;

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

  .center{
    text-align:center;
    border: 1px solid #2d3436;
  }


</style>

<table cellpadding="10" cellspacing="10">
  <tr>
    <td></td>
    <td></td>
    <td colspan="3"></td>
  </tr>
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


<table style="text-align:center">
  <thead>
    <tr>
      <td class="head" colspan="9">STATEMENT OF ACCOUNT</td> 
    </tr>
    <tr>
      <td class="head" colspan="9">FOR THE MONTH OF {{ date('F Y') }} </td> 
    </tr>
  </thead>
</table>

<table border="1"  style="text-align:center">
  <thead>
    <tr style="background: #8E8E8E; color:#ffffff;">
      <td class="bg-col">Delivery Date</td>
      <td class="bg-col">SI NO.</td>
      <td class="bg-col">DR NO.</td>
      <td class="bg-col">PO NO.</td>
      <td class="bg-col">PART NUMBER</td>
      <td class="bg-col">DUE DATE</td>
      <td class="bg-col">Sum of Amount USD</td>
      <td class="bg-col">Sum of Amount PHP</td>
      <td class="bg-col">No. of days from date delivered</td>
    </tr>
  </thead>
  <tbody>
    @foreach($data as $row)
    
    @php
      $__usd = 0;
      $__php = 0;

      $datenow = Carbon::now();
      $delivered = new Carbon($row->sales->s_deliverydate);
      $diff = $delivered->diffInDays($datenow);
      $due = $delivered->addDays($pt)->format('Y-m-d');

      if($row->sales->s_currency == 'USD')
      {
        $__usd = $row->sitem_totalamount;
        $total_usd+= $__usd;


        if($diff > $terms[4]){
          $aging_280_usd+= $__usd;
        }
        else if($diff > $terms[3]){
          $aging_190_usd+= $__usd;
        }
        else if($diff > $terms[2]){
          $aging_120_usd+= $__usd; 
        }
        else if($diff > $terms[1]){
          $aging_90_usd+= $__usd; 
        }
        else if($diff > $terms[0]){
          $aging_60_usd+= $__usd; 
        }
        else if($diff <= $terms[0]){
          $aging_0_usd+= $__usd;
        }

        if($diff >= $pt)
          $due_total_usd+= $__usd;

      }
      else if($row->sales->s_currency == 'PHP')
      {
        $__php = $row->sitem_totalamount;
        $total_php+= $__php;


        if($diff > $terms[4]){
          $aging_280_php+= $__php;
        }
        else if($diff > $terms[3]){
          $aging_190_php+= $__php;
        }
        else if($diff > $terms[2]){
          $aging_120_php+= $__php; 
        }
        else if($diff > $terms[1]){
          $aging_90_php+= $__php; 
        }
        else if($diff > $terms[0]){
          $aging_60_php+= $__php; 
        }
        else if($diff <= $terms[0]){
          $aging_0_php+= $__php;
        }

        if($diff >= $pt)
          $due_total_php+= $__php;

      }   

    @endphp
      <tr>
        <td  style="background: #B0B0B0; color:#ffffff;" class="bg-col">
          {{ $row->sales->s_deliverydate }}</td>
        <td  style="background: #E5E5E5;" class="bg-col">{{ $row->sales->s_invoicenum }}</td>
        <td class="bg-col">{{ $row->sitem_drnum }}</td>
        <td  style="background: #E5E5E5;" class="bg-col">{{ $row->sitem_ponum }}</td>
        <td class="bg-col"  style="text-align:left" >{{ $row->sitem_partnum }}</td>
        <td class="bg-col">{{ $due }}</td>
        <td style="text-align:right" class="bg-col">$ {{ number_format($__usd,4) }}</td>
        <td style="text-align:right" class="bg-col">PHP {{ number_format($__php,2) }}</td>
        <td style="text-align:center" class="bg-col">{{ $diff }}</td>
      </tr>
    @endforeach

    <tr>
      <td class="bg-col" colspan="6">Grand Total</td>
      <td style="text-align:right" class="bg-col">$ {{ number_format($total_usd,4) }}</td>
      <td style="text-align:right" class="bg-col">PHP {{ number_format($total_php,2) }}</td>
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

<table >
  <thead>
    <tr>
      <td class="bg-col" colspan="2" rowspan="2">No. of Days from Date Delivered</td>
      <td  class="center" colspan="7">PAST DUE</td>
    </tr>
    <tr>
      <td class="bg-col">0-{{ $terms[0] }}</td>
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
      <td class="bg-col"  colspan="2">PHP</td>
      <td class="bg-col">{{ $aging_0_php }}</td>
      <td class="bg-col">{{ $aging_60_php }}</td>
      <td class="bg-col">{{ $aging_90_php }}</td>
      <td class="bg-col">{{ $aging_120_php }}</td>
      <td class="bg-col">{{ $aging_190_php }}</td>
      <td class="bg-col">{{ $aging_280_php }}</td>
      <td style="text-align: right" class="bg-col">PHP {{ number_format($due_total_php,2) }}</td>
    </tr>
    <tr>
      <td class="bg-col"  colspan="2">USD</td>
      <td class="bg-col">{{ $aging_0_usd }}</td>
      <td class="bg-col">{{ $aging_60_usd }}</td>
      <td class="bg-col">{{ $aging_90_usd }}</td>
      <td class="bg-col">{{ $aging_120_usd }}</td>
      <td class="bg-col">{{ $aging_190_usd }}</td>
      <td class="bg-col">{{ $aging_280_usd }}</td>
      <td style="text-align: right" class="bg-col">$ {{ number_format($due_total_usd,4) }}</td>
    </tr>
  </tbody>
</table>
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

<table cellpadding="10" border="1" cellspacing="10">
  <tr>
    <td colspan="5" rowspan="5"><img src="img/logo.png" width="50px" height="50px" /></td>
    <td>Blk2 Lot2 Filinvest Technology Park</td>
  </tr>
  <tr>
    <td>Ciudad De Calamba</td>
  </tr>
  <tr>
    <td>Calamba City, Laguna</td>
  </tr>
  <tr>
    <td>Email: exelpack@gmail.com</td>
  </tr>
  <tr>
    <td>Telefax No. 049-502-0295</td>
  </tr>
</table>

<table cellpadding="10" border="1" cellspacing="10">
  <tr>
    <td>&nbsp;</td>
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
    <tr>
      <td style="background: #8E8E8E; color:#ffffff;border:1px solid black;" class="bg-col">Delivery Date</td>
      <td style="background: #8E8E8E; color:#ffffff;border:1px solid black;">SI NO.</td>
      <td style="background: #8E8E8E; color:#ffffff;border:1px solid black;">DR NO.</td>
      <td style="background: #8E8E8E; color:#ffffff;border:1px solid black;">PO NO.</td>
      <td style="background: #8E8E8E; color:#ffffff;border:1px solid black;">PART NUMBER</td>
      <td style="background: #8E8E8E; color:#ffffff;border:1px solid black;">DUE DATE</td>
      <td style="background: #8E8E8E; color:#ffffff;border:1px solid black;">Sum of Amount USD</td>
      <td style="background: #8E8E8E; color:#ffffff;border:1px solid black;">Sum of Amount PHP</td>
      <td style="background: #8E8E8E; color:#ffffff;border:1px solid black;">No. of days from date delivered</td>
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
        <td  style="background: #B0B0B0; border:1px solid black; color:#ffffff;" class="bg-col">
          {{ $row->sales->s_deliverydate }}</td>
        <td  style="background: #E5E5E5; border:1px solid black;" class="bg-col">{{ $row->sales->s_invoicenum }}</td>
        <td style=" border:1px solid black;" class="bg-col">{{ $row->sitem_drnum }}</td>
        <td  style="background: #E5E5E5; border:1px solid black;" class="bg-col">{{ $row->sitem_ponum }}</td>
        <td style="text-align:left;  border:1px solid black;" >{{ $row->sitem_partnum }}</td>
        <td style=" border:1px solid black;" class="bg-col">{{ $due }}</td>
        <td style="text-align:right;border:1px solid black;" class="bg-col">$ {{ number_format($__usd,4) }}</td>
        <td style="text-align:right;border:1px solid black;" class="bg-col">PHP {{ number_format($__php,2) }}</td>
        <td style="text-align:center;border:1px solid black;" class="bg-col">{{ $diff }}</td>
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
      <td style="border:1px solid black;" rowspan="2">No. of Days from Date Delivered</td>
      <td style="border:1px solid black; text-align : center;" colspan="7">PAST DUE</td>
    </tr>
    <tr>
      <td style="border:1px solid black;" >0-{{ $terms[0] }}</td>
      <td style="border:1px solid black;">{{ $terms[0] + 1 }}-{{ $terms[1] }}</td>
      <td style="border:1px solid black;">{{ $terms[1] + 1 }}-{{ $terms[2] }}</td>
      <td style="border:1px solid black;">{{ $terms[2] + 1 }}-{{ $terms[3] }}</td>
      <td style="border:1px solid black;">{{ $terms[3] + 1 }}-{{ $terms[4] }}</td>
      <td style="border:1px solid black;">{{ $terms[4] + 1 }}-{{ $terms[5] }} UP</td>
      <td style="border:1px solid black;">TOTAL DUE FOR PAYMENT</td>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td style="border:1px solid black;">PHP</td>
      <td style="border:1px solid black;">{{ number_format($aging_0_php,2) }}</td>
      <td style="border:1px solid black;">{{ number_format($aging_60_php,2) }}</td>
      <td style="border:1px solid black;">{{ number_format($aging_90_php,2) }}</td>
      <td style="border:1px solid black;">{{ number_format($aging_120_php,2) }}</td>
      <td style="border:1px solid black;">{{ number_format($aging_190_php,2) }}</td>
      <td style="border:1px solid black;">{{ number_format($aging_280_php,2) }}</td>
      <td style="text-align: right; border:1px solid black;">PHP {{ number_format($due_total_php,2) }}</td>
    </tr>
    <tr>
      <td style="border:1px solid black;">USD</td>
      <td style="border:1px solid black;">{{ number_format($aging_0_usd,4) }}</td>
      <td style="border:1px solid black;">{{ number_format($aging_60_usd,4) }}</td>
      <td style="border:1px solid black;">{{ number_format($aging_90_usd,4) }}</td>
      <td style="border:1px solid black;">{{ number_format($aging_120_usd,4) }}</td>
      <td style="border:1px solid black;">{{ number_format($aging_190_usd,4) }}</td>
      <td style="border:1px solid black;">{{ number_format($aging_280_usd,4) }}</td>
      <td style="text-align: right; border:1px solid black;" class="bg-col">$ {{ number_format($due_total_usd,4) }}</td>
    </tr>
  </tbody>
</table>

<table >
  <tbody>
    <tr>
      <td colspan="3" align="center">Prepare By:__________________</td>
      <td colspan="3" align="center">Checked By:__________________</td>
      <td colspan="3" align="center">Approved By:__________________</td>
    </tr>
    <tr>
      <td colspan="3" align="center">INSERTNAME</td>
      <td colspan="3" align="center">INSERTNAME</td>
      <td colspan="3" align="center">Jasper A. Cabuntocan</td>
    </tr>
    <tr>
      <td colspan="3" align="center">Acctg. Staff</td>
      <td colspan="3" align="center">Acctg. Head</td>
      <td colspan="3" align="center">General Manager</td>
    </tr>
    <tr>
      <td colspan="9" align="center"></td>
    </tr>
    <tr>
      <td colspan="9" class="head">
      This is your outstanding balance for the month of {{ date('F') }} as per our record. If the amount is correct please sign in the space below. 
      </td>
    </tr>
    <tr>
      <td colspan="9" class="head">
      Should there be any discrepancy please state so and send to EXELPACK CORPORATION through our Fax No. 049-502-0295 loc. 103.
      </td>
    </tr>
    <tr>
      <td colspan="9" align="center"></td>
    </tr>
    <tr>
      <td colspan="3" align="center">Customer's Autorized Representative:</td>
      <td colspan="2">_____________________</td>
    </tr>
    <tr>
      <td colspan="3" align="center">Date of Acceptance:</td>
      <td colspan="2">_____________________</td>
    </tr>
  </tbody>
</table>
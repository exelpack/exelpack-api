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
      <th style="background: #8E8E8E; color:#ffffff;border:1px solid black;">Delivery Date</th>
      <th style="background: #8E8E8E; color:#ffffff;border:1px solid black;">SI NO.</th>
      <th style="background: #8E8E8E; color:#ffffff;border:1px solid black;">DR NO.</th>
      <th style="background: #8E8E8E; color:#ffffff;border:1px solid black;">PO NO.</th>
      <th style="background: #8E8E8E; color:#ffffff;border:1px solid black;">PART NUMBER</th>
      <th style="background: #8E8E8E; color:#ffffff;border:1px solid black;">DUE DATE</th>
      <th style="background: #8E8E8E; color:#ffffff;border:1px solid black;">Sum of Amount</th>
      <th style="background: #8E8E8E; color:#ffffff;border:1px solid black;">No. of days from date delivered</th>
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
        <td  style="background: #B0B0B0; border:1px solid black; color:#ffffff;" class="bg-col">
          {{ $row->sales->s_deliverydate }}</td>
        <td  style="background: #E5E5E5; border:1px solid black;" class="bg-col">{{ $row->sales->s_invoicenum }}</td>
        <td style=" border:1px solid black;" class="bg-col">{{ $row->sitem_drnum }}</td>
        <td  style="background: #E5E5E5; border:1px solid black;" class="bg-col">{{ $row->sitem_ponum }}</td>
        <td style="text-align:left;  border:1px solid black;" >{{ $row->sitem_partnum }}</td>
        <td style=" border:1px solid black;" class="bg-col">{{ $due }}</td>
        <td style="text-align:right; border:1px solid black;" class="bg-col">{{ number_format($row_amount,$decimal) }}</td>
        <td style=" border:1px solid black;" class="bg-col">{{ $diff }}</td>
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
      <td style="border:1px solid black;">Amount</td>
      <td style="border:1px solid black;">{{ number_format($aging_0,2) }}</td>
      <td style="border:1px solid black;">{{ number_format($aging_60,2) }}</td>
      <td style="border:1px solid black;">{{ number_format($aging_90,2) }}</td>
      <td style="border:1px solid black;">{{ number_format($aging_120,2) }}</td>
      <td style="border:1px solid black;">{{ number_format($aging_190,2) }}</td>
      <td style="border:1px solid black;">{{ number_format($aging_280,2) }}</td>
      <td style="border:1px solid black;">{{ $currency_sign }} {{ number_format($due_total,$decimal) }}</td>
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

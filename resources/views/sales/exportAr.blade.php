@php
use App\Http\Controllers\SalesController;
use Carbon\Carbon;

$count = count($collected_dates);
$cols = 12;
if($count > 0){
  $cols+= $count +2;
}

@endphp

<style>
  .bg-col{
    text-align:right;
    border: 1px solid #2d3436;
  }

  .bg-head{
    text-align:center;
    border: 1px solid #2d3436;
  }

  .head{
    text-align: center;
  }

  .center{
    border: 1px solid #2d3436;
  }


</style>
<table  style="text-align:center">
  <thead>
    <tr>
      <td class="head" colspan="{{ $cols }}">ACCOUNT RECEIVABLE</td> 
    </tr>
    <tr>
      <td class="head" colspan="{{ $cols }}">FOR THE YEAR {{ Carbon::createFromDate($year,$month)->endOfMonth()->format('F Y') }} </td> 
    </tr>
  </thead>
</table>

<table border="1">
  <thead>
    <tr>
      <th style="background-color : #B0B0B0" class="bg-head" colspan="3"></th>
      <th style="background-color : #B0B0B0" class="bg-head" colspan="6">AGING ANALYSIS</th>
      <th style="background-color : #B0B0B0" class="bg-head">{{ Carbon::createFromDate($year,$month)->endOfMonth()->format('F') }}</th>
      <th style="background-color : #B0B0B0" class="bg-head"></th>
      @foreach($collected_dates as $dates)
        <th style="background-color : #B0B0B0" class="bg-head">{{ Carbon::parse($dates)->format('d-M') }}</th>
        @if($loop->last)
        <th style="background-color : #B0B0B0" class="bg-head">TOTAL</th>
        <th style="background-color : #B0B0B0" class="bg-head">{{ Carbon::createFromDate($year,$month)->endOfMonth()->format('F') }}</th>
        @endif
      @endforeach
      <th style="background-color : #B0B0B0" class="bg-head"></th>
    </tr>
    <tr>
      <th style="background-color : #B0B0B0" class="bg-head">Customer Name</th>
      <th style="background-color : #B0B0B0" class="bg-head">Overall account receivable</th>
      <th style="background-color : #B0B0B0" class="bg-head">Payment terms</th>
      <th style="background-color : #B0B0B0" class="bg-head">0-29 days</th>
      <th style="background-color : #B0B0B0" class="bg-head">30-59 days</th>
      <th style="background-color : #B0B0B0" class="bg-head">60-89 days</th>
      <th style="background-color : #B0B0B0" class="bg-head">90-119 days</th>
      <th style="background-color : #B0B0B0" class="bg-head">Over 120 days</th>
      <th style="background-color : #B0B0B0" class="bg-head">Over a year</th>
      <th style="background-color : #B0B0B0" class="bg-head">Account receivable for the month</th>
      <th style="background-color : #B0B0B0"class="bg-head">Total remaining balance</th>
      @foreach($collected_dates as $dates)
        <th style="background-color : #B0B0B0" class="bg-head">Collected</th>
        @if($loop->last)
          <th style="background-color : #B0B0B0" class="bg-head">Total Collected</th>
          <th style="background-color : #B0B0B0" class="bg-head">Collection %</th>
        @endif
      @endforeach
      <th style="background-color : #B0B0B0" class="bg-head">Remarks</th>
    </tr>
  </thead>
  <tbody>
    @foreach($amounts as $data)

    @php
      $crncy = $data['currency'] === 'USD' ? '$' : '';
      $isCollected = false;

      if($data['perc_collected'] == '100.00%'){
        $isCollected = true;
      }
    @endphp
      <tr>
        <td class="bg-head">{{ $data['companyname'] }} {{ $data['currency'] }}  </td>
        <td class="bg-col">{{ $data['overall'] }}</td>
        <td class="bg-col">{{ $data['payment_terms'] }}</td>
        <td class="bg-col">{{ $data['aging_first'] }}</td>
        <td class="bg-col">{{ $data['aging_second'] }}</td>
        <td class="bg-col">{{ $data['aging_third'] }}</td>
        <td class="bg-col">{{ $data['aging_fourth'] }}</td>
        <td class="bg-col">{{ $data['aging_fifth'] }}</td>
        <td class="bg-col">{{ $data['aging_six'] }}</td>
        <td style="background-color : #feca57" class="bg-col">{{ $data['total_collectibles_month'] }}</td>
        <td style="background-color : #ecf0f1" class="bg-col">{{ $data['total_remaining_balance'] }}</td>
        

        @foreach($collected_dates as $date)
          @php
            
            $collected_amt = SalesController::getCollectedAmount($date,$data['c_id'],$data['currency']);
            
            $crcy = '';
            //check  currency
            if($data['currency'] == "PHP"){
              //increment dynamic variable if the variable already exist
              if(isset(${$date."_php"})){
                ${$date."_php"}+=$collected_amt;
              }else{
                //create dynamic variable if not exist
                ${$date."_php"} =  0;
                ${$date."_php"}+=$collected_amt;
              }


            }else{
              
              if(isset(${$date."_usd"})){
                ${$date."_usd"}+=$collected_amt;
              }else{
                ${$date."_usd"} =  0;
                ${$date."_usd"}+=$collected_amt;
              }

              // $crcy = '';
    
            }

            
          @endphp

          <td class="bg-col">{{ $collected_amt != 0 ? $crcy.' '. number_format($collected_amt,2)
            : '' }}</td>

          @if($loop->last)
            <td class="bg-col">{{ $data['amt_collected'] }}</td>
            <td style="color : #c0392b" class="bg-col">{{ $data['perc_collected'] }}</td>
          @endif
        @endforeach
        <td style="background-color: {{ $isCollected ? '#feca57' : '' }}" class="bg-head">{{ $isCollected ? 'Collected' : '' }}</td>
      </tr>

    @endforeach
    
  </tbody>
</table>

<br/>
<table>
  <tbody>
    <tr>
      <td class="bg-head"><b>TOTAL</b> PHP</td>
      <td class="bg-col">{{ number_format($total_overall_php,2) }}</td>
      <td class="bg-col"></td>
      <td class="bg-col">{{ number_format($aging_first_php,2) }}</td>
      <td class="bg-col">{{ number_format($aging_second_php,2) }}</td>
      <td class="bg-col">{{ number_format($aging_third_php,2) }}</td>
      <td class="bg-col">{{ number_format($aging_fourth_php,2) }}</td>
      <td class="bg-col">{{ number_format($aging_fifth_php,2) }}</td>
      <td class="bg-col">{{ number_format($aging_six_php,2) }}</td>
      <td class="bg-col">{{ number_format($receivable_of_month_php,2) }}</td>
      <td class="bg-col">{{ number_format($total_remaining_php,2) }}</td>
      @foreach($collected_dates as $dates)
        <td class="bg-col">{{ 
          isset(${$date."_php"})
          ? number_format(${$date."_php"},2) 
          : 0
      }}</td>

      @if($loop->last)
          <td class="bg-col">{{ number_format($total_collected_php,2) }}</td>
          <td class="bg-col"></td>
        @endif
      @endforeach
      
      <td class="bg-col"></td>
    </tr>
    <tr>
      <td class="bg-head"><b>TOTAL</b> USD</td>
      <td class="bg-col">{{ number_format($total_overall_usd,2) }}</td>
      <td class="bg-col"></td>
      <td class="bg-col">{{ number_format($aging_first_usd,2) }}</td>
      <td class="bg-col">{{ number_format($aging_second_usd,2) }}</td>
      <td class="bg-col">{{ number_format($aging_third_usd,2) }}</td>
      <td class="bg-col">{{ number_format($aging_fourth_usd,2) }}</td>
      <td class="bg-col">{{ number_format($aging_fifth_usd,2) }}</td>
      <td class="bg-col">{{ number_format($aging_six_usd,2) }}</td>
      <td class="bg-col">{{ number_format($receivable_of_month_usd,2) }}</td>
      <td class="bg-col">{{ number_format($total_remaining_usd,2) }}</td>
      @foreach($collected_dates as $dates)
        <td class="bg-col">{{ 
          isset(${$date."_usd"}) 
          ? number_format(${$date."_usd"},2) 
          : 0
      }}</td>
      @if($loop->last)
          <td class="bg-col">{{ number_format($total_collected_usd,2) }}</td>
          <td class="bg-col"></td>
        @endif
      @endforeach
      <td class="bg-col"></td>
    </tr>
    <tr>
      <td class="bg-head"></td>
      <td class="bg-col">{{ number_format($total_overall_usd * $conversion,2) }}</td>
      <td class="bg-col"></td>
      <td class="bg-col">{{ number_format($aging_first_usd * $conversion,2) }}</td>
      <td class="bg-col">{{ number_format($aging_second_usd * $conversion,2) }}</td>
      <td class="bg-col">{{ number_format($aging_third_usd * $conversion,2) }}</td>
      <td class="bg-col">{{ number_format($aging_fourth_usd * $conversion,2) }}</td>
      <td class="bg-col">{{ number_format($aging_fifth_usd * $conversion,2) }}</td>
      <td class="bg-col">{{ number_format($aging_six_usd * $conversion,2) }}</td>
      <td class="bg-col">{{ number_format($receivable_of_month_usd * $conversion,2) }}</td>
      <td class="bg-col">{{ number_format($total_remaining_usd * $conversion,2) }}</td>
      @foreach($collected_dates as $dates)
        <td class="bg-col">{{ 
        isset(${$date."_usd"}) 
        ? number_format(${$date."_usd"} * $conversion,2) 
        : 0
      }}</td>
        @if($loop->last)
          <td class="bg-col">{{ number_format($total_collected_usd * $conversion,2) }}</td>
          <td class="bg-col"></td>
        @endif
      @endforeach
      <td class="bg-col"></td>
    </tr>
    <tr>
      <td style="background-color : #B0B0B0" class="bg-head"><b>GRAND TOTAL</b></td>
      <td style="background-color : #B0B0B0" class="bg-col">{{ number_format(($total_overall_usd * $conversion) + $total_overall_php,2) }}</td>
      <td style="background-color : #B0B0B0" class="bg-col"></td>
      <td style="background-color : #B0B0B0" class="bg-col">{{ number_format(($aging_first_usd * $conversion) + $aging_first_php,2) }}</td>
      <td style="background-color : #B0B0B0" class="bg-col">{{ number_format(($aging_second_usd * $conversion) + $aging_second_php,2) }}</td>
      <td style="background-color : #B0B0B0" class="bg-col">{{ number_format(($aging_third_usd * $conversion) + $aging_third_php,2) }}</td>
      <td style="background-color : #B0B0B0" class="bg-col">{{ number_format(($aging_fourth_usd * $conversion) + $aging_fourth_php,2) }}</td>
      <td style="background-color : #B0B0B0" class="bg-col">{{ number_format(($aging_fifth_usd * $conversion) + $aging_fifth_php,2) }}</td>
      <td style="background-color : #B0B0B0" class="bg-col">{{ number_format(($aging_six_usd * $conversion) + $aging_six_php,2) }}</td>
      <td style="background-color : #B0B0B0" class="bg-col">{{ number_format(($receivable_of_month_usd * $conversion) + $receivable_of_month_php,2) }}</td>
      <td style="background-color : #B0B0B0" class="bg-col">{{ number_format(($total_remaining_usd * $conversion) + $total_remaining_php,2) }}</td>
      @foreach($collected_dates as $dates)
        @php
          if(isset(${$date."_usd"}))
            $usd_ = ${$date."_usd"};
          else
            $usd_ = 0;

          if(isset(${$date."_php"}))
            $php_ = ${$date."_php"};
          else
            $php_ = 0;

        @endphp
        <td style="background-color : #B0B0B0" class="bg-col">{{ 
        number_format(($usd_ * $conversion) + $php_,2) }}</td>

        @if($loop->last)
          <td class="bg-col">{{ number_format(($total_collected_usd * $conversion) + $total_collected_php,2) }}</td>
          <td class="bg-col"></td>
        @endif
      @endforeach
      <td style="background-color : #B0B0B0" class="bg-col"></td>
    </tr>
  </tbody>
</table>

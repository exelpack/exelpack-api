<style>
  .bordered{
    border : 1px solid #000000;
  }
</style>

<table>
  <tr>
    <td>SALES REPORT - {{ $m }}</td>
  </tr>
</table>
<br/>

<table border="1" cellspacing="0" width="100%">
  <thead>
    <tr>
      <th class="bordered" >COMPANY NAME</th>
      <th class="bordered">DELIVERY DATE</th>
      <th class="bordered">INVOICE NO.</th>
      <th class="bordered">DR NO.</th> 
      <th class="bordered">PO NO.</th>
      <th class="bordered">PART NO.</th>
      <th class="bordered">Sum of USD Amount</th>
      <th class="bordered">Sum of PHP Amount</th>
      <th class="bordered">Sum of Total Amount</th>
    </tr>
  </thead>
  <tbody>
    @if(count($data) > 0)

      @foreach($data as $row)
        <tr style="text-align:center">
          <td class="bordered">{{ $row['company'] }}</td>
          <td class="bordered">{{ $row['delivery_date'] }}</td>
          <td class="bordered">{{ $row['invoice'] }}</td>
          <td class="bordered">{{ $row['dr_num'] }}</td>
          <td class="bordered">{{ $row['po_num'] }}</td>
          <td class="bordered">{{ $row['part_num'] }}</td>
          <td class="bordered" style="text-align:right">{{ $row['usd'] }}</td>
          <td class="bordered" style="text-align:right">{{ $row['php'] }}</td>
          <td class="bordered" style="text-align:right">{{ $row['totalamount'] }}</td>
        </tr>
      @endforeach

    @else
      <tr>
        <td  class="bordered" colspan="9" style="text-align:center">NO RECORD</td>
      </tr>
    @endif
  </tbody>
</table>
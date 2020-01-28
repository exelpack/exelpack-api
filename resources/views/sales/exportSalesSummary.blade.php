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
          <td>{{ $row->sales->customer->c_customername }}</td>
          <td>{{ $row->s_deliverydate }}</td>
          <td>{{ $row->sales->s_invoicenum }}</td>
          <td>{{ $row->sitem_drnum }}</td>
          <td>{{ $row['sitem_ponum'] }}</td>
          <td>{{ $row['sitem_partnum'] }}</td>
          <td class="a-right">{{ number_format($usd,2) }}</td>
          <td class="a-right">{{ number_format($php,2) }}</td>
          <td class="a-right">{{ number_format($total,2) }}</td>
        </tr>
      @endforeach

    @else
      <tr>
        <td  class="bordered" colspan="9" style="text-align:center">NO RECORD</td>
      </tr>
    @endif
  </tbody>
</table>
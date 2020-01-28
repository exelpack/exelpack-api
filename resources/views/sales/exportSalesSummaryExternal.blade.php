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
      <th class="bordered">DELIVERY DATE</th>
      <th class="bordered">COMPANY NAME</th>
      <th class="bordered">SI NO.</th>
      <th class="bordered">DR NO.</th> 
      <th class="bordered">PO NO.</th>
      <th class="bordered">PART NO.</th>
      <th class="bordered">QTY</th>
      <th class="bordered">UNIT PRICE</th>
      <th class="bordered">Sum of USD Amount</th>
      <th class="bordered">Sum of PHP Amount</th>
      <th class="bordered">Sum of Total Amount in PHP</th>
    </tr>
  </thead>
  <tbody>
    @if(count($sales) > 0)

      @foreach($sales as $row)
      @php
        $usd = 0;
        $php = 0;
        $total = 0;

        if($row->sales->s_currency === 'USD'){
          $usd = $row->sitem_totalamount;
          $total = $row->sitem_totalamount * $conversion;
        }else{
          $php = $row->sitem_totalamount;
          $total = $row->sitem_totalamount;
        }

      @endphp
        <tr style="text-align:center">
          <td>{{ $row->sales->s_deliverydate }} hehehe</td>
          <td>{{ $row->sales->customer->c_customername }}</td>
          <td>{{ $row->sales->s_invoicenum }}</td>
          <td>{{ $row->sitem_drnum }}</td>
          <td>{{ $row['sitem_ponum'] }}</td>
          <td>{{ $row['sitem_quantity'] }}</td>
          <td>{{ $row['sitem_unitprice'] }}</td>
          <td  class="bordered" style="text-align:right">{{ number_format($usd,2) }}</td>
          <td  class="bordered" style="text-align:right">{{ number_format($php,2) }}</td>
          <td  class="bordered" style="text-align:right">{{ number_format($total,2) }}</td>
        </tr>
      @endforeach

    @else
      <tr>
        <td class="bordered" colspan="9" style="text-align:center">NO RECORD</td>
      </tr>
    @endif
  </tbody>
</table>
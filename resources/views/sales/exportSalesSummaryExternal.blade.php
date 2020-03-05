<style>
  .bordered{
    border : 1px solid #000000;
  }
</style>

<table>
  <tr>
    <td>BIR REPORT - {{ $m }}</td>
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
      <th class="bordered">BIR Amount</th>
    </tr>
  </thead>
  <tbody>
    @if(count($sales) > 0)

      @foreach($sales as $sale)

        @if( ($sale->items()->count() > 0) && ($sale->s_isRevised != 1) && (!$sale->deleted_at) )

          @foreach($sale->items as $row)
            @php
              $usd = 0;
              $php = 0;
              $total = 0;

              if($sale->s_currency === 'USD'){
                $usd = $row->sitem_totalamount;
                $total = $row->sitem_totalamount * $conversion;
              }else{
                $php = $row->sitem_totalamount;
                $total = $row->sitem_totalamount;
              }

            @endphp
            <tr style="text-align:center">
              <td>{{ $sale->s_deliverydate }} </td>
              <td>{{ $sale->customer->c_customername }}</td>
              <td>{{ $sale->s_invoicenum }}</td>
              <td>{{ $row->sitem_drnum }}</td>
              <td>{{ $row['sitem_ponum'] }}</td>
              <td>{{ $row['sitem_partnum'] }}</td>
              <td>{{ $row['sitem_quantity'] }}</td>
              <td>{{ $row['sitem_unitprice'] }}</td>
              <td  class="bordered" style="text-align:right">{{ number_format($usd,2) }}</td>
              <td  class="bordered" style="text-align:right">{{ number_format($php,2) }}</td>
              <td  class="bordered" style="text-align:right">{{ number_format($total,2) }}</td>
            </tr>
          @endforeach
        @continue
      @endif

      @php
        $status = 'CANCELLED';

        if($sale->s_isRevised)
          $status = 'REVISED';

      @endphp
  
      <tr style="text-align:center">
        <td></td>
        <td>{{ $status }}</td>
        <td>{{ $sale->s_invoicenum }}</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td  class="bordered" style="text-align:right"></td>
        <td  class="bordered" style="text-align:right"></td>
        <td  class="bordered" style="text-align:right"></td>
      </tr>

      @endforeach

    @else
      <tr>
        <td class="bordered" colspan="9" style="text-align:center">NO RECORD</td>
      </tr>
    @endif
  </tbody>
</table>
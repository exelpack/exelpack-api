<style>
  .thead {
    background : skyblue;
    text-align:center;
  }

  .a-right{
    text-align: right;
  }
</style>

<table border="1" cellspacing="0" width="100%" style="font-size :8px">
  <thead>
    <tr>
      <th class="thead">STATUS</th>
      <th class="thead">DELIVERY DATE</th>
      <th class="thead">COMPANY NAME</th>
      <th class="thead">SI NO.</th>
      <th class="thead">DR NO.</th> 
      <th class="thead">PO NO.</th>
      <th class="thead">PART NO.</th>
      <th class="thead">QTY</th>
      <th class="thead">UNIT PRICE</th>
      <th class="thead">Sum of USD Amount</th>
      <th class="thead">Sum of PHP Amount</th>
      <th class="thead">Sum of Total Amount</th>
      <th class="thead">Paymen terms</th>
      <th class="thead">With Holding Tax</th>
      <th class="thead">OR No.</th>
      <th class="thead">Date Collected</th>
      <th class="thead">Amount Collected</th>
    </tr>
  </thead>
  <tbody>
    @if(count($sales) > 0)

      @foreach($sales as $sale)
        @php
          $status = 'Not Collected';

          if($sale->s_ornumber && $sale->s_datecollected)
            $status = 'Collected';

          if($sale->deleted_at)
            $status = 'Cancelled';

          if($sale->s_isRevised)
            $status = 'Revised';
        @endphp

        @if($sale->items()->count() > 0)

          @foreach($sale->items as $row)
        
          @php
             
            $usd = 0;
            $php = 0;
            $total = 0;
            $amount_collected = 0;
    
            if($sale->s_currency === 'USD'){
              $usd = $row->sitem_totalamount;
              $total = $row->sitem_totalamount * $conversion;
            }else{
              $php = $row->sitem_totalamount;
              $total = $row->sitem_totalamount;
            }


            if($sale->s_withholding != null)
            {
              $amount_collected  = $total * ((100 - $sale->s_withholding) / 100);
            }

          @endphp
          <tr style="text-align:center">
            <td>{{ $status }}</td>
            <td>{{ $sale->s_deliverydate }} </td>
            <td>{{ $sale->customer->c_customername }}</td>
            <td>{{ $sale->s_invoicenum }}</td>
            <td>{{ $row->sitem_drnum }}</td>
            <td>{{ $row['sitem_ponum'] }}</td>
            <td>{{ $row['sitem_partnum'] }}</td>
            <td>{{ $row['sitem_quantity'] }}</td>
            <td>{{ $row['sitem_unitprice'] }}</td>
            <td class="a-right">{{ number_format($usd,4) }}</td>
            <td class="a-right">{{ number_format($php,4) }}</td>
            <td class="a-right">{{ number_format($total,4) }}</td>
            <td>{{ $sale->customer->c_paymentterms }}</td>
            <td>{{ $sale->s_withholding }}</td>
            <td>{{ $sale->s_ornumber }}</td>
            <td>{{ $sale->s_datecollected }}</td>
            <td>{{ $amount_collected }}</td>
          </tr>
        @endforeach
        @continue
      @endif

      <tr style="text-align:center">
        <td>{{ $status }}</td>
        <td></td>
        <td></td>
        <td>{{ $sale->s_invoicenum }}</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
      </tr>

      @endforeach

    @else
      <tr>
        <td colspan="9" style="text-align:center">NO RECORD</td>
      </tr>
    @endif
  </tbody>
</table>
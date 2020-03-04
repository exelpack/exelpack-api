<style>
.small {
  font-size: 10px;
  text-align: center;
}
* {
  font-family: Arial, Helvetica, sans-serif;
}

.subDetails {
  width: 100%; 
  font-size: 12px;
}

.itemTable {
  width: 100%;
  text-align: center;
  font-size: 11px;
}

.itemTable tbody tr td{
  font-size: 9px;
}

.sigTable {
  font-size: 12px;
}

@page {
  margin: 30px 30px 10px 30px;
}

</style>

<table style="width: 100%;">
  <tr>
    <td style="width: 30%">
      <img src="{{ asset('storage/img/logo.png') }}" width="200px" />
    </td>
    <td align="center" style="width: 40%">
      <i class="small">EXELPACK CORPORATION</i><br>
      <b>PURCHASE REQUISITION</b>
    </td>
    <td style="width: 30%; ">
      <table width="100%" class="small" border="1" cellspacing="0">
        <tr>
          <td colspan="2" style="background-color:#dddddd;" class="small">
            STATUS
          </td>
        </tr>
        <tr>
          <td>
            DATE COMPLETED
          </td>
          <td>
            DATE CANCELLED
          </td>
        </tr>
        <tr>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
        </tr>
      </table>
    </td>
  </tr>
</table>

<table class="subDetails">
  <tr>
    <td >Section: WHSE</td>
    <td >Date: {{ $details['date'] }}</td>
    <td >PO. #: {{ $details['po'] }}</td>
    <td >JO. #: {{ $details['jo'] }}</td>
  </tr>
</table>
<table border="1" class="itemTable" cellpadding="4" cellspacing="0">
  <thead>
    <tr  style="background-color:#dddddd;">
      <th width="5%">Item</th>
      <th width="30%">Description</th>
      <th width="5%">Qty</th>
      <th width="5%">Unit</th>
      <th width="10%">Unit Price</th>
      <th width="15%">Amount</th>
      <th width="15%">Supplier</th>
      <th width="10%">Remarks</th>
    </tr>
  </thead>
  <tbody>
    @foreach($items as $key => $item)
      <tr>
        <td>{{ $key + 1 }}</td>
        <td style="text-align: left">{{ $item['materialSpecification'] }}</td>
        <td>{{ $item['quantity'] }}</td>
        <td>{{ $item['unit'] }}</td>
        <td>{{ $item['unitPrice'] }}</td>
        <td>{{ $details['currency'] == 'USD' ? '$' : 'PHP' }} {{ number_format($item['amount'], 2) }}</td>
        <td>{{ $item['supplier'] }}</td>
        <td>{{ $item['code'] }}</td>
      </tr> 
    @endforeach

    @if(count($items) > 0)
      <tr>
        <td colspan="5"></td>
        <td>{{ $details['currency'] }} 
          @php echo number_format(array_sum(array_column($items, 'amount')), 2) @endphp</td>
        <td colspan="2"></td>
      </tr>
    @endif
  </tbody>
</table>

<br/>
<table border="1" style="width: 100%;" class="sigTable">
  <tr>
    <td>Requested by:
      <br/>
      <img 
        src="{{ asset('storage/img/defaultsign.jpg') }}"
        style="width: 100px;"
      />
    </td>
    <td>Requested by:
      <br/>
      <img 
        src="{{ asset('storage/img/defaultsign.jpg') }}"
        style="width: 100px;"
      />
    </td>
    <td>Requested by:
      <br/>
      <img 
        src="{{ asset('storage/img/defaultsign.jpg') }}"
        style="width: 100px;"
      />
    </td>
    <td>Requested by:
      <br/>
      <img 
        src="{{ asset('storage/img/defaultsign.jpg') }}"
        style="width: 100px;"
      />
    </td>
  </tr>
</table>
<!-- <table border="1" cellspacing="0" cellpadding="0" width="101%">
<tr >
<td  width="25%" > Requested by:<br><font align="center"><img align="center"  src="../signature/cocoi.png" width="30px"/></font><br>
  <font align="center"  style=" text-decoration: overline; position:relative;">Signature Over Printed Name</font></td>

  <td  width="25%"> Checked by:<br><font align="center"><img align="center" src="../signature/{$src}.png" style="display:$switch1;" width="{$w}"/><img align="center" src="../signature/block.png" width="50px" style="display:$switch2;"/></font><br>
  <font align="center"  style=" text-decoration: overline;">Purchasing Officer</font></td>

  <td  width="25%">  Recommending Approval by:<br><font align="center"><img align="center" src="../signature/om.png" style="display:$switch3;" width="95px"/><img align="center" src="../signature/block.png" width="50px" style="display:$switch4;"/></font><br>
  <font align="center" style=" text-decoration: overline;">Department Head</font></td>

  <td  width="25%"> Approved by:<br><font align="center"><img align="center" src="../signature/gm.png" style="display:$switch5;" width="95px"/><img align="center" src="../signature/block.png" width="50px" style="display:$switch6;"/></font><br>

  <font align="center" style=" text-decoration: overline;">MR. JA CABUNTOCAN</font></td>
</tr>
  </table> -->
<style>
.container{
  border: 1px solid black;
  padding: 10px 5px;
}

.sm-title {
  text-transform: uppercase;
  text-align: center;
  font-style: italic;
  font-size: 10px;
  font-weight: 600;
  margin: 0;
}

.lg-title {
  text-transform: uppercase;
  text-align: center;
  font-size: 14px;
  font-weight: 700;
  margin: 0;
}

.ctrl, .date {
  display: inline-block;
  margin: 0;
  font-size: 12px;
}

.date {
  float: right;
}

.rrtable-info{
  font-size: 10px;
  width: 100%;
  border: 1px solid black;
}

.table-row {
  width: 100%;
  box-sizing: border-box;
}

.table-row p {
  display: inline-block;
  border: 1px solid black;
  margin: 0px;
  padding: 0px;
}

.box {
  height: 6px;
  width: 10px;
  border: 1px solid black;
  display: inline-block;
}

.shade {
  background-color: black;
}

.signee {
  text-align: center;
  margin: 0;
}

@page {
  margin: 20px 10px;
}
</style>

<div class="container">
  <p class="sm-title">Exelpack Corporation</p>
  <p class="lg-title">RECEIVING INSPECTION REPORT</p>

  <div>
    <p class="ctrl">CONTROL NO. : {{ $rrDetails->rrNum }}</p>
    <p class="date">DATE : {{ date('Y-m-d') }}</p>
  </div>

  <table class="rrtable-info" border="1" cellspacing="0" cellpadding="1">
    <tr>
      <td style="width: 50%">Item Description: {{ $rrDetails->itemDescription }}</td>
      <td style="width: 50%" colspan="2">
        <table style="width: 100%;" cellspacing="0" cellpadding="2">
          <tr>
            <td style="width: 33%; border-right:1px solid black;">DR Quantity: {{ $rrDetails->drQty }}</td>
            <td style="width: 33%; border-right:1px solid black;">Inspected Quantity: {{ $rrDetails->inspectedQty }}</td>
            <td style="width: 33%">Received Quantity: {{ $rrDetails->receivedQty }}</td>
          </tr>
        </table>
      </td>
    </tr>
    <tr>
      <td style="width: 50%">Supplier: {{ $rrDetails->supplier }}</td>
      <td style="width: 50%" colspan="2">
        <table style="width: 100%;" cellspacing="0" cellpadding="2">
          <tr>
            <td style="width: 33%; border-right:1px solid black;">&nbsp;</td>
            <td style="width: 33%; border-right:1px solid black;">Arrival Date: {{ $rrDetails->arrivalDate }}</td>
            <td style="width: 33%;">Arrival Time: </td>
          </tr>
        </table>
      </td>
    </tr>
    <tr>
      <td style="width: 50%">PO No. / JO No.: {{ $rrDetails->poNum }} / {{ $rrDetails->jo }}</td>
      <td style="width: 25%">DR No.: {{ $rrDetails->drNum }}</td>
      <td style="width: 25%">Invoice No.: {{ $rrDetails->invoice }}</td>
    </tr>
    <tr>
      <td style="width: 50%"><div class="box"></div> Special item, issue to end user</td>
      <td colspan="2"><div class="box shade"></div> Stock item, endorse to Warehouse / Purchasing</td>
    </tr>
  </table>
  <p
    class="sm-title"
    style="font-style: normal; font-size:12px; font-weight: 200px; letter-spacing: 1;">INSPECTION STATUS</p>

  <table class="rrtable-info" border="1" cellspacing="0">
    <thead>
      <tr>
        <th style="width: 35%">Defect</th>
        <th style="width: 15%">Quantity</th>
        <th style="width: 35%">Defect</th>
        <th style="width: 15%">Quantity</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>{{ strtoupper($rrDetails->rejectRemarks) }}</td>
        <td>{{ $rrDetails->rejectQty }}</td>
        <td>&nbsp;</td>
        <td></td>
      </tr>
    </tbody>
  </table>
  <p style="text-transform: lowercase; font-weight: 400;" class="sm-title">-use seperate sheet if necessary-</p>
  <table style="width: 100%; font-size: 12px;" border="1" cellpadding="5" cellspacing="0">
    <tr>
      <td>Inspected by: 
        <p class="signee">
          ____________________________________________________
          <br/>IQC Inspector
        </p>
      </td>
      <td> Checked by:
        <p class="signee">
          ____________________________________________________
          <br/>Purchasing / Mfg. In-charge
        </p>
      </td>
    </tr>
    <tr>
      <td> Received by: 
        <p class="signee">
          ____________________________________________________
          <br/>Warehouseman
        </p>
      </td>
      <td> Documents Received by: 
        <p class="signee">
          ____________________________________________________
          <br/>Acctng. &amp; Finance Section
        </p>
      </td>
    </tr>
</table>
<p style="font-weight: 700; font-size: 10px; margin: 0;">QFO01-03A</p>
</div>  
<br/>
<br/>
<div class="container">
  <p class="sm-title">Exelpack Corporation</p>
  <p class="lg-title">RECEIVING INSPECTION REPORT</p>

  <div>
    <p class="ctrl">CONTROL NO. : {{ $rrDetails->rrNum }}</p>
    <p class="date">DATE : {{ date('Y-m-d') }}</p>
  </div>

  <table class="rrtable-info" border="1" cellspacing="0" cellpadding="1">
    <tr>
      <td style="width: 50%">Item Description: {{ $rrDetails->itemDescription }}</td>
      <td style="width: 50%" colspan="2">
        <table style="width: 100%;" cellspacing="0" cellpadding="2">
          <tr>
            <td style="width: 33%; border-right:1px solid black;">DR Quantity: {{ $rrDetails->drQty }}</td>
            <td style="width: 33%; border-right:1px solid black;">Inspected Quantity: {{ $rrDetails->inspectedQty }}</td>
            <td style="width: 33%">Received Quantity: {{ $rrDetails->receivedQty }}</td>
          </tr>
        </table>
      </td>
    </tr>
    <tr>
      <td style="width: 50%">Supplier: {{ $rrDetails->supplier }}</td>
      <td style="width: 50%" colspan="2">
        <table style="width: 100%;" cellspacing="0" cellpadding="2">
          <tr>
            <td style="width: 33%; border-right:1px solid black;">&nbsp;</td>
            <td style="width: 33%; border-right:1px solid black;">Arrival Date: {{ $rrDetails->arrivalDate }}</td>
            <td style="width: 33%;">Arrival Time: </td>
          </tr>
        </table>
      </td>
    </tr>
    <tr>
      <td style="width: 50%">PO No. / JO No.: {{ $rrDetails->poNum }} / {{ $rrDetails->jo }}</td>
      <td style="width: 25%">DR No.: {{ $rrDetails->drNum }}</td>
      <td style="width: 25%">Invoice No.: {{ $rrDetails->invoice }}</td>
    </tr>
    <tr>
      <td style="width: 50%"><div class="box"></div> Special item, issue to end user</td>
      <td colspan="2"><div class="box shade"></div> Stock item, endorse to Warehouse / Purchasing</td>
    </tr>
  </table>
  <p
    class="sm-title"
    style="font-style: normal; font-size:12px; font-weight: 200px; letter-spacing: 1;">INSPECTION STATUS</p>

  <table class="rrtable-info" border="1" cellspacing="0">
    <thead>
      <tr>
        <th style="width: 35%">Defect</th>
        <th style="width: 15%">Quantity</th>
        <th style="width: 35%">Defect</th>
        <th style="width: 15%">Quantity</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>{{ $rrDetails->rejectRemarks }}</td>
        <td>{{ $rrDetails->rejectQty }}</td>
        <td>&nbsp;</td>
        <td></td>
      </tr>
    </tbody>
  </table>
  <p style="text-transform: lowercase; font-weight: 400;" class="sm-title">-use seperate sheet if necessary-</p>
  <table style="width: 100%; font-size: 12px;" border="1" cellpadding="5" cellspacing="0">
    <tr>
      <td>Inspected by: 
        <p class="signee">
          ____________________________________________________
          <br/>IQC Inspector
        </p>
      </td>
      <td> Checked by:
        <p class="signee">
          ____________________________________________________
          <br/>Purchasing / Mfg. In-charge
        </p>
      </td>
    </tr>
    <tr>
      <td> Received by: 
        <p class="signee">
          ____________________________________________________
          <br/>Warehouseman
        </p>
      </td>
      <td> Documents Received by: 
        <p class="signee">
          ____________________________________________________
          <br/>Acctng. &amp; Finance Section
        </p>
      </td>
    </tr>
  </table>
<p style="font-weight: 700; font-size: 10px; margin: 0;">QFO01-03A</p>
</div>  
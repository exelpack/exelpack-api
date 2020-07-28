<style>

.title{
  font-size: 26px;
  text-transform: uppercase;
  margin: 0;
  text-align: center;
}

* {
  font-size: 11px;
}

.row{
  width: 100%;
  border-collapse: collapse;
}

.row tr td, .td {
  border: 1px solid black;
  padding: 2px;
  border-bottom: 0;
}

.row:last-child tr td {
  border: 1px solid black;
  padding: 2px;
}

.description {
  text-decoration: underline;;
  margin: 0;
  margin-bottom: 5px;
}
.signee {
  text-align: center;
  margin: 0;
}

@page {
  margin: 20px 30px;
}
</style>


<p class="title">return to vendor form</p>
<table style="width: 100%; margin:0;" cellpadding="0" cellspacing="0">
  <tr>
    <td>
      <table class="row" cellpadding="0" cellspacing="0">
        <tr>
          <td style="width: 20%;">RTV No. : {{ str_replace("RR","RTV",$rrDetails->rrNum) }}</td>
          <td style="width: 20%;">Date : {{ $rrDetails->arrivalDate }}</td>
          <td style="width: 60%;">Supplier : {{ $rrDetails->supplier }}</td>
        </tr>
      </table>
    </td>
  </tr>
  <tr>
    <td>
      <table class="row" cellpadding="0" cellspacing="0">
        <tr>
          <td style="width: 20%;">PO No.: {{ $rrDetails->poNum }}</td>
          <td style="width: 25%;">SI No.: {{ $rrDetails->invoice }}</td>
          <td style="width: 25%;">DR No. : {{ $rrDetails->drNum }}</td>
          <td style="width: 15%;">Qty. : </td>
          <td style="width: 15%;">{{ $rrDetails->drQty }}</td>
        </tr>
      </table>
    </td>
  </tr>
  <tr>
    <td class="td">
      <p class="description">ITEM DESCRIPTION:</p>
      <b>{{ $rrDetails->itemDescription }}</b>
    </td>
  </tr>
  <tr>
    <td class="td">
      <p class="description">NON-CONFORMING CONDITION FOUND:</p>
      {{ strtoupper($rrDetails->rejectRemarks) }} - {{ $rrDetails->rejectQty }}
    </td>
  </tr>
  <tr>
    <td>
      <table class="row" cellpadding="5" cellspacing="0">
        <tr>
          <td>Inspected by: 
            <p class="signee">
              ____________________________________________________
              <br/>Signature Over Printed Name(EXELPACK)
            </p>
          </td>
          <td> Checked by:
            <p class="signee">
              ____________________________________________________
              <br/>Signature Over Printed Name(SUPPLIER
            </p>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
<p style="font-weight: 700; font-size: 10px; margin: 0;">QFO06-02A</p>
<br/>
<br/>

<p class="title">return to vendor form</p>
<table style="width: 100%; margin:0;" cellpadding="0" cellspacing="0">
  <tr>
    <td>
      <table class="row" cellpadding="0" cellspacing="0">
        <tr>
          <td style="width: 20%;">RTV No. : {{ $rrDetails->rrNum }}</td>
          <td style="width: 20%;">Date : {{ $rrDetails->arrivalDate }}</td>
          <td style="width: 60%;">Supplier : {{ $rrDetails->supplier }}</td>
        </tr>
      </table>
    </td>
  </tr>
  <tr>
    <td>
      <table class="row" cellpadding="0" cellspacing="0">
        <tr>
          <td style="width: 20%;">PO No.: {{ $rrDetails->poNum }}</td>
          <td style="width: 25%;">SI No.: {{ $rrDetails->invoice }}</td>
          <td style="width: 25%;">DR No. : {{ $rrDetails->drNum }}</td>
          <td style="width: 15%;">Qty. : </td>
          <td style="width: 15%;">{{ $rrDetails->drQty }}</td>
        </tr>
      </table>
    </td>
  </tr>
  <tr>
    <td class="td">
      <p class="description">ITEM DESCRIPTION:</p>
      <b>{{ $rrDetails->itemDescription }}</b>
    </td>
  </tr>
  <tr>
    <td class="td">
      <p class="description">NON-CONFORMING CONDITION FOUND:</p>
      {{ $rrDetails->rejectRemarks }} - {{ $rrDetails->rejectQty }}
    </td>
  </tr>
  <tr>
    <td>
      <table class="row" cellpadding="5" cellspacing="0">
        <tr>
          <td>Inspected by: 
            <p class="signee">
              ____________________________________________________
              <br/>Signature Over Printed Name(EXELPACK)
            </p>
          </td>
          <td> Checked by:
            <p class="signee">
              ____________________________________________________
              <br/>Signature Over Printed Name(SUPPLIER
            </p>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
<p style="font-weight: 700; font-size: 10px; margin: 0;">QFO06-02A</p>
<!-- <div class="row" style="border: 1px solid black;">
  <div style="width: 20%; border-right: 1px solid black;">hehe</div>
  <div>hehe</div>
  <div>hehe</div>
</div -->

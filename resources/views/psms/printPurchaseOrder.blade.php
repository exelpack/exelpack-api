<style>
.small {
  font-size: 10px;
  text-align: center;
}

.colored {
  background-color: #dddddd;
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

.sigTable tr td{
  max-width: 25%;
  width: 25%;
}

.signature {
  width: 70px;
  display: block;
  margin: 0 auto;
}

.signature img{
  width: 100%;
  height: 40px;
}

.signature-text{
  text-align: center;
  margin: 0;
  text-decoration: overline;
}

@page {
  margin: 30px 30px 10px 30px;
}

</style>

@php
  $total = $poItems->sum(function($item) {
    return $item->unitprice * $item->quantity;
  });

  $url = asset('api/storage/signature').'?filepath=';
  $defaultImg = asset('storage/img/defaultsign.jpg');
  
@endphp

<table style="width: 100%;">
  <tr>
    <td style="width: 30%">
      <img src="{{ asset('storage/img/logo.png') }}" width="200px" />
    </td>
    <td align="center" style="width: 30%">
    </td>
    <td style="width: 40%; font-size: 8px; text-align: left;">
      <b>Blk 2 Lot 2 Filinvest Techno. Park, Ciudad de Calamba Laguna</b><br/>
      <span>Tel. Nos:(049) 502-0295/(02) 584-4424 Telefax No.:(02)-584-4424</span><br />
      <span>Email add: purchase.exelpack@gmail.com</span><br />
    </td>
  </tr>
</table>

<table style="width: 100%; font-size: 11px;">
  <tbody>
    <tr>
      <td style="width: 55%;">
        <table style="width: 100%;" cellspacing="0" cellpadding="2">
          <tr>
            <td style="width: 23%;">SUPPLIER:</td>
            <td style="font-size: 9px;">{{ strtoupper($poDetails->supplierName) }}</td>
          </tr>
          <tr>
            <td>ADDRESS:</td>
            <td style="font-size: 9px;">{{ $poDetails->address ?? "No address record" }}</td>
          </tr>
          <tr>
            <td>TIN NO.:</td>
            <td style="font-size: 10px;">{{ $poDetails->tin }}</td>
          </tr>
          <tr>
            <td>ATTENTION TO:</td>
            <td style="font-size: 10px;">{{ $poDetails->attention ?? "No record" }}</td>
          </tr>
        </table>
      </td>
      <td style="width: 45%;">
        <table style="width: 101%;" border="1" cellspacing="0" cellpadding="3">
          <tr>
            <td class="colored" style="width: 20%;">P.O NO.</td>
            <td>{{ $poDetails->poNumber }}</td>
          </tr>
          <tr>
            <td class="colored">P.R. NO.</td>
            <td style="font-size: 8px;">{{ $poDetails->prNumber }}</td>
          </tr>
          <tr>
            <td class="colored">Date</td>
            <td>{{ $poDetails->date }}</td>
          </tr>
          <tr>
            <td class="colored">Page</td>
            <td></td>
          </tr>
        </table>
      </td>
    </tr>
  </tbody>
</table>

<table style="width: 100%; font-size: 11px; page-break-inside: avoid;" border="1" cellpadding="3" cellspacing="0">
  <tbody>
    <tr>
      <td class="colored" colspan="2">PAYMENT TERMS</td>
      <td colspan="6">{{ $poDetails->paymentTerms }}</td>
    </tr>
    <tr>
      <td class="colored" colspan="2">OTHERS</td>
      <td colspan="6"></td>
    </tr>
    <tr class="colored" style="text-align: center">
      <td style="width: 8%">ITEM NO.</td>
      <td style="width: 12%">CODE NO.</td>
      <td style="width: 27%">DESCRIPTION</td>
      <td style="width: 8%">QTY</td>
      <td style="width: 10%">UNIT</td>
      <td style="width: 10%">UNIT PRICE</td>
      <td style="width: 10%">AMOUNT</td>
      <td style="width: 15%">DELIVERY DATE</td>
    </tr>
    
    @foreach($poItems as $key => $item)
      <tr>
        <td style="text-align: center;">{{ intval($key + 1) }}</td>
        <td style="text-align: center;">{{ $item->code }}</td>
        <td>{{ $item->materialSpecification }}</td>
        <td style="text-align: center;">{{ $item->quantity }}</td>
        <td style="text-align: center;">{{ $item->unit }}</td>
        <td style="text-align: right;">{{ number_format($item->unitprice,2) }}</td>
        <td style="text-align: right;">{{ number_format($item->unitprice * $item->quantity,2) }}</td>
        <td style="text-align: center;">{{ $item->deliveryDate }}</td>
      </tr>

      @if($loop->last)
        <tr>
          <td style="text-align: center;" class="colored" colspan="2">CURRENCY</td>
          <td style="text-align: center;" colspan="2">{{ strtoupper($poDetails->currency) }}</td>
          <td class="colored" style="text-align: center;" colspan="2">TOTAL AMOUNT</td>
          <td style="text-align: right;">{{ number_format($total, 2) }}</td>
          <td></td>
        </tr>
      @endif
    @endforeach
  </tbody>
</table>

<div style="page-break-inside: avoid;">
  <p 
    class="colored"
    style="
    margin: 10px 0 0 0;
    width:30%;
    text-align: center;
    border: 1px solid black;
    border-bottom: 0;
    font-size: 12px;"
  >CONTIONS &amp; INSTRUCTIONS</p>

  <pre
    style="
    margin: 0;
    width:100%;
    padding: 5px;
    text-align: left;
    border: 1px solid black;
    font-size: 11px;"
  >
  1. Exelpack Corporation reserves the right to reject any items that will not comply with the specification.
  2. Please indicate exelpack P.O. &amp; P.R. number in your DR &amp; Sales Invoice.
  3. Please ensure to include original sales invoice during delivery to prevent payment delays.
  4. For MRS corrugated board Exelpack will not accept any damage such as corrugation in outer liner, wash board, spot, short liner.
  </pre>
</div>

<br/>
<table border="1" style="width: 100%;" cellspacing="0" class="sigTable">
  <tr>
    <td style="width: 20%;">Prepared by:
      <br/>
      <div class="signature">
        @if($preparedBySig)
          <img 
            src="{{ $url.$prepareBySigFile.'&token='.$token }}"
            alt="Cannot load signature"
          />
        @else
          <img 
            src="{{ $defaultImg }}"
            alt="Cannot load signature"
          />
        @endif
      </div>
      <p class="signature-text">
        {{ $preparedByName ?? "Purchasing Officer" }}
      </p>
    </td>
    <td style="width: 20%;">Checked by:
      <br/>
      <div style="padding: 12.8px 0;">&nbsp;</div>
      <p class="signature-text">
        {{ $checkByName ?? "Operations Manager" }}
      </p>
    </td>
    <td style="width: 20%;">Approved by:
      <br/>
      <div style="padding: 12.8px 0;">&nbsp;</div>
      <p class="signature-text">
        {{ $approvedByName ?? "General Manager" }}
      </p>
    </td>
    <td style="width: 40%;">Supplier's Confirmation:
      <br/>
      <div style="padding: 12.8px 0;">&nbsp;</div>
      <p class="signature-text">
        Signature Over Printed Name
      </p>
    </td>
  </tr>
</table>

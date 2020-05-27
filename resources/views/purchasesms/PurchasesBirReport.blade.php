<table>
  <tr>
    <td>{{ $company }}</td>
  </tr>
  <tr>
    <td>PAYABLES FOR THE MONTH  OF {{ $fixedDate->format('F Y') }}</td>
  </tr>
</table>
<table>
  <thead>
    <tr>
      <th>SUPPLIER'S NAME</th>
      <th>TIN</th>
      <th>ADDRESS</th>
      <th>DATE RECEIVED</th>
      <th>CODE</th>
      <th>PO</th>
      <th>PR</th>
      <th>SI</th>
      <th>DR</th>
      <th>PARTICULAR</th>
      <th>PURCHASEDATE</th>
      <th>DUE DATE</th>
      <th>Sum of AMOUNT PHP</th>
      <th>Sum OF AMOUNT USD</th>
      <th>Sum OF Zero-Rated</th>
      <th>Sum of IMPORTATION</th>
    </tr> 
  </thead>
  <tbody>
    @foreach($purchasesItems as $row)
      <tr>
        <td>{{ $row['suppliers_name'] }}</td>
        <td>{{ $row['tin'] }}</td>
        <td>{{ $row['address'] }}</td>
        <td>{{ $row['date_received'] }}</td>
        <td>{{ $row['code'] }}</td>
        <td>{{ $row['po'] }}</td>
        <td>{{ $row['pr'] }}</td>
        <td>{{ $row['si'] }}</td>
        <td>{{ $row['dr'] }}</td>
        <td>{{ $row['particular'] }}</td>
        <td>{{ $row['purchasedate'] }}</td>
        <td>{{ $row['duedate'] }}</td>
        <td>{{ $row['amountphp'] !== 0 ? number_format($row['amountphp'],2) : '' }}</td>
        <td>{{ $row['amountusd'] !== 0 ? number_format($row['amountusd'],2) : '' }}</td>
        <td>{{ $row['zerorated'] !== 0 ? number_format($row['zerorated'],2) : '' }}</td>
        <td></td>
      </tr>
    @endforeach
  </tbody>
</table>
<br/>
<br/>
<h6>JOURNAL ENTRY</h6>
<table cellpadding="10" cellspacing="10">
  @foreach($accountsTotal as $key => $row)
    <tr>
      <th>{{ $key }}</th>
      <td style="text-align : right;">{{ number_format($row,2) }}</td>
      <td></td>
      <td></td>
    </tr>
  @endforeach
  <tr>
    <td></td>
    <td style="text-align : right;">{{ number_format($accountsTotalAll,2) }}</td>
    <td></td>
    <td></td>
  </tr> 
</table>
<table>
  <tr>
    <td>{{ $company }}</td>
  </tr>
  <tr>
    <td>PURCHASES FOR THE MONTH OF {{ $date->format('F Y') }}</td>
  </tr>
</table>
<table>
  <thead>
    <tr>
      <th>SUPPLIER'S NAME</th>
      <th >CODE</th>
      <th>PR</th>
      <th>PO</th>
      <th>SI</th>
      <th>DR</th>
      <th>PARTICULAR</th>
      <th>PURCHASEDATE</th>
      <th>DUE DATE</th>
      <th>TERMS</th>
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
        <td>{{ $row['code'] }}</td>
        <td>{{ $row['pr'] }}</td>
        <td>{{ $row['po'] }}</td>
        <td>{{ $row['si'] }}</td>
        <td>{{ $row['dr'] }}</td>
        <td>{{ $row['particular'] }}</td>
        <td>{{ $row['purchasedate'] }}</td>
        <td>{{ $row['duedate'] }}</td>
        <td>{{ $row['terms'] }}</td>
        <td>{{ $row['amountphp'] !== 0 ? number_format($row['amountphp'],2) : '' }}</td>
        <td >{{ $row['amountusd'] !== 0 ? number_format($row['amountusd'],2) : '' }}</td>
        <td>{{ $row['zerorated'] !== 0 ? number_format($row['zerorated'],2) : '' }}</td>
        <td></td>
      </tr>
    @endforeach
  </tbody>
</table>
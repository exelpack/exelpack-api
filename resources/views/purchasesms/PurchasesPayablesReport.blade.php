
<table>
  <tr>
    <td>{{ $company }}</td>
  </tr>
  <tr>
    <td>PAYABLES FOR THE MONTH  OF {{ $month->format('F Y') }}</td>
  </tr>
</table>
<table>
  <thead>
    <tr>
      <th>SUPPLIER'S NAME</th>
      <th>PO</th>
      <th>SI</th>
      <th>DR</th>
      <th>PARTICULAR</th>
      <th>PURCHASEDATE</th>
      <th>DUE DATE</th>
      <th>STATUS</th>
      <th>Sum of AMOUNT PHP</th>
      <th>Sum OF AMOUNT USD</th>
      <th>Sum of UNRELEASED CHECK</th>
      <th>REMARKS</th>
    </tr> 
  </thead>
  <tbody>
    @foreach($payables as $row)
    <tr>
      <td>{{ $row['suppliers_name'] }}</td>
      <td>{{ $row['po'] }}</td>
      <td>{{ $row['si'] }}</td>
      <td>{{ $row['dr'] }}</td>
      <td>{{ $row['particular'] }}</td>
      <td>{{ $row['purchasedate'] }}</td>
      <td>{{ $row['duedate'] }}</td>
      <td>{{ $row['status'] }}</td>
      <td>{{ $row['php'] !== 0 ? number_format($row['php'],2) : '' }}</td>
      <td>{{ $row['usd'] !== 0 ? number_format($row['usd'],2) : '' }}</td>
      <td>{{ $row['unreleased_amt'] !== 0 ? number_format($row['unreleased_amt'],2) : '' }}</td>
      <td>{{ $row['remarks'] }}</td>
    </tr>
    @endforeach
  </tbody>
</table>
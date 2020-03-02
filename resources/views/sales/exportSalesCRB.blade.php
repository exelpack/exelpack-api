<table>
  <tr>
    <td>CASH RECEIPT REPORT - {{ $m }} - {{ $year }}</td>
  </tr>
</table>
<br/>

<table border="1" cellspacing="0" width="100%">
  <thead>
    <tr>
      <th style="background-color: #7AC7DF; color: #ffffff">DATE COLLECTED</th>
      <th style="background-color: #7AC7DF; color: #ffffff">OR NO.</th>
      <th style="background-color: #7AC7DF; color: #ffffff">COMPANY NAME</th>
      <th style="background-color: #7AC7DF; color: #ffffff">CASH IN BANK RCBC</th> 
      <th style="background-color: #7AC7DF; color: #ffffff">CASH IN BANK SECURITY</th>
      <th style="background-color: #7AC7DF; color: #ffffff">INVOICE</th>
      <th style="background-color: #7AC7DF; color: #ffffff">CWT($)</th>
      <th style="background-color: #7AC7DF; color: #ffffff">CWH(PHP)</th>
      <th style="background-color: #7AC7DF; color: #ffffff">COLLECTION($)</th>
      <th style="background-color: #7AC7DF; color: #ffffff">COLLECTION(PHP)</th>
      <th style="background-color: #7AC7DF; color: #ffffff">ACCOUNT RECEIVABLE</th>
    </tr>
  </thead>
  <tbody>
    @if(count($data) > 0)

      @foreach($data as $row)
        <tr style="text-align:center">
          <td class="bordered">{{ $row['dateCollected'] }}</td>
          <td class="bordered">{{ $row['orNum'] }}</td>
          <td class="bordered">{{ $row['customerName'] }}</td>
          <td class="bordered"></td>
          <td class="bordered"></td>
          <td class="bordered">{{ $row['invoiceNum'] }}</td>
          <td style="text-align:right">{{ $row['cwtUsd'] }}</td>
          <td style="text-align:right">{{ $row['cwtPhp'] }}</td>
          <td class="bordered" style="text-align:right">{{ $row['collectedUsd'] }}</td>
          <td class="bordered" style="text-align:right">{{ $row['collectedPhp'] }}</td>
          <td class="bordered" style="text-align:right">{{ $row['receivable'] }}</td>
        </tr>
      @endforeach

    @else
      <tr>
        <td  class="bordered" colspan="11" style="text-align:center">NO RECORD</td>
      </tr>
    @endif
  </tbody>
</table>
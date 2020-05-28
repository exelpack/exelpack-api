
<table >
  <thead>
    <tr>
      <th style="text-align:center;" colspan="21">{{ $company }}
        <br/>ACCOUNTS PAYABLE SUMMARY FOR THE MONTH OF {{ $date->format('F Y') }}</th>
      </tr>
      <tr>
        <th style="border : 1px solid #2d3436; background:#F5F6FA; text-align:center;">SUPPLIER'S NAME</th>
        <th style="border : 1px solid #2d3436; background:#F5F6FA; text-align:center;" colspan="2">TOTAL ACCOUNTS PAYABLE</th>
        <th style="border : 1px solid #2d3436; background:#F5F6FA; text-align:center;">Payment Terms</th>
        <th style="border : 1px solid #2d3436; background:#F5F6FA; text-align:center;" colspan="2">0-29 DAYS</th>
        <th style="border : 1px solid #2d3436; background:#F5F6FA; text-align:center;" colspan="2">30-59 DAYS</th>
        <th style="border : 1px solid #2d3436; background:#F5F6FA; text-align:center;" colspan="2">60-89 DAYS</th>
        <th style="border : 1px solid #2d3436; background:#F5F6FA; text-align:center;" colspan="2">90-119 DAYS</th>
        <th style="border : 1px solid #2d3436; background:#F5F6FA; text-align:center;" colspan="2">Over 120 DAYS</th>
        <th style="border : 1px solid #2d3436; background:#F5F6FA; text-align:center;" colspan="2">Over 365 DAYS</th>
        <th style="border : 1px solid #2d3436; background:#F5F6FA; text-align:center;" colspan="2">ACCOUNTS PAYABLE</th>
        <th style="border : 1px solid #2d3436; background:#F5F6FA; text-align:center;">Unreleased Check</th>
        <th style="border : 1px solid #2d3436; background:#F5F6FA; text-align:center;">REMARKS</th>
        <th style="border : 1px solid #2d3436; background:#F5F6FA; text-align:center;">SOA AMOUNT</th>
      </tr> 
      <tr>
        <th style="border : 1px solid #2d3436; background:#F5F6FA;"></th>
        <th style="border : 1px solid #2d3436; background:#F5F6FA; text-align:center;">PHP</th>
        <th style="border : 1px solid #2d3436; background:#F5F6FA; text-align:center;">USD</th>
        <th style="border : 1px solid #2d3436; background:#F5F6FA; text-align:center;"></th>
        <th style="border : 1px solid #2d3436; background:#F5F6FA; text-align:center;">PHP</th>
        <th style="border : 1px solid #2d3436; background:#F5F6FA; text-align:center;">USD</th>
        <th style="border : 1px solid #2d3436; background:#F5F6FA; text-align:center;">PHP</th>
        <th style="border : 1px solid #2d3436; background:#F5F6FA; text-align:center;">USD</th>
        <th style="border : 1px solid #2d3436; background:#F5F6FA; text-align:center;">PHP</th>
        <th style="border : 1px solid #2d3436; background:#F5F6FA; text-align:center;">USD</th>
        <th style="border : 1px solid #2d3436; background:#F5F6FA; text-align:center;">PHP</th>
        <th style="border : 1px solid #2d3436; background:#F5F6FA; text-align:center;">USD</th>
        <th style="border : 1px solid #2d3436; background:#F5F6FA; text-align:center;">PHP</th>
        <th style="border : 1px solid #2d3436; background:#F5F6FA; text-align:center;">USD</th>
        <th style="border : 1px solid #2d3436; background:#F5F6FA; text-align:center;">PHP</th>
        <th style="border : 1px solid #2d3436; background:#F5F6FA; text-align:center;">USD</th>
        <th style="border : 1px solid #2d3436; background:#F5F6FA; text-align:center;">PHP</th>
        <th style="border : 1px solid #2d3436; background:#F5F6FA; text-align:center;">USD</th>
        <th style="border : 1px solid #2d3436; background:#F5F6FA; text-align:center;"></th>
        <th style="border : 1px solid #2d3436; background:#F5F6FA; text-align:center;"></th>
        <th style="border : 1px solid #2d3436; background:#F5F6FA; text-align:center;"></th>
      </tr> 
    </thead>
    <tbody>
      @foreach($ap as $row)
      <tr>
        <td style="border : 1px solid #2d3436;">{{ $row['supplier_name'] }}</td>
        <td style="border : 1px solid #2d3436; text-align:right;">{{ number_format($row['total_accounts_payable_php'],2) }}</td>
        <td style="border : 1px solid #2d3436; text-align:right;">{{ number_format($row['total_accounts_payable_usd'],2) }}</td>
        <td style="border : 1px solid #2d3436; text-align:center;">{{ $row['terms'] }}</td>
        <td style="border : 1px solid #2d3436; text-align:right;">$ {{ number_format($row['first_php'],2) }}</td>
        <td style="border : 1px solid #2d3436; text-align:right;">{{ number_format($row['first_usd'],2) }}</td>

        <td style="border : 1px solid #2d3436; text-align:right;">{{ number_format($row['second_php'],2) }}</td>
        <td style="border : 1px solid #2d3436; text-align:right;">{{ number_format($row['second_usd'],2) }}</td>

        <td style="border : 1px solid #2d3436; text-align:right;">{{ number_format($row['third_php'],2) }}</td>
        <td style="border : 1px solid #2d3436; text-align:right;">{{ number_format($row['third_usd'],2) }}</td>

        <td style="border : 1px solid #2d3436; text-align:right;">{{ number_format($row['fourth_php'],2) }}</td>
        <td style="border : 1px solid #2d3436; text-align:right;">{{ number_format($row['fourth_usd'],2) }}</td>

        <td style="border : 1px solid #2d3436; text-align:right;">{{ number_format($row['fifth_php'],2) }}</td>
        <td style="border : 1px solid #2d3436; text-align:right;">{{ number_format($row['fifth_usd'],2) }}</td>

        <td style="border : 1px solid #2d3436; text-align:right;">{{ number_format($row['sixth_php'],2) }}</td>
        <td style="border : 1px solid #2d3436; text-align:right;">{{ number_format($row['sixth_usd'],2) }}</td>

        <td style="border : 1px solid #2d3436; text-align:right;">{{ number_format($row['accounts_payable_php'],2) }}</td>
        <td style="border : 1px solid #2d3436; text-align:right;">{{ number_format($row['accounts_payable_usd'],2) }}</td>
        <td style="border : 1px solid #2d3436; text-align:right;">{{ number_format($row['unreleased_checked'],2) }}</td>
        <td style="border : 1px solid #2d3436;"></td>
        <td style="border : 1px solid #2d3436;"></td>
      </tr>
      @endforeach
      <tr>
        <td>TOTAL  PHP</td>
        <td style="text-align:right;">{{ number_format(array_sum(array_column($ap,'total_accounts_payable_php')),2) }}</td>
        <td style="text-align:right;"></td>
        <td style="text-align:center;"></td>
        <td style="text-align:right;">{{ number_format(array_sum(array_column($ap,'first_php')),2) }}</td>
        <td style="text-align:right;"></td>

        <td style="text-align:right;">{{ number_format(array_sum(array_column($ap,'second_php')),2) }}</td>
        <td style="text-align:right;"></td>

        <td style="text-align:right;">{{ number_format(array_sum(array_column($ap,'third_php')),2) }}</td>
        <td style="text-align:right;"></td>

        <td style="text-align:right;">{{ number_format(array_sum(array_column($ap,'fourth_php')),2) }}</td>
        <td style="text-align:right;"></td>

        <td style="text-align:right;">{{ number_format(array_sum(array_column($ap,'fifth_php')),2) }}</td>
        <td style="text-align:right;"></td>

        <td style="text-align:right;">{{ number_format(array_sum(array_column($ap,'sixth_php')),2) }}</td>
        <td style="text-align:right;"></td>

        <td style="text-align:right;">{{ number_format(array_sum(array_column($ap,'accounts_payable_php')),2) }}</td>
        <td style="text-align:right;"></td>
        <td style="text-align:right;">{{ number_format(array_sum(array_column($ap,'unreleased_checked')),2) }}</td>

      </tr>
      <tr>
        <td>TOTAL  USD</td>
        <td style="text-align:right;"></td>
        <td style="text-align:right;">{{ number_format(array_sum(array_column($ap,'total_accounts_payable_usd')),2) }}</td>
        <td style="text-align:center;"></td>
        <td style="text-align:right;"></td>
        <td style="text-align:right;">{{ number_format(array_sum(array_column($ap,'first_usd')),2) }}</td>

        <td style="text-align:right;"></td>
        <td style="text-align:right;">{{ number_format(array_sum(array_column($ap,'second_usd')),2) }}</td>

        <td style="text-align:right;"></td>
        <td style="text-align:right;">{{ number_format(array_sum(array_column($ap,'third_usd')),2) }}</td>

        <td style="text-align:right;"></td>
        <td style="text-align:right;">{{ number_format(array_sum(array_column($ap,'fourth_usd')),2) }}</td>

        <td style="text-align:right;"></td>
        <td style="text-align:right;">{{ number_format(array_sum(array_column($ap,'fifth_usd')),2) }}</td>

        <td style="text-align:right;"></td>
        <td style="text-align:right;">{{ number_format(array_sum(array_column($ap,'sixth_usd')),2) }}</td>

        <td style="text-align:right;"></td>
        <td style="text-align:right;">{{ number_format(array_sum(array_column($ap,'accounts_payable_usd')),2) }}</td>
        <td style="text-align:right;"></td>
      </tr>
      <tr>
        <td>GRANDTOTAL</td>
        <td style="text-align:right;">{{ number_format(array_sum(array_column($ap,'total_accounts_payable_php')) + (array_sum(array_column($ap,'total_accounts_payable_usd')) * 50),2) }}</td>
        <td style="text-align:right;"></td>
        <td style="text-align:center;"></td>
        <td style="text-align:right;">{{ number_format(array_sum(array_column($ap,'first_php')) + (array_sum(array_column($ap,'first_usd')) * 50),2) }}</td>
        <td style="text-align:right;"></td>

        <td style="text-align:right;">{{ number_format(array_sum(array_column($ap,'second_php')) + (array_sum(array_column($ap,'second_usd')) * 50),2) }}</td>
        <td style="text-align:right;"></td>

        <td style="text-align:right;">{{ number_format(array_sum(array_column($ap,'third_php')) + (array_sum(array_column($ap,'third_usd')) * 50),2) }}</td>
        <td style="text-align:right;"></td>

        <td style="text-align:right;">{{ number_format(array_sum(array_column($ap,'fourth_php')) + (array_sum(array_column($ap,'fourth_usd')) * 50),2) }}</td>
        <td style="text-align:right;"></td>

        <td style="text-align:right;">{{ number_format(array_sum(array_column($ap,'fifth_php')) + (array_sum(array_column($ap,'fifth_usd')) * 50),2) }}</td>
        <td style="text-align:right;"></td>

        <td style="text-align:right;">{{ number_format(array_sum(array_column($ap,'sixth_php')) + (array_sum(array_column($ap,'sixth_usd')) * 50),2) }}</td>
        <td style="text-align:right;"></td>

        <td style="text-align:right;">{{ number_format(array_sum(array_column($ap,'accounts_payable_php')) + (array_sum(array_column($ap,'accounts_payable_usd')) * 50),2) }}</td>
        <td style="text-align:right;"></td>
        <td style="text-align:right;">{{ number_format(array_sum(array_column($ap,'unreleased_checked')),2) }}</td>

      </tr>
    </tbody>
  </table>

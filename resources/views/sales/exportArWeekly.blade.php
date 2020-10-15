<style>
  .head{
    padding: 5px;
  }

  .gray {
    background-color : #AFAFAF;
    color: #fff;
  }

  .yellow {
  	background-color : #EDF935;
  }

  .orange {
  	background-color: #FFD288;
  }

</style>
<p>{{ $title }}</p>
<table border="1" cellpadding="0" cellspacing="0">
	<thead>
		<tr style="text-align: center">
			<th class="head" style="background-color : #AFAFAF; color: #fff;">Customer name</th>
			<th class="head" style="background-color : #AFAFAF; color: #fff;">Payment terms</th>
			<th class="head" style="background-color : #EDF935;">Due account</th>
			<th class="head" style="background-color : #EDF935;">Overdue account</th>
			<th class="head" style="background-color : #EDF935;">Delinquent account</th>
			<th class="head" style="background-color : #FFD288;">Projected collectibles for the week</th>
		</tr>
	</thead>
	<tbody>
		@foreach($amounts as $amount)
			<tr>
				<td style="text-align: center">{{ $amount['customer'] }}</td>
				<td style="text-align: center">{{ $amount['terms'] }}</td>
				<td style="text-align: right">{{ $amount['due'] }}</td>
				<td style="text-align: right">{{ $amount['overdue'] }}</td>
				<td style="text-align: right">{{ $amount['delinquent'] }}</td>
				<td style="text-align: right">{{ $amount['collectibles'] }}</td>
			</tr>
		@endforeach
		
	</tbody>
</table>
<br>
<table border="1" cellpadding="0" cellspacing="0">
	<tbody>
		<tr>
			<td style="text-align: center" colspan="2">PHP TOTAL: </td>
			<td style="text-align: right; background-color : #EDF935;">{{ $totalPhp['due'] }}</td>
			<td style="text-align: right; background-color : #EDF935;">{{ $totalPhp['overdue'] }}</td>
			<td style="text-align: right; background-color : #EDF935;">{{ $totalPhp['delinquent'] }}</td>
			<td style="text-align: right; background-color : #EDF935;">{{ $totalPhp['collectibles'] }}</td>
		</tr>
		<tr>
			<td style="text-align: center" colspan="2">USD TOTAL: </td>
			<td style="text-align: right; background-color : #EDF935;">{{ $totalUsd['due'] }}</td>
			<td style="text-align: right; background-color : #EDF935;">{{ $totalUsd['overdue'] }}</td>
			<td style="text-align: right; background-color : #EDF935;">{{ $totalUsd['delinquent'] }}</td>
			<td style="text-align: right; background-color : #EDF935;">{{ $totalUsd['collectibles'] }}</td>
		</tr>
	</tbody>
</table>
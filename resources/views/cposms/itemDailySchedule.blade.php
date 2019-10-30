
<div style="margin : 0; padding : 0">
	
	<div>Delivery schedule for {{ $date }}</div>
	<table 
		style="font-size : 9px; padding : 0; margin : 0; width: 100%"
		border="1"
		cellspacing="0" 
		cellpadding="3"
	>
		<thead>
			<tr style="background : #3c8dbc; color : #ffffff; text-align: center;">
				<th>CUSTOMER</th>
				<th>PO NO.</th>
				<th>JO NO.</th>
				<th>ITEM DESC</th>
				<th>QUANTITY</th>
				<th>REMARKS</th>
				<th>PROD. QTY</th>
				<th>PROD. REMARKS</th>
				<th>OTHERS</th>
			</tr>
		</thead>

		<tbody>
			@foreach($schedules as $schedule)
			<tr>
				<td style="width: 15%">{{ $schedule['customer'] }}</td>
				<td style="width: 10%">{{ $schedule['po'] }}</td>
				<td style="width: 15%">{{ $schedule['jo'] }}</td>
				<td style="width: 15%">{{ $schedule['itemdesc'] }}</td>
				<td style="width: 10%">{{ $schedule['quantity'] }}</td>
				<td style="width: 10%">{{ $schedule['remarks'] }}</td>
				<td style="width: 10%">{{ $schedule['commited_qty'] }}</td>
				<td style="width: 10%">{{ $schedule['prod_remarks'] }}</td>
				<td style="width: 10%">{{ $schedule['others'] }}</td>
			</tr>
			@endforeach
		</tbody>

	</table>

</div>
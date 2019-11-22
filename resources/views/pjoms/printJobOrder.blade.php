<style>
.page-break {
	page-break-after: always;
}
</style>

@foreach($joborders as $jo)
	<table 
	style="font-size : 12px; padding : 0; margin-bottom : 40px; width: 100%"
	border="1"
	cellspacing="0" 
	cellpadding="5"
	>
	<thead>
		<tr style="background : #3c8dbc; color : #ffffff;">
			<th colspan="2">JOB ORDER DETAILS</th>
		</tr>
	</thead>

	<tbody>
		<tr>
			<td width="25%">JOB ORDER</td>
			<td width="75%">{{ $jo['jo_num'] }}</td>
		</tr>
		<tr>
			<td width="25%">CUSTOMER</td>
			<td width="75%">{{ $jo['customer'] }}</td>
		</tr>
		<tr>
			<td width="25%">DATE ISSUED</td>
			<td width="75%">{{ $jo['date_issued'] }}</td>
		</tr>
		<tr>
			<td width="25%">DATE NEEDED</td>
			<td width="75%">{{ $jo['date_needed'] }}</td>
		</tr>
		<tr>
			<td width="25%">PO NO.</td>
			<td width="75%">{{ $jo['po_num'] }}</td>
		</tr>
		<tr>
			<td width="25%">CODE</td>
			<td width="75%">{{ $jo['code'] }}</td>
		</tr>
		<tr>
			<td width="25%">ITEM DESC</td>
			<td width="75%">{{ $jo['item_desc'] }}</td>
		</tr>
		<tr>
			<td width="25%">PART NUMBER</td>
			<td width="75%">{{ $jo['part_num'] }}</td>
		</tr>
		<tr>
			<td width="25%">QUANTITY</td>
			<td width="75%">{{ $jo['quantity'] }}</td>
		</tr>
		<tr>
			<td width="25%">REMARKS</td>
			<td width="75%">{{ $jo['remarks'] }}</td>
		</tr>

	</tbody>

</table>

@if(( $loop->iteration % 3 === 0) && (!$loop->last) )
	<div class="page-break"></div>
@endif

@endforeach

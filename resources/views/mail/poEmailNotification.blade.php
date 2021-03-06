<div style="line-height: 0.5; font-size : 14px">
	<p>Customer name : {{ $poDetails->customer }}</p>
	<p>Purchase order no. : {{ $poDetails->po_num }}</p>
	<p>Date : {{ $poDetails->date }}</p>
	<p>Item count : {{ $poDetails->itemCount }}</p>
</div>
<table 
	style="font-size : 12px; padding : 0; margin : 0; width: 100%"
	border="1"
	cellspacing="0" 
	cellpadding="3"
	>
	<thead>
		<tr style="background : #3c8dbc; color : #ffffff; text-align: center;">
			<th>Code</th>
			<th>Part number</th>
			<th>Item Description</th>
			<th>Quantity</th>
			<th>Unit</th>
			<th>Delivery date</th>
		</tr>
	</thead>

	<tbody>
		@foreach($poDetails->items as $item)
		<tr>
			<td>{{ $item['code'] }}</td>
			<td>{{ $item['partnum'] }}</td>
			<td>{{ $item['itemdesc'] }}</td>
			<td>{{ $item['quantity'] }}</td>
			<td>{{ $item['unit'] }}</td>
			<td>{{ $item['deliverydate'] }}</td>
		</tr>
		@endforeach
	</tbody>

</table>
<h5>This email is auto generated by the system.</h5>
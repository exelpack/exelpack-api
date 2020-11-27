<h3>PURCHASE REQUEST DETAILS</h3>
<div style="font-size : 14px">
	<p>Customer's purchase order : {{ $prDetails->customerPo }}</p>
	<p>Supplier : {{ $prDetails->supplier }}</p>
	<p>Purchase request no. : {{ $prDetails->prNumber }}</p>
	<p>Purchase request date : {{ $prDetails->prDate }}</p>
	<p>Requested by : {{ $prDetails->requestee }}</p>
	<p>You can approve this request on <a href="https://odash.minamotocorpsystems.com">https://odash.minamotocorpsystems.com</a></p>
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
			<th>Material Specification</th>
			<th>Quantity</th>
			<th>Unit</th>
			<th>Unit Price</th>
			<th>Amount</th>
			<th>Delivery date</th>
		</tr>
	</thead>
	<tbody style="text-align: center;">
		@foreach($prDetails->items as $item)
		<tr>
			<td>{{ $item->pri_code }}</td>
			<td>{{ $item->pri_mspecs }}</td>
			<td>{{ $item->pri_quantity }}</td>
			<td>{{ $item->pri_uom }}</td>
			<td>{{ $item->pri_unitprice }}</td>
			<td>{{ number_format($item->pri_unitprice * $item->pri_quantity, 2)  }}</td>
			<td>{{ $item->pri_deliverydate }}</td>
		</tr>
		@endforeach
	</tbody>
</table>
<h5>This email is auto generated by the system.</h5>
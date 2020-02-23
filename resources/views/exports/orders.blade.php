<table>
    <thead>
    <tr>
        <th>Name</th>
        <th>Email</th>
    </tr>
    </thead>
    <tbody>
    @foreach($orders as $order)
        <tr>
            <td>{{ $order->order_sn }}</td>
            <td>{{ $order->pay_status_text }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
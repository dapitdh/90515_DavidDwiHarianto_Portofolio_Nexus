@extends('layout.admin_navbar')
@section('title')
    Transaction Detail
@endsection
@section('content')

<div class="px-8 py-6 pb-[4rem] pt-[5rem]">
    <div class="text-2xl font-semibold mb-6" style="color: #412f26">
        Transaction Detail
    </div>

    <!-- Search and Date Range -->
    <div class="flex flex-col md:flex-row justify-center items-center gap-4 mb-6 px-4">
        <input
            type="text"
            placeholder="Search Transactions"
            id="searchInput"
            oninput="filterTransactions()"
            class="input w-full bg-white border-[#412f26] text-[#412f26] focus:outline-none focus:ring focus:ring-[#412f26]"
        />
    </div>

    <!-- Table -->
    <div class="overflow-x-auto mx-auto max-w-6xl shadow-md border border-gray-300 rounded-lg">
        <table class="table-auto w-full text-center">
            <thead class="bg-[#412f26] text-white">
                <tr>
                    <th class="p-4">Tanggal</th>
                    <th class="p-4">ID Order</th>
                    <th class="p-4">Nama Customer</th>
                    <th class="p-4">Nomor Meja</th>
                    <th class="p-4">Tipe Order</th>
                    <th class="p-4">Jumlah Pendapatan</th>
                    <th class="p-4">Status</th>
                    <th class="p-4">Detail</th>
                </tr>
            </thead>
            <tbody class="bg-white text-black" id="transactionTable">
                @foreach ($orders as $order)
                <tr class="border-b border-gray-300">
                    <td class="p-4">{{ date('d F Y', strtotime($order->waktu_transaksi)) }}</td>
                    <td class="p-4">{{ $order->id_order }}</td>
                    <td class="p-4">{{ $order->customer }}</td>
                    <td class="p-4">{{ $order->meja }}</td>
                    <td class="p-4">{{ $order->tipe_order }}</td>
                    <td class="p-4">Rp{{ number_format($order->total_harga, 0, ',', '.') }}</td>
                    <td class="p-4">{{ $order->status }}</td>
                    <td class="p-4">
                        <a href="{{route('admin.detail_transaction', $order->id_order)}}" class="text-[#412f26] hover:underline">View Detail</a>
                        <a href="{{ route('admin.transaction.export.id_order', $order->id_order) }}" class="text-[#412f26] hover:underline">Download Excel</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<script>
    function filterTransactions() {
        const searchInput = document.getElementById('searchInput').value.toLowerCase();
        const rows = document.querySelectorAll('#transactionTable tr');

        rows.forEach(row => {
            const date = row.querySelector('td:nth-child(1)').textContent.toLowerCase();
            const idOrder = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
            const customer = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
            const meja = row.querySelector('td:nth-child(4)').textContent.toLowerCase();
            const tipeOrder = row.querySelector('td:nth-child(5)').textContent.toLowerCase();
            const amount = row.querySelector('td:nth-child(6)').textContent.toLowerCase();
            const status = row.querySelector('td:nth-child(7)').textContent.toLowerCase();

            if (date.includes(searchInput) || idOrder.includes(searchInput) || customer.includes(searchInput) || meja.includes(searchInput) || tipeOrder.includes(searchInput) || amount.includes(searchInput) || status.includes(searchInput)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
</script>
@endsection
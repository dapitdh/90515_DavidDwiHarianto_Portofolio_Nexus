@extends('layout.admin_navbar')
@section('title')
    Add Menu
@endsection
@section('content')
    <form action="{{route('admin.store')}}" method="post" enctype="multipart/form-data">
        @csrf
        <label for="nama">Nama Menu :</label>
        <input type="text" name="nama_menu" id="nama">
        <br>
        <label for="harga">Harga :</label>
        <input type="number" name="harga" id="harga">
        <br>
        <label for="deskripsi">Deskripsi :</label>
        <textarea name="deskripsi" id="deskripsi"></textarea>
        <br>
        <label for="stok">Stok :</label>
        <input type="number" name="stok" id="stok">
        <br>
        <label for="gambar">Gambar :</label>
        <input type="file" name="gambar" id="gambar">
        <br>
        
        <button type="button" onclick="addAddOns()">Add Add-Ons</button>
        <br>
        <div id="addOns">
        </div>
        <button type="submit">Add Menu</button>
    </form>
    <script>
        let i = 0;
        function togglePromo() {
            const promoContainer = document.getElementById('promo-container');
            if (promoContainer.style.display === 'none') {
                promoContainer.style.display = 'block';
            } else {
                promoContainer.style.display = 'none';
            }
        }
        
        function addAddOns() {
            const AddOns = document.getElementById('addOns');
            AddOns.innerHTML += `
                <input type="text" name="addOns[]" placeholder="Add-Ons"><br>
                <input type="text" name="harga_addOns[]" placeholder="Harga"><br>
            `;
        }
    </script>
@endsection
<?php
namespace App\Livewire;

use App\Models\AddOn;
use App\Models\DetailAddon;
use App\Models\Menu;
use App\Models\Order;
use Livewire\Component;
use App\Models\DetailOrder;
use Illuminate\Support\Carbon;

class PesanManual extends Component
{
    public $items = [];
    public $addItems = [];
    public $qtyMenu = [];
    public $qtyAddOns = [];
    public $customer, $tipeOrder, $meja;
    public $antrian;
    public $totalHarga = 0;

    public function mount()
    {
        $this->items = Menu::with('addOns')->get();
        $this->addItems = Addon::all();

        // Inisialisasi qty untuk setiap item menu
        foreach ($this->items as $item) {
            $this->qtyMenu[$item->id_menu] = 0;
        }

        foreach ($this->addItems as $addItem) {
            $this->qtyAddOns[$addItem->id_addon] = 0;
        }

        $this->updateTotal();
    }

    // Fungsi untuk menambah kuantitas Menu
    public function tambahMenu($id)
    {
        // Periksa apakah ID menu ada dalam qtyMenu
        if (isset($this->qtyMenu[$id])) {
            // Jika ada, tambah kuantitas menu
            $this->qtyMenu[$id]++;
        } else {
            // Jika tidak ada, inisialisasi kuantitas dengan 1
            $this->qtyMenu[$id] = 1;
        }
            
            $this->updateTotal();
    }

    // Fungsi untuk mengurangi kuantitas Menu
    public function kurangMenu($id)
    {
        // Pastikan qty lebih besar dari 0 sebelum dikurangi
        if (isset($this->qtyMenu[$id]) && $this->qtyMenu[$id] > 0) {
            $this->qtyMenu[$id]--;
        }
        $this->updateTotal();
    }

    // Fungsi untuk menambah kuantitas Add Ons
    public function tambahAddon($id)
    {
        // Cek apakah qty sudah terinisialisasi
        if (isset($this->qtyAddOns[$id])) {
            $this->qtyAddOns[$id]++;
        } else {
            $this->qtyAddOns[$id] = 1;
        }
        $this->updateTotal();
    }

    // Fungsi untuk mengurangi kuantitas Add Ons
    public function kurangAddon($id)
    {
        // Pastikan qty lebih besar dari 0 sebelum dikurangi
        if (isset($this->qtyAddOns[$id]) && $this->qtyAddOns[$id] > 0) {
            $this->qtyAddOns[$id]--;
        }
        $this->updateTotal();
    }

    // Fungsi untuk mengonfirmasi pesanan
    public function confirmOrder()
    {
        if (empty($this->customer || $this->tipeOrder)) {
            session()->flash('error', 'Seluruh Field harus diisi!');
            return;
        }

        // Ambil tanggal transaksi dan antrian (auto increment)
        $tanggalTransaksi = Carbon::now()->format('Ymd');
        $lastOrder = Order::where('antrian', '>', 0)->orderBy('antrian', 'desc')->first(); // Ambil antrian terakhir
        $antrian = $lastOrder ? $lastOrder->antrian + 1 : 1; // Antrian dimulai dari 1

        // Membuat id_order berdasarkan format yang diinginkan
        $idOrder = "ORD" . $tanggalTransaksi . "-" . $antrian;

        // Menyimpan data pesanan ke dalam tabel orders
        $order = Order::create([
            'id_order' => $idOrder,
            'id_user' => 99999999,
            'antrian' => $antrian,
            'customer' => $this->customer,
            'meja' => $this->tipeOrder == 'Dine In' ? $this->meja : 0, // Jika tipe order adalah Take Away, meja diisi null
            'tipe_order' => $this->tipeOrder,
            'status' => 'Open Bill',
            'total_harga' => $this->totalHarga,
            'waktu_transaksi' => Carbon::now(),
        ]);

        // Simpan item menu yang dipesan ke tabel detail_orders
        foreach ($this->items as $item) {
            if ($this->qtyMenu[$item->id_menu] > 0) {
                $idDetailOrder = 'OD' . uniqid();

                // Buat record di tabel detail_orders
                $detailOrder = DetailOrder::create([
                    'id_detailorder' => $idDetailOrder,
                    'id_order' => $order->id_order,
                    'id_menu' => $item->id_menu,
                    'kuantitas' => $this->qtyMenu[$item->id_menu],
                    'harga_menu' => $item->harga,
                    'notes' => ""
                ]);

                // Simpan add-ons yang terkait dengan menu ini ke tabel detail_addons
                foreach ($this->addItems as $addItem) {
                    if ($addItem->id_menu == $item->id_menu && isset($this->qtyAddOns[$addItem->id_addon]) && $this->qtyAddOns[$addItem->id_addon] > 0) {
                        DetailAddon::create([
                            'id_detailaddon' => 'DA' . substr(uniqid(), -5) . '-' . mt_rand(10, 99),
                            'id_addon' => $addItem->id_addon,
                            'id_detailorder' => $detailOrder->id_detailorder,
                            'kuantitas' => $this->qtyAddOns[$addItem->id_addon],
                            'harga' => $addItem->harga * $this->qtyAddOns[$addItem->id_addon],
                        ]);
                    }
                }
            }
        }

        $this->reset('customer', 'tipeOrder', 'meja');
    
        // Inisialisasi kembali kuantitas untuk setiap item menu
        foreach ($this->items as $item) {
            $this->qtyMenu[$item->id_menu] = 0; // Reset kuantitas untuk setiap item
        }

        foreach ($this->addItems as $addItem) {
            $this->qtyAddOns[$addItem->id_addon] = 0; // Reset kuantitas untuk setiap item
        }

        // Perbarui total harga setelah reset
        $this->updateTotal();

        // Menampilkan pesan sukses
        session()->flash('success', 'Pesanan berhasil dibuat!');
    }

    // Fungsi untuk memperbarui total harga
    private function updateTotal()
    {
        $this->totalHarga = 0;
        foreach ($this->items as $item) {
            $this->totalHarga += $this->qtyMenu[$item->id_menu] * $item->harga;
        }

        foreach ($this->addItems as $addItem) {
            $this->totalHarga += $this->qtyAddOns[$addItem->id_addon] * $addItem->harga;
        }
    }

    public function render()
    {
        return view('livewire.pesan-manual');
    }
}

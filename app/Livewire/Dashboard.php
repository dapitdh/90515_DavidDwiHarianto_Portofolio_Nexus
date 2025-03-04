<?php

namespace App\Livewire;

use Exception;
use App\Models\Menu;
use App\Models\Order;
use Livewire\Component;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Log;

class Dashboard extends Component
{
    public $currentTab = 'All';

    //Data Pesanan
    public $orders = [];
    public $selectedOrder;

    //Data Menu Utama
    public $menuItems = [];
    public $quantities = [];
    public $totalPrices = [];

    //Data Add-Ons
    public $addOns = [];
    public $addonQuantities = [];

    //Pop Up Variable
    public $isApproveModalOpen = false;
    public $paymentMethod = 'edc';
    public $amountPaid = 0;
    public $approveDetails = [
        'menuItems' => [],
        'addOns' => [],
        'totalHarga' => 0,
        'orderId' => null
    ];
    public $isEditModalOpen = false;

    public function mount()
    {
        $this->updateOrders();
    }

    public function refreshOrders() 
    { 
        $this->updateOrders(); 
    }

    public function switchTab($tab)
    {
        $this->currentTab = $tab;
        $this->updateOrders();
    }

    public function approveOrder($id)
    {
        // Mengambil data order beserta detail menu dan add-ons
        $order = Order::with(['detailOrders.menu.promo', 'detailOrders.addOns'])->find($id);

        if ($order) {
            $currentTime = now()->format('H:i:s');

            // Siapkan detail menu dan harga
            $menuItems = $order->detailOrders->map(function ($detail) use ($currentTime) {
                $menu = $detail->menu;
                $promo = $menu->promo;
                $isPromoActive = $promo 
                && $promo->status === 'Aktif' // Promo aktif
                && $currentTime >= $promo->waktu_mulai
                && $currentTime <= $promo->waktu_berakhir; // Promo berlaku pada jam tertentu

                $harga = $isPromoActive ? $promo->harga_promo : $menu->harga;

                return [
                    'nama_menu' => $detail->menu->nama_menu,
                    'kuantitas' => $detail->kuantitas,
                    'harga' => $harga, // Mengambil harga dari tabel menus
                    'total_harga' => $detail->kuantitas * $harga, // Menghitung total harga per menu
                ];
            });

            // Siapkan detail add-ons dan harga
            $addOns = $order->detailOrders->flatMap(function ($detail) {
                return $detail->addOns->map(function ($addon) {
                    return [
                        'nama_addon' => $addon->addon->nama_addon,
                        'kuantitas' => $addon->kuantitas,
                        'harga' => $addon->addon->harga, // Mengambil harga dari tabel add_ons
                        'total_harga' => $addon->kuantitas * $addon->addon->harga, // Menghitung total harga per add-on
                    ];
                });
            });

            // Menghitung total harga untuk menu dan add-ons
            $totalHarga = $menuItems->sum('total_harga') + $addOns->sum('total_harga');

            // Menyimpan data ke modal untuk ditampilkan
            $this->approveDetails = [
                'menuItems' => $menuItems->toArray(),
                'addOns' => $addOns->toArray(),
                'totalHarga' => $totalHarga,
                'orderId' => $order->id_order,
            ];

            // Membuka modal approve
            $this->isApproveModalOpen = true;
        }
    }


    // Fungsi Finalisasi Pembayaran
    public function finalizePayment($id)
    {
        Log::info('Memulai finalizePayment untuk orderId:', ['orderId' => $id]);
        $order = Order::find($id);
        if ($order && $order->metode_pembayaran === null) {
            if ($this->paymentMethod === 'cash') {
                if ($this->amountPaid < $this->approveDetails['totalHarga']) {
                    $this->addError('amountPaid', 'Jumlah uang yang diberikan kurang.');
                    return;
                }
        
                $order->bayar = $this->amountPaid;
                $order->kembalian = $this->amountPaid - $this->approveDetails['totalHarga'];
            } else {
                $order->bayar = $this->approveDetails['totalHarga'];
                $order->kembalian = 0;
            }
        
            $paymentMethod = $this->paymentMethod;

            $order->update([
                'id_user' => session('id_user'), // Nama cashier yang approved
                'metode_pembayaran' => $paymentMethod,
                'status' => 'Paid'
            ]);

            $this->isApproveModalOpen = false;
            $this->updateOrders();
        }
        // Kirim event ke frontend untuk memicu printReceipt
        Log::info('Dispatching event bayarBerhasil for orderId:', ['orderId' => $id]);
        $this->dispatch('bayarBerhasil', ['orderId' => $id]);

    }

    public function editOrder($orderId)
    {
        $order = Order::with(['detailOrders.menu', 'detailOrders.addOns'])->find($orderId);

        if ($order) {
            // Ambil detail order dan addons
            $this->menuItems = $order->detailOrders; // Menu utama
            foreach ($this->menuItems as $item) {
                $this->quantities[$item->id_detailorder] = $item->kuantitas;
                $this->totalPrices[$item->id_detailorder] = $item->harga_menu * $item->kuantitas;
            }

            $this->addOns = $order->detailOrders->flatMap(function ($detailOrder) {
                return $detailOrder->addOns;
            });

            foreach ($this->addOns as $addon) {
                $this->addonQuantities[$addon->id_detailaddon] = $addon->kuantitas;
            }

            $this->selectedOrder = $order;
            $this->isEditModalOpen = true;
        }
    }

    public function saveOrder()
    {
        $totalHarga = 0;

        // Update menu utama
        foreach ($this->menuItems as $menu) {
            $detailOrder = $menu->find($menu->id_detailorder);
            if ($detailOrder) {
                $kuantitasBaru = $this->quantities[$menu->id_detailorder] ?? $menu->kuantitas;
                $kuantitasLama = $menu->kuantitas;

                $promo = $menu->menu->promo;

                // Cek promo aktif
                $isPromoActive = $promo 
                && $promo->status === 'Aktif'
                && now()->format('H:i:s') >= $promo->waktu_mulai
                && now()->format('H:i:s') <= $promo->waktu_berakhir;

                $hargaMenu = $isPromoActive ? $promo->harga_promo : $menu->menu->harga;

                $perubahan = $kuantitasBaru - $kuantitasLama;

                // Cek stok mencukupi
                if ($perubahan > 0 && $menu->menu->stock < $perubahan) {
                    session()->flash('error', "Stok untuk {$menu->menu->nama_menu} tidak mencukupi!");
                    return;
                }

                $detailOrder->update([
                    'kuantitas' => $kuantitasBaru,
                    'harga_menu' => $hargaMenu,
                ]);

                // Update stok menu
                $menu->menu->stock -= $perubahan;
                $menu->menu->save();

                $totalHarga += $hargaMenu * $kuantitasBaru;
            }
        }

        // Update addons
        foreach ($this->addOns as $addon) {
            $detailAddon = $addon->find($addon->id_detailaddon);
            if ($detailAddon) {
                $kuantitas = $this->addonQuantities[$addon->id_detailaddon] ?? $addon->kuantitas;
                $hargaAddon = $addon->addon->harga;

                $detailAddon->update([
                    'kuantitas' => $kuantitas,
                    'harga' => $hargaAddon,
                ]);

                $totalHarga += $hargaAddon * $kuantitas;
            }
        }

        // Update total harga di tabel orders
        $this->selectedOrder->update([
            'total_harga' => $totalHarga,
        ]);

        $this->isEditModalOpen = false;
        $this->updateOrders();
    }

    public function increaseQuantity($id_detailorder)
    {
        
        if (isset($this->quantities[$id_detailorder])) {
            $this->quantities[$id_detailorder]++;
            $this->updatePrice($id_detailorder); // Update harga saat kuantitas bertambah
            $this->updateOrderTotal(); // Update total harga order
        }
    }

    public function decreaseQuantity($id_detailorder)
    {
        if (isset($this->quantities[$id_detailorder]) && $this->quantities[$id_detailorder] > 1) {
            $this->quantities[$id_detailorder]--;
            $this->updatePrice($id_detailorder); // Update harga saat kuantitas berkurang
            $this->updateOrderTotal(); // Update total harga order
        }
    }

    public function increaseAddonQuantity($id_addon)
    {
        if (isset($this->addonQuantities[$id_addon])) {
            $this->addonQuantities[$id_addon]++;
            $this->updateAddonPrice($id_addon); // Update harga saat kuantitas bertambah
            $this->updateOrderTotal(); // Update total harga order
        }
    }

    public function decreaseAddonQuantity($id_addon)
    {
        if (isset($this->addonQuantities[$id_addon]) && $this->addonQuantities[$id_addon] > 1) {
            $this->addonQuantities[$id_addon]--;
            $this->updateAddonPrice($id_addon); // Update harga saat kuantitas berkurang
            $this->updateOrderTotal(); // Update total harga order
        }
    }

    public function cancelOrder($id)
    {
        // Load order along with related detailOrders and their detailAddon
        $order = Order::with('detailOrders.detailAddon')->find($id);

        if($order && $order->status === 'Open Bill') {
            $order->update(['status' => 'Cancelled']);
            $order->delete(); // Melakukan soft delete pada order

            foreach ($order->detailOrders as $detailOrder) {
                // Update stok menu
                $menu = Menu::find($detailOrder->id_menu);
                if ($menu) {
                    $menu->increment('stock', $detailOrder->kuantitas);
                }

                // Set kuantitas detailOrder to 0 before soft delete
                $detailOrder->kuantitas = 0;
                $detailOrder->save();

                // Soft delete detail order
                $detailOrder->delete(); 

                // Soft delete detail addons jika ada
                $detailAddons = $detailOrder->detailAddon; // Ensure we get all related detailAddon
                if ($detailAddons) {
                    foreach ($detailAddons as $detailAddon) {
                        // Set kuantitas detailAddon to 0 before soft delete
                        $detailAddon->kuantitas = 0;
                        $detailAddon->save();

                        // Soft delete detail addon
                        $detailAddon->delete(); 
                    }
                }
            }

            $this->updateOrders();
        }
    }

    private function updateAddonPrice($id_addon)
    {
        $addon = $this->addOns->firstWhere('id_detailaddon', $id_addon); // ID tetap berupa string
        if ($addon) {
            $this->totalPrices[$id_addon] = $addon->addon->harga * $this->addonQuantities[$id_addon];
        }
    }


    // Fungsi untuk menghitung ulang harga berdasarkan kuantitas
    private function updatePrice($id_detailorder)
    {
        $item = $this->menuItems->firstWhere('id_detailorder', $id_detailorder);
        if ($item) {
            $promo = $item->menu->promo;
            $isPromoActive = $promo 
                && $promo->status === 'Aktif'
                && now()->format('H:i:s') >= $promo->waktu_mulai
                && now()->format('H:i:s') <= $promo->waktu_berakhir;

            $harga = $isPromoActive ? $promo->harga_promo : $item->menu->harga;
            $this->totalPrices[$id_detailorder] = $harga * $this->quantities[$id_detailorder];
        }
    }

    // Fungsi untuk mengupdate total harga pada tabel orders
    private function updateOrderTotal()
    {
        $totalHarga = 0;
        $currentTime = now()->format('H:i:s');

        foreach ($this->menuItems as $item) {
            $menu = $item->menu;
            $promo = $menu->promo;

            // Periksa apakah promo aktif
            $isPromoActive = $promo 
                && $promo->status === 'Aktif'
                && $currentTime >= $promo->waktu_mulai
                && $currentTime <= $promo->waktu_berakhir;

            $hargaMenu = $isPromoActive ? $promo->harga_promo : $menu->harga;

            $totalHarga += $hargaMenu * $this->quantities[$item->id_detailorder];
        }

        // Hitung total harga dari add-ons
        foreach ($this->addOns as $addon) {
            $totalHarga += $addon->addon->harga * $this->addonQuantities[$addon->id_detailaddon];
        }

        // Update total harga pada order
        if ($this->selectedOrder) {
            $this->selectedOrder->update([
                'total_harga' => $totalHarga,
            ]);
        }
    }

    private function updateOrders()
    {
        if ($this->currentTab === 'All') {
            // Jika 'All', ambil semua pesanan
            $this->orders = Order::orderBy('antrian', 'asc')->get();
        } else {
            // Jika Dine In atau Take Away, filter berdasarkan tipe order
            $this->orders = Order::where('tipe_order', $this->currentTab)
                ->orderBy('antrian', 'asc')
                ->get();
        }
    }

    public function render()
    {
        return view('livewire.dashboard', [
            'currentTab' => $this->currentTab,
            'orders' => $this->orders
        ]) ->title('Cashier Dashboard');
    }
}

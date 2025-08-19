<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Carbon\Carbon;
class InvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         // Create sample invoices
        $invoice1 = Invoice::create([
            'client_name' => 'John Doe',
            'client_email' => 'john@example.com',
            'client_address' => '123 Main St, City, State 12345',
            'invoice_date' => Carbon::now(),
            'due_date' => Carbon::now()->addDays(30),
            'tax_rate' => 10.00,
            'status' => 'draft',
            'notes' => 'Thank you for your business!'
        ]);

        // Add items to invoice
        $items1 = [
            ['description' => 'Web Development Service', 'quantity' => 10, 'unit_price' => 100.00],
            ['description' => 'Hosting Setup', 'quantity' => 1, 'unit_price' => 50.00]
        ];

        foreach ($items1 as $itemData) {
            $item = new InvoiceItem($itemData);
            $invoice1->items()->save($item);
        }

        $invoice1->calculateTotals()->save();

        // Create second invoice
        $invoice2 = Invoice::create([
            'client_name' => 'Jane Smith',
            'client_email' => 'jane@example.com',
            'client_address' => '456 Oak Ave, Town, State 67890',
            'invoice_date' => Carbon::now()->subDays(5),
            'due_date' => Carbon::now()->addDays(25),
            'tax_rate' => 8.50,
            'status' => 'sent'
        ]);

        $items2 = [
            ['description' => 'Logo Design', 'quantity' => 1, 'unit_price' => 300.00],
            ['description' => 'Business Card Design', 'quantity' => 1, 'unit_price' => 150.00]
        ];

        foreach ($items2 as $itemData) {
            $item = new InvoiceItem($itemData);
            $invoice2->items()->save($item);
        }

        $invoice2->calculateTotals()->save();
    }
}

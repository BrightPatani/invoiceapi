<?php
// app/Http/Controllers/Api/InvoiceController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $invoices = Invoice::with('items')
                              ->orderBy('created_at', 'desc')
                              ->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Invoices retrieved successfully',
                'data' => $invoices
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve invoices'
            ], 500);
        }
    }

    public function store(StoreInvoiceRequest $request)
    {
        DB::beginTransaction();
        
        try {
            // Create invoice
            $invoice = Invoice::create($request->except('items'));
            
            // Create invoice items
            foreach ($request->items as $itemData) {
                $item = new InvoiceItem($itemData);
                $invoice->items()->save($item);
            }
            
            // Calculate and update totals
            $invoice->calculateTotals()->save();
            
            // Load items relationship
            $invoice->load('items');
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Invoice created successfully',
                'data' => $invoice
            ], 201);
            
        } catch (Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create invoice'
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $invoice = Invoice::with('items')->find($id);
            
            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice not found'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Invoice retrieved successfully',
                'data' => $invoice
            ], 200);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve invoice'
            ], 500);
        }
    }

    public function update(UpdateInvoiceRequest $request, $id)
    {
        DB::beginTransaction();
        
        try {
            $invoice = Invoice::find($id);
            
            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice not found'
                ], 404);
            }
            
            // Update invoice details
            $invoice->update($request->except('items'));
            
            // Update items if provided
            if ($request->has('items')) {
                // Delete existing items
                $invoice->items()->delete();
                
                // Create new items
                foreach ($request->items as $itemData) {
                    $item = new InvoiceItem($itemData);
                    $invoice->items()->save($item);
                }
            }
            
            // Recalculate totals
            $invoice->calculateTotals()->save();
            
            // Load updated items
            $invoice->load('items');
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Invoice updated successfully',
                'data' => $invoice
            ], 200);
            
        } catch (Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update invoice'
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $invoice = Invoice::find($id);
            
            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice not found'
                ], 404);
            }
            
            $invoice->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Invoice deleted successfully'
            ], 200);
            
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete invoice'
            ], 500);
        }
    }
}
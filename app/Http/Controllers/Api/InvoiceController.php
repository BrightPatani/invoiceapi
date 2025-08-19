<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Exception;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
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


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        //validating user ID
        try {
            $validator = Validator::make($request->all(), [
                'client_name' => 'required|string|max:255',
                'client_email' => 'required|email|max:255',
                'client_address' => 'required|string',
                'invoice_date' => 'required|date',
                'due_date' => 'required|date|after_or_equal:invoice_date',
                'tax_rate' => 'nullable|numeric|min:0|max:100',
                'status' => 'nullable|in:draft,sent,paid,overdue',
                'notes' => 'nullable|string',
                'items' => 'required|array|min:1',
                'items.*.description' => 'required|string|max:255',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.unit_price' => 'required|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

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
                'message' => 'Failed to create invoice',
                'errors' => ['server' => $e->getMessage()]
            ], 500);
        }
    }


    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $validator = Validator::make(['id' => $id], [
                'id' => 'required|integer|exists:invoices,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

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
                'message' => 'Failed to retrieve invoice',
                'errors' => ['server' => $e->getMessage()]
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
        {
            DB::beginTransaction();
            
            try {
                // Validating user ID
                $validator = Validator::make(array_merge($request->all(), ['id' => $id]), [
                    'id' => 'required|integer|exists:invoices,id',
                    'client_name' => 'sometimes|string|max:255',
                    'client_email' => 'sometimes|email|max:255',
                    'client_address' => 'sometimes|string',
                    'invoice_date' => 'sometimes|date',
                    'due_date' => 'sometimes|date|after_or_equal:invoice_date',
                    'tax_rate' => 'nullable|numeric|min:0|max:100',
                    'status' => 'sometimes|in:draft,sent,paid,overdue',
                    'notes' => 'nullable|string',
                    'items' => 'sometimes|array|min:1',
                    'items.*.description' => 'sometimes|string|max:255',
                    'items.*.quantity' => 'sometimes|integer|min:1',
                    'items.*.unit_price' => 'sometimes|numeric|min:0',
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => $validator->errors()
                    ], 422);
                }

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
                    'message' => 'Failed to update invoice',
                    'errors' => ['server' => $e->getMessage()]
                ], 500);
            }
        }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            // Validating user ID
            $validator = Validator::make(['id' => $id], [
                'id' => 'required|integer|exists:invoices,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

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
                'message' => 'Failed to delete invoice',
                'errors' => ['server' => $e->getMessage()]
            ], 500);
        }
    }
}

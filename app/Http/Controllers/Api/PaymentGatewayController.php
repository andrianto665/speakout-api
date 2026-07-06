<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Enrollment;
use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Notification;

class PaymentGatewayController extends Controller
{
    public function __construct()
    {
        // Konfigurasi Midtrans
        Config::$serverKey = config('midtrans.server_key');
        Config::$clientKey = config('midtrans.client_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');
    }

    /**
     * Create payment transaction
     * POST /api/payment/create
     */
    public function createPayment(Request $request)
    {
        $request->validate([
            'enrollment_id' => 'required|exists:enrollments,id',
        ]);

        $enrollment = Enrollment::with(['user', 'course'])->find($request->enrollment_id);

        // ✅ Cek kepemilikan enrollment
        if ($enrollment->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // Cek apakah sudah dibayar
        if ($enrollment->payment_status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Pembayaran sudah lunas'
            ], 400);
        }

        // ✅ Override amount untuk testing di environment local/sandbox
        // Nominal asli tetap tersimpan di enrollment->amount_paid (database tidak berubah)
        $grossAmount = (int) $enrollment->amount_paid;
        if (config('app.env') === 'local' && !config('midtrans.is_production')) {
            $grossAmount = 1000; // ✅ nominal testing, bebas diganti sesuai kebutuhan
        }

        $transactionDetails = [
            'order_id' => 'ENR-' . $enrollment->id . '-' . time(),
            'gross_amount' => $grossAmount,
        ];

        // Customer details
        $customerDetails = [
            'first_name' => $enrollment->user->name,
            'email' => $enrollment->user->email,
            'phone' => $enrollment->user->phone ?? '081234567890',
        ];

        // Item details
        $itemDetails = [
            [
                'id' => $enrollment->course->id,
                'price' => $grossAmount, // ✅ pakai variabel yang sama, bukan amount_paid asli
                'quantity' => 1,
                'name' => $enrollment->course->title,
            ],
        ];

        $params = [
            'transaction_details' => $transactionDetails,
            'customer_details' => $customerDetails,
            'item_details' => $itemDetails,
        ];

        try {
            $snapToken = Snap::getSnapToken($params);

            // Simpan snap token dan order_id
            $enrollment->update([
                'payment_gateway_order_id' => $transactionDetails['order_id'],
                'payment_gateway_snap_token' => $snapToken,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment created successfully',
                'data' => [
                    'snap_token' => $snapToken,
                    'order_id' => $transactionDetails['order_id'],
                    'gross_amount' => $enrollment->amount_paid,
                    'enrollment_id' => $enrollment->id,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Midtrans notification handler
     * POST /api/payment/notification
     */
    public function midtransNotification(Request $request)
    {
        $notif = new Notification();

        // ✅ Verifikasi signature supaya notifikasi tidak bisa dipalsukan
        $orderId = $notif->order_id;
        $statusCode = $notif->status_code;
        $grossAmount = $notif->gross_amount;
        $serverKey = config('midtrans.server_key');

        $signature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        if ($signature !== $notif->signature_key) {
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        $transactionStatus = $notif->transaction_status;
        $fraudStatus = $notif->fraud_status;

        // Extract enrollment_id from order_id
        // Format: ENR-{enrollment_id}-{timestamp}
        $parts = explode('-', $orderId);
        $enrollmentId = $parts[1] ?? null;

        if (!$enrollmentId) {
            return response()->json(['message' => 'Invalid order ID'], 400);
        }

        $enrollment = Enrollment::find($enrollmentId);

        if (!$enrollment) {
            return response()->json(['message' => 'Enrollment not found'], 404);
        }

        // Update payment status
        if ($transactionStatus == 'capture') {
            if ($fraudStatus == 'accept') {
                $enrollment->update([
                    'payment_status' => 'paid',
                    'payment_gateway_status' => 'success',
                    'payment_gateway_response' => json_encode($notif->getRawNotification()),
                    'paid_at' => now(),
                ]);
            }
        } else if ($transactionStatus == 'settlement') {
            $enrollment->update([
                'payment_status' => 'paid',
                'payment_gateway_status' => 'success',
                'payment_gateway_response' => json_encode($notif->getRawNotification()),
                'paid_at' => now(),
            ]);
        } else if ($transactionStatus == 'pending') {
            $enrollment->update([
                'payment_status' => 'pending',
                'payment_gateway_status' => 'pending',
                'payment_gateway_response' => json_encode($notif->getRawNotification()),
            ]);
        } else if ($transactionStatus == 'deny' || $transactionStatus == 'expire' || $transactionStatus == 'cancel') {
            $enrollment->update([
                'payment_status' => 'failed',
                'payment_gateway_status' => 'failed',
                'payment_gateway_response' => json_encode($notif->getRawNotification()),
            ]);
        }

        return response()->json(['message' => 'Notification received']);
    }

    /**
     * Check payment status
     * GET /api/payment/status/{enrollment_id}
     */
    public function checkPaymentStatus(Request $request, $enrollmentId)
    {
        $enrollment = Enrollment::with(['user', 'course'])->find($enrollmentId);

        if (!$enrollment) {
            return response()->json([
                'success' => false,
                'message' => 'Enrollment not found'
            ], 404);
        }

        // ✅ Cek kepemilikan enrollment
        if ($enrollment->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'enrollment_id' => $enrollment->id,
                'payment_status' => $enrollment->payment_status,
                'payment_gateway_status' => $enrollment->payment_gateway_status,
                'amount_paid' => $enrollment->amount_paid,
                'course_title' => $enrollment->course->title,
                'is_locked' => $enrollment->payment_status !== 'paid',
                'paid_at' => $enrollment->paid_at,
            ]
        ]);
    }

    public function syncStatus(Request $request, $enrollmentId)
    {
        $enrollment = Enrollment::find($enrollmentId);

        if (!$enrollment || $enrollment->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        if (!$enrollment->payment_gateway_order_id) {
            return response()->json(['success' => false, 'message' => 'Belum ada transaksi'], 404);
        }

        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');

        try {
            $status = \Midtrans\Transaction::status($enrollment->payment_gateway_order_id);

            if (in_array($status->transaction_status, ['capture', 'settlement']) && $status->fraud_status === 'accept') {
                $enrollment->update([
                    'payment_status' => 'paid',
                    'payment_gateway_status' => $status->transaction_status,
                    'payment_method' => 'midtrans',
                    'paid_at' => now(),
                ]);
            }

            return response()->json(['success' => true, 'payment_status' => $enrollment->fresh()->payment_status]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
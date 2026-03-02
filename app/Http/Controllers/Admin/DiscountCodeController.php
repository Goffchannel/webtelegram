<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiscountCode;
use Illuminate\Http\Request;

class DiscountCodeController extends Controller
{
    public function index()
    {
        $codes = DiscountCode::orderByDesc('created_at')->get();
        return view('admin.discount-codes.index', compact('codes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'code'        => 'required|string|max:50|unique:discount_codes,code|alpha_dash',
            'description' => 'nullable|string|max:255',
            'type'        => 'required|in:percent,fixed',
            'value'       => 'required|numeric|min:0.01|max:100',
            'min_amount'  => 'nullable|numeric|min:0',
            'max_uses'    => 'nullable|integer|min:1',
            'expires_at'  => 'nullable|date|after:now',
        ]);

        DiscountCode::create([
            'code'        => strtoupper($request->code),
            'description' => $request->description,
            'type'        => $request->type,
            'value'       => $request->value,
            'min_amount'  => $request->min_amount,
            'max_uses'    => $request->max_uses,
            'expires_at'  => $request->expires_at,
            'is_active'   => true,
        ]);

        return back()->with('success', 'Código creado correctamente.');
    }

    public function toggle(DiscountCode $discountCode)
    {
        $discountCode->update(['is_active' => !$discountCode->is_active]);
        $status = $discountCode->is_active ? 'activado' : 'desactivado';
        return back()->with('success', "Código {$status}.");
    }

    public function destroy(DiscountCode $discountCode)
    {
        $discountCode->delete();
        return back()->with('success', 'Código eliminado.');
    }

    public function validate(Request $request)
    {
        $request->validate([
            'code'   => 'required|string',
            'amount' => 'required|numeric|min:0',
        ]);

        $code = DiscountCode::where('code', strtoupper($request->code))->first();

        if (!$code || !$code->isValid((float) $request->amount)) {
            return response()->json(['valid' => false, 'message' => 'Código inválido, expirado o sin usos disponibles.']);
        }

        $discount = $code->apply((float) $request->amount);

        return response()->json([
            'valid'          => true,
            'discount'       => $discount,
            'formatted'      => '€' . number_format($discount, 2),
            'final_amount'   => round((float) $request->amount - $discount, 2),
            'description'    => $code->description,
            'type'           => $code->type,
            'value'          => $code->value,
        ]);
    }
}

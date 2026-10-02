<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ErrorPreviewController extends Controller
{
    /**
     * Previsualiza en vivo una página de error animada de la suite Drypt
     */
    public function preview(Request $request, string $code)
    {
        $allowedCodes = ['404', '403', '419', '429', '500', '503'];
        if (!in_array($code, $allowedCodes)) {
            abort(404);
        }

        $previewMode = $request->query('mode');

        return response()->view("errors.{$code}", [
            'exception' => new \Exception('Simulación de error en modo previsualización.'),
            'previewMode' => $previewMode,
        ], 200); // 200 para que iframes y navegadores la rendericen sin interrumpir
    }
}

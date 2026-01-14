<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Intent;
use App\Http\Requests\StoreIntentRequest;

class IntentController extends Controller
{
    public function index()
    {
        $intents = Intent::with(['faqs', 'department'])->get();
        return response()->json($intents);
    }

    public function show($id)
    {
        $intent = Intent::with('faqs')->find($id);
        if (!$intent) {
            return response()->json(['message' => 'Intent not found'], 404);
        }
        return response()->json($intent);
    }

    public function store(StoreIntentRequest $request)
    {
        $intent = Intent::create($request->validated());
        return response()->json($intent, 201);
    }

    public function update(StoreIntentRequest $request, $id)
    {
        $intent = Intent::find($id);
        if (!$intent) {
            return response()->json(['message' => 'Intent not found'], 404);
        }

        $intent->update($request->validated());
        return response()->json($intent);
    }

    public function destroy($id)
    {
        $intent = Intent::find($id);
        if (!$intent) {
            return response()->json(['message' => 'Intent not found'], 404);
        }

        $intent->delete();
        return response()->json(['message' => 'Intent deleted']);
    }
}

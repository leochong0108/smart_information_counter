<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Intent;

class intentController extends Controller
{
    public function index()
    {
        $intents = Intent::with('faqs')->get();
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

    public function store(Request $request)
    {
        $request->validate([
            'intent_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        $intent = Intent::create($request->all());
        return response()->json($intent, 201);
    }

    public function update(Request $request, $id)
    {
        $intent = Intent::find($id);
        if (!$intent) {
            return response()->json(['message' => 'Intent not found'], 404);
        }

        $request->validate([
            'intent_name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        $intent->update($request->all());
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

<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = $request->user()->products()->latest()->get();
        return response()->json($products);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'category' => 'nullable|string|max:100',
        ]);

        $product = $request->user()->products()->create($request->all());
        return response()->json($product, 201);
    }

    public function show(Request $request, Product $product)
    {
        if ($product->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        return response()->json($product);
    }

    public function update(Request $request, Product $product)
    {
        if ($product->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'stock' => 'sometimes|integer|min:0',
            'category' => 'nullable|string|max:100',
        ]);

        $product->update($request->all());
        return response()->json($product);
    }

    public function destroy(Request $request, Product $product)
    {
        if ($product->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Accès non autorisé'], 403);
        }

        $product->delete();
        return response()->json(['message' => 'Produit supprimé avec succès']);
    }

      public function getImageAttribute($value)
    {
        if (!$value) {
            return null;
        }
        
        // Si c'est déjà une URL complète, la retourner telle quelle
        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }
        
        // Sinon, construire l'URL complète vers storage/public
        return url('storage/' . $value);
    }

    /**
     * Mutator pour sauvegarder seulement le chemin relatif
     */
    public function setImageAttribute($value)
    {
        // Si c'est une URL complète, extraire seulement le chemin
        if ($value && str_starts_with($value, url('storage/'))) {
            $value = str_replace(url('storage/'), '', $value);
        }
        
        $this->attributes['image'] = $value;
    }
}

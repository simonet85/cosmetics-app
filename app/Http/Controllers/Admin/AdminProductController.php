<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Tag;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['categories', 'images'])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating');

        // Search
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('sku', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->whereHas('categories', function($q) use ($request) {
                $q->where('categories.id', $request->category);
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $products = $query->paginate(20);
        $categories = Category::all();

        return view('admin.products.index', compact('products', 'categories'));
    }

    public function create()
    {
        $categories = Category::all();
        $tags = Tag::all();

        return view('admin.products.create', compact('categories', 'tags'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:100|unique:products,sku',
            'description' => 'required|string',
            'short_description' => 'nullable|string|max:500',
            'price' => 'required|numeric|min:0',
            'compare_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|string|max:100',
            'status' => 'required|in:active,inactive,draft',
            'is_featured' => 'boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:255',
            'categories' => 'nullable|array',
            'categories.*' => 'exists:categories,id',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        try {
            DB::beginTransaction();

            // Generate slug
            $validated['slug'] = Str::slug($validated['name']);

            // Ensure unique slug
            $originalSlug = $validated['slug'];
            $count = 1;
            while (Product::where('slug', $validated['slug'])->exists()) {
                $validated['slug'] = $originalSlug . '-' . $count;
                $count++;
            }

            $product = Product::create($validated);

            // Attach categories
            if ($request->has('categories')) {
                $product->categories()->attach($request->categories);
            }

            // Attach tags
            if ($request->has('tags')) {
                $product->tags()->attach($request->tags);
            }

            // Handle image uploads
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $image) {
                    if ($image->isValid()) {
                        // Get extension
                        $extension = $image->getClientOriginalExtension();
                        if (empty($extension)) {
                            $extension = $image->guessExtension() ?? 'jpg';
                        }

                        // Ensure directory exists
                        $imageDir = public_path('storage/products');
                        if (!file_exists($imageDir)) {
                            mkdir($imageDir, 0755, true);
                        }

                        // Generate unique filename
                        $filename = time() . '-' . uniqid() . '.' . $extension;

                        // Move uploaded file
                        $image->move($imageDir, $filename);

                        // Store relative path
                        $path = 'storage/products/' . $filename;

                        ProductImage::create([
                            'product_id' => $product->id,
                            'path' => $path,
                            'is_primary' => $index === 0,
                            'order' => $index,
                        ]);
                    }
                }
            }

            DB::commit();

            return redirect()->route('admin.products.index')
                ->with('success', 'Produit créé avec succès!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()
                ->with('error', 'Erreur lors de la création du produit: ' . $e->getMessage());
        }
    }

    public function show(Product $product)
    {
        $product->load(['categories', 'tags', 'images', 'reviews.user']);

        return view('admin.products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        $categories = Category::all();
        $tags = Tag::all();
        $product->load(['categories', 'tags', 'images']);

        return view('admin.products.edit', compact('product', 'categories', 'tags'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:100|unique:products,sku,' . $product->id,
            'description' => 'required|string',
            'short_description' => 'nullable|string|max:500',
            'price' => 'required|numeric|min:0',
            'compare_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|string|max:100',
            'status' => 'required|in:active,inactive,draft',
            'is_featured' => 'boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:255',
            'categories' => 'nullable|array',
            'categories.*' => 'exists:categories,id',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        try {
            DB::beginTransaction();

            // Update slug if name changed
            if ($product->name !== $validated['name']) {
                $validated['slug'] = Str::slug($validated['name']);

                // Ensure unique slug
                $originalSlug = $validated['slug'];
                $count = 1;
                while (Product::where('slug', $validated['slug'])->where('id', '!=', $product->id)->exists()) {
                    $validated['slug'] = $originalSlug . '-' . $count;
                    $count++;
                }
            }

            $product->update($validated);

            // Sync categories
            if ($request->has('categories')) {
                $product->categories()->sync($request->categories);
            } else {
                $product->categories()->detach();
            }

            // Sync tags
            if ($request->has('tags')) {
                $product->tags()->sync($request->tags);
            } else {
                $product->tags()->detach();
            }

            // Handle new image uploads
            if ($request->hasFile('images')) {
                $currentImagesCount = $product->images()->count();

                foreach ($request->file('images') as $index => $image) {
                    if ($image->isValid()) {
                        // Get extension
                        $extension = $image->getClientOriginalExtension();
                        if (empty($extension)) {
                            $extension = $image->guessExtension() ?? 'jpg';
                        }

                        // Ensure directory exists
                        $imageDir = public_path('storage/products');
                        if (!file_exists($imageDir)) {
                            mkdir($imageDir, 0755, true);
                        }

                        // Generate unique filename
                        $filename = time() . '-' . uniqid() . '.' . $extension;

                        // Move uploaded file
                        $image->move($imageDir, $filename);

                        // Store relative path
                        $path = 'storage/products/' . $filename;

                        ProductImage::create([
                            'product_id' => $product->id,
                            'path' => $path,
                            'is_primary' => $currentImagesCount === 0 && $index === 0,
                            'order' => $currentImagesCount + $index,
                        ]);
                    }
                }
            }

            DB::commit();

            return redirect()->route('admin.products.edit', $product)
                ->with('success', 'Produit mis à jour avec succès!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()
                ->with('error', 'Erreur lors de la mise à jour du produit: ' . $e->getMessage());
        }
    }

    public function destroy(Product $product)
    {
        try {
            DB::beginTransaction();

            // Delete images from storage
            foreach ($product->images as $image) {
                Storage::disk('public')->delete($image->image_path);
            }

            // Delete product (cascading will handle images, reviews, etc.)
            $product->delete();

            DB::commit();

            return redirect()->route('admin.products.index')
                ->with('success', 'Produit supprimé avec succès!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Erreur lors de la suppression du produit: ' . $e->getMessage());
        }
    }

    public function uploadImages(Request $request, Product $product)
    {
        $request->validate([
            'images' => 'required|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        try {
            $uploaded = [];
            $currentImagesCount = $product->images()->count();

            foreach ($request->file('images') as $index => $image) {
                $path = $image->store('products', 'public');

                $productImage = ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => $path,
                    'is_primary' => $currentImagesCount === 0 && $index === 0,
                    'sort_order' => $currentImagesCount + $index,
                ]);

                $uploaded[] = $productImage;
            }

            return response()->json([
                'success' => true,
                'message' => count($uploaded) . ' image(s) téléchargée(s) avec succès!',
                'images' => $uploaded
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du téléchargement: ' . $e->getMessage()
            ], 500);
        }
    }
}

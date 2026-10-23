<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
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
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('sku', 'like', '%'.$request->search.'%')
                    ->orWhere('full_description', 'like', '%'.$request->search.'%');
            });
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('categories.id', $request->category);
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            match ($request->status) {
                'active' => $query->where('is_active', true)->where('is_archived', false),
                'inactive' => $query->where('is_active', false)->where('is_archived', false),
                'archived' => $query->where('is_archived', true),
                default => null,
            };
        }

        // Sort
        $sortBy = in_array($request->get('sort_by'), ['name', 'sku', 'price', 'stock', 'created_at'], true)
            ? $request->get('sort_by')
            : 'created_at';
        $sortOrder = $request->get('sort_order') === 'asc' ? 'asc' : 'desc';
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
            'discount_price' => 'nullable|numeric|min:0|lt:price',
            'discount_percentage' => 'nullable|integer|min:0|max:100',
            'stock' => 'required|integer|min:0',
            'status' => 'required|in:active,inactive,archived',
            'is_featured' => 'boolean',
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
                $validated['slug'] = $originalSlug.'-'.$count;
                $count++;
            }

            $product = Product::create($this->productData($validated, $request));

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
                        $this->storeImage($image, $product, $index === 0, $index);
                    }
                }
            }

            DB::commit();

            return redirect()->route('admin.products.index')
                ->with('success', 'Produit créé avec succès!');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->withInput()
                ->with('error', 'Erreur lors de la création du produit: '.$e->getMessage());
        }
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
            'sku' => 'required|string|max:100|unique:products,sku,'.$product->id,
            'description' => 'required|string',
            'short_description' => 'nullable|string|max:500',
            'price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0|lt:price',
            'discount_percentage' => 'nullable|integer|min:0|max:100',
            'stock' => 'required|integer|min:0',
            'status' => 'required|in:active,inactive,archived',
            'is_featured' => 'boolean',
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
                    $validated['slug'] = $originalSlug.'-'.$count;
                    $count++;
                }
            }

            $product->update($this->productData($validated, $request));

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
                        $this->storeImage($image, $product, $currentImagesCount === 0 && $index === 0, $currentImagesCount + $index);
                    }
                }
            }

            DB::commit();

            return redirect()->route('admin.products.edit', $product)
                ->with('success', 'Produit mis à jour avec succès!');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->withInput()
                ->with('error', 'Erreur lors de la mise à jour du produit: '.$e->getMessage());
        }
    }

    public function destroy(Product $product)
    {
        try {
            DB::beginTransaction();

            // Delete images from storage
            foreach ($product->images as $image) {
                File::delete(public_path($image->path));
            }

            // Delete product (cascading will handle images, reviews, etc.)
            $product->delete();

            DB::commit();

            return redirect()->route('admin.products.index')
                ->with('success', 'Produit supprimé avec succès!');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'Erreur lors de la suppression du produit: '.$e->getMessage());
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
                $uploaded[] = $this->storeImage($image, $product, $currentImagesCount === 0 && $index === 0, $currentImagesCount + $index);
            }

            return response()->json([
                'success' => true,
                'message' => count($uploaded).' image(s) téléchargée(s) avec succès!',
                'images' => $uploaded,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du téléchargement: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Map the admin form fields onto the real products columns.
     */
    private function productData(array $validated, Request $request): array
    {
        $status = $validated['status'];

        $validated['full_description'] = $validated['description'];
        $validated['is_active'] = $status === 'active';
        $validated['is_archived'] = $status === 'archived';
        $validated['is_featured'] = $request->boolean('is_featured');

        unset($validated['description'], $validated['status'], $validated['categories'], $validated['tags'], $validated['images']);

        return $validated;
    }

    /**
     * Move an uploaded image into public/storage/products and record it.
     */
    private function storeImage(UploadedFile $image, Product $product, bool $isPrimary, int $order): ProductImage
    {
        $extension = $image->getClientOriginalExtension() ?: ($image->guessExtension() ?? 'jpg');
        $imageDir = public_path('storage/products');

        if (! file_exists($imageDir)) {
            mkdir($imageDir, 0755, true);
        }

        $filename = time().'-'.uniqid().'.'.$extension;
        $image->move($imageDir, $filename);

        return ProductImage::create([
            'product_id' => $product->id,
            'path' => 'storage/products/'.$filename,
            'is_primary' => $isPrimary,
            'order' => $order,
        ]);
    }
}

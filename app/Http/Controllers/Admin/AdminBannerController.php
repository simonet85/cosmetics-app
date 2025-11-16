<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminBannerController extends Controller
{
    public function index(Request $request)
    {
        $query = Banner::query();

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by position
        if ($request->filled('position')) {
            $query->where('position', $request->position);
        }

        // Filter by status
        if ($request->filled('status')) {
            $isActive = $request->status === 'active';
            $query->where('is_active', $isActive);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'order');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $banners = $query->paginate(20)->withQueryString();

        return view('admin.banners.index', compact('banners'));
    }

    public function create()
    {
        return view('admin.banners.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:hero,promotional,category,product',
            'position' => 'required|in:home_slider,home_top,home_middle,home_bottom,sidebar',
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'image' => 'required|image|mimes:jpeg,jpg,png,webp|max:2048',
            'link_url' => 'nullable|url|max:255',
            'button_text' => 'nullable|string|max:50',
            'order' => 'required|integer|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'boolean',
        ], [
            'type.required' => 'Le type est requis.',
            'position.required' => 'La position est requise.',
            'title.required' => 'Le titre est requis.',
            'image.required' => 'L\'image est requise.',
            'image.image' => 'Le fichier doit être une image.',
            'image.mimes' => 'L\'image doit être au format JPEG, JPG, PNG ou WEBP.',
            'image.max' => 'L\'image ne doit pas dépasser 2 Mo.',
            'link_url.url' => 'L\'URL doit être valide.',
            'end_date.after_or_equal' => 'La date de fin doit être après ou égale à la date de début.',
        ]);

        // Handle image upload
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $image = $request->file('image');

            // Get extension
            $extension = $image->getClientOriginalExtension();
            if (empty($extension)) {
                $extension = $image->guessExtension() ?? 'jpg';
            }

            // Ensure directory exists
            $imageDir = public_path('storage/images/banners');
            if (!file_exists($imageDir)) {
                mkdir($imageDir, 0755, true);
            }

            // Generate unique filename
            $filename = time() . '-' . uniqid() . '.' . $extension;

            // Move uploaded file
            $image->move($imageDir, $filename);

            // Store relative path
            $validated['image_path'] = 'storage/images/banners/' . $filename;
        }

        $validated['is_active'] = $request->has('is_active');

        Banner::create($validated);

        return redirect()->route('admin.banners.index')->with('success', 'Bannière créée avec succès.');
    }

    public function edit(Banner $banner)
    {
        return view('admin.banners.edit', compact('banner'));
    }

    public function update(Request $request, Banner $banner)
    {
        $validated = $request->validate([
            'type' => 'required|in:hero,promotional,category,product',
            'position' => 'required|in:home_slider,home_top,home_middle,home_bottom,sidebar',
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            'link_url' => 'nullable|url|max:255',
            'button_text' => 'nullable|string|max:50',
            'order' => 'required|integer|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'boolean',
        ], [
            'type.required' => 'Le type est requis.',
            'position.required' => 'La position est requise.',
            'title.required' => 'Le titre est requis.',
            'image.image' => 'Le fichier doit être une image.',
            'image.mimes' => 'L\'image doit être au format JPEG, JPG, PNG ou WEBP.',
            'image.max' => 'L\'image ne doit pas dépasser 2 Mo.',
            'link_url.url' => 'L\'URL doit être valide.',
            'end_date.after_or_equal' => 'La date de fin doit être après ou égale à la date de début.',
        ]);

        // Handle image upload
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            // Delete old image
            if ($banner->image_path) {
                $oldImagePath = public_path($banner->image_path);
                if (file_exists($oldImagePath)) {
                    @unlink($oldImagePath);
                }
            }

            $image = $request->file('image');

            // Get extension
            $extension = $image->getClientOriginalExtension();
            if (empty($extension)) {
                $extension = $image->guessExtension() ?? 'jpg';
            }

            // Ensure directory exists
            $imageDir = public_path('storage/images/banners');
            if (!file_exists($imageDir)) {
                mkdir($imageDir, 0755, true);
            }

            // Generate unique filename
            $filename = time() . '-' . uniqid() . '.' . $extension;

            // Move uploaded file
            $image->move($imageDir, $filename);

            // Store relative path
            $validated['image_path'] = 'storage/images/banners/' . $filename;
        }

        $validated['is_active'] = $request->has('is_active');

        $banner->update($validated);

        return redirect()->route('admin.banners.index')->with('success', 'Bannière mise à jour avec succès.');
    }

    public function destroy(Banner $banner)
    {
        // Delete image
        if ($banner->image_path) {
            $path = str_replace('storage/', '', $banner->image_path);
            Storage::disk('public')->delete($path);
        }

        $banner->delete();

        return redirect()->route('admin.banners.index')->with('success', 'Bannière supprimée avec succès.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Product;

class HomeController extends Controller
{
    public function index()
    {
        $products = Product::active()->get();
        return view('public.home', compact('products'));
    }
}

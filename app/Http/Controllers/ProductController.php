<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    //
    public function index(Product $product){
        return view("pages.product", [
            "product" => $product,
        ]);
    }
}

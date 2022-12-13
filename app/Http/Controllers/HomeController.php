<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    //
    public function index(){
        return view("pages.home", [
            "page_title" => "Home",
            "navbarActive" => true,
            "productList" => Product::paginate(8)->onEachSide(4),
        ]);
    }
}
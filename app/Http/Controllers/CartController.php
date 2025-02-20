<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartDetail;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    /*

    Display Cart Items

    */
    public function index(){
        $cart_id = Cart::current()->first()->id;
        $user_cart = Cart::find($cart_id);
        $cart_details = $user_cart->cartDetails();
        $total_quantity = $cart_details->sum('quantity');

        // Calculate total price
        $total_price = 0;
        foreach($user_cart->cartDetails as $cart_detail){
            $total_price += $cart_detail->product->price * $cart_detail->quantity;
        }

        return view("pages.cart", [
            "total_quantity" => $total_quantity,
            "total_price" => $total_price,
            "cart_details" => $cart_details->get(),
        ]);
    }


    /*

     Add new Item to Cart

    */
    public function add(Request $request){
        $validated = $request->validate([
            "product_id" => 'required',
            "quantity" => 'required|numeric|gt:0',
        ]);

        $current_cart = Cart::current()->first();
        $existing_cart_detail = CartDetail::where("cart_id", $current_cart->id)->where("product_id", $request->product_id);

        // Check if product already in cart
        if($existing_cart_detail->exists()){
            $existing_cart_detail->update([
                "quantity" => $existing_cart_detail->first()->quantity + $request->quantity,
            ]);
        }else{
            CartDetail::create([
                "cart_id" => $current_cart->id,
                "product_id" => $validated["product_id"],
                "quantity" => $validated["quantity"],
            ]);
        }

        return redirect()->back()->with("add-success", "Product successfully added to cart!");
    }


    /*

    Display edit cart page

    */
    public function edit(Product $product){
        $cart_id = Cart::current()->first()->id;
        $user_cart_detail = $product->cartDetail()->where('cart_id', $cart_id)->first();

        return view("pages.product", [
            "product" => $product,
            "user_cart_detail" => $user_cart_detail,
        ]);
    }


    /*

    Update cart item quantity

    */
    public function update(Product $product, Request $request){
        $validated = $request->validate([
            "quantity" => 'required|numeric|gt:0',
        ]);

        $cart_id = Cart::current()->first()->id;
        $product->cartDetail()->where('cart_id', $cart_id)->update([
            "quantity" => $validated["quantity"]
        ]);

        return redirect("/cart");
    }


    /*

    Delete cart item

    */
    public function delete(Product $product){
        $cart_id = Cart::current()->first()->id;
        $product->cartDetail()->where('cart_id', $cart_id)->delete();

        return redirect()->back();
    }


    /*

    Checkout

    */
    public function checkout(){
        $user_id = Auth::user()->id;
        $user_cart = Cart::current()->first();
        $cart_details = $user_cart->cartDetails();

        if($cart_details->exists()){
            // Create new transaction
            $transaction = Transaction::create([
                'user_id' => $user_id,
            ]);

            // Create an array of transaction details
            $transaction_details = [];
            foreach($cart_details->get() as $cart_detail){
                array_push($transaction_details, [
                    'product_id' => $cart_detail->product_id,
                    'quantity' => $cart_detail->quantity,
                ]);
            }

            // Insert all transaction details
            $transaction->transactionDetails()->createMany($transaction_details);

            // delete all cart details
            $cart_details->delete();

            return redirect()->back()->with('message', 'Successfully checkout!');
        }

        return redirect()->back()->with('message', 'There is no product in the cart!');
    }
}

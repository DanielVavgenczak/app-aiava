<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get("/admin/teste", function(Request $request){
    return response()->json(['status' => 'ok']);
});


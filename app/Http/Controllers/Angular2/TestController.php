<?php

namespace App\Http\Controllers\Angular2;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;


class TestController extends Controller
{
  public function angular2_test_content() {

    return view('angular2.test.content');
  }


}

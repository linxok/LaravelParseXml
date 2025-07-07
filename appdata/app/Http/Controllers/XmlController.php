<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class XmlController extends Controller
{

    public function index()
    {
        // xml file path
        $path = "test.xml";

// Read entire file into string
        $xmlfile = file_get_contents($path);



// Convert xml string into an object
        $new = simplexml_load_string($xmlfile);
        $categories = [];

foreach ($new->shop->categories->category as $item) {;
            $categories[]=  json_decode(json_encode($item), true);
        }

dd ($categories);


    }
}

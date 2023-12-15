<?php
require __DIR__.'/../vendor/autoload.php';

use Illuminate\Support\LazyCollection;

$c = LazyCollection::make(function() {
   $number = 1;
   while (true) {
       yield $number;
       $number++;
   }
});


foreach ($c->take(20) as $i) {
    print_r($i . "\n");
}

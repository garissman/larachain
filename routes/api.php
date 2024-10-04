<?php

use Garissman\LaraChain\Http\Controllers\DocumentController;
use Illuminate\Support\Facades\Route;


Route::resource('document', DocumentController::class);

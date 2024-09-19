<?php

use Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;

Route::get('/chat/{chat?}', [ChatController::class, 'index'])
    ->name('guest.chats.index');
Route::post('/chat/{chat}', [ChatController::class, 'chat'])
    ->name('guest.chats.messages');
Route::patch('/chat/{chat}', [ChatController::class, 'updateChat'])
    ->name('guest.update.chat');
Route::delete('/chat/{chat}', [ChatController::class, 'deleteChat'])
    ->name('guest.delete.chat');
Route::get('/new/chat', [ChatController::class, 'newChat'])
    ->name('guest.chats.new');

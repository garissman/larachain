# Your chat bot to support your Laravel Product
----
Chat bot Clerk to support your product made with Laravel.

## Installation
----
`composer require garissman/larachain`

## Publish
----
After installing, you should publish the configuration file using the vendor:publish Artisan command. This
command will publish the configuration file to your application's config directory:

`php artisan larachain:install`

## Lets install Ollama
----
Go to https://ollama.com/ and follow there instructions

ones installed, download the model, at this moment the model that works with function for me is mistral-nemo su run:

`ollama pull mistral-nemo`

## Create the Default Agent
----
This is important to give personality to your char bot

`php artisan larachain:create_default_agent`

## Install Horizon
----
Go to https://laravel.com/docs/11.x/horizon and follow there instructions, after install run:

`php artisan horizon`

## Install Reverb
----
Go to https://laravel.com/docs/11.x/reverb and follow there instructions, after install run:

`php artisan reverb:start --debug`


## Go to the UI and Chat with your bot
----
Now you are ready to chat,

`php artisan serve`

http://localhost:8000/larachain/chat


## TODO 
---
Guess, Tests, nothing new!!! 




# Your chat bot to support your Laravel Product

Chatbot using LLM Models to support your product made with Laravel.

## Installation

`composer require garissman/larachain`

## Publish

After installing, you should publish the configuration file using the vendor:publish Artisan command. This
command will publish the configuration file to your application's config directory:

`php artisan larachain:install`

## Using Ollama(Free)

Go to https://ollama.com/ and follow there instructions

ones installed, download the model, at this moment the model that works with function for me is mistral-nemo su run:

`ollama pull mistral-nemo`

## Using OpenAi(ChatGPT)

Just get your API key in: https://platform.openai.com/api-keys

and set you OPENAI_API_KEY in the .env file

## Create the Default Agent

This is important to give personality to your char bot

`php artisan larachain:create_default_agent`

## Install Horizon

Go to https://laravel.com/docs/11.x/horizon and follow there instructions, after install run:

`php artisan horizon`

## Install Reverb

Go to https://laravel.com/docs/11.x/reverb and follow there instructions, after install run:

`php artisan reverb:start --debug`


## Go to the UI and Chat with your bot

Now you are ready to chat,

`php artisan serve`

http://localhost:8000/larachain/chat

## Create your tools

Now let's do actual coding, tools are the reason why y made this package, 
are custom code triggers by the chain and let the LLM model do the RAG 
base on that function output,

create a Class that extend from Garissman\LaraChain\Structures\Classes\FunctionContract,

like Garissman\LaraChain\Functions\ExampleTool

The Description is the most important part of the function, 
it tells the LLM when to trigger the tool call and start asking por parameter

The properties are the parameter of your too, it tells the LLM what is the parameter, 
also has a description as well, very important.

## Todo make Function command and use RAG for the description
## Todo make Agent command and use RAG for the context


`php artisan serve`

http://localhost:8000/larachain/chat


## TODO 

Guess, Tests, nothing new!!! 




<?php

namespace Garissman\LaraChain\Functions;

use App\Helpers\ChatHelperTrait;
use App\Models\EsimPackage;
use App\Models\Message;
use App\Models\Product;
use App\Models\ProductPrice;
use Garissman\LaraChain\ToolsHelper;
use Illuminate\Support\Facades\Log;
use Prompts\PackageListPrompt;
use Responses\FunctionResponse;

class GetPackageDetailsTool extends FunctionContract
{
    use ChatHelperTrait, ToolsHelper;

    protected string $name = 'get_package_details';

    protected string $description = 'Trigger this intent if user ask for packages';

    public bool $showInUi = true;

    public array $toolTypes = [
        ToolTypes::Chat,
        ToolTypes::ChatCompletion,
        ToolTypes::ManualChoice,
        ToolTypes::Source,
        ToolTypes::Output,
    ];

    //    protected array $promptHistory = [];

    public function handle(
        Message $message): FunctionResponse
    {
        Log::info('[LaraChain] get_package_details Function called');

        $args = $message->args;

        $country = data_get($args, 'country', null);

        $packages = Product::whereHasMorph('package', [EsimPackage::class], function ($q) use ($country) {
            $q->where('validity', '!=', -1)
                ->whereHas('country', function ($q) use ($country) {
                    $q->where('name', $country);
                });
        })
            ->WithPrice()
            ->get()
            ->map(function (Product $product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'prices' => $product->productPrice()
                        ->get()
                        ->map(function (ProductPrice $productPrice) {
                            return [
                                'price' => $productPrice->price,
                                'currency' => $productPrice->currency()->first()->iso_code,
                            ];
                        }),
                ];
            })
            ->toJson();
        $body = 'There is not packages available in this country.';
        if ($packages) {
            $body = PackageListPrompt::prompt($packages);
        }
        $message->body = $body;
        $message->save();

        return FunctionResponse::from([
            'content' => $message->body,
            'prompt' => $message->getContent(),
            'requires_followup' => false,
            'documentChunks' => collect([]),
            'save_to_message' => false,
        ]);
    }

    /**
     * @return PropertyDto[]
     */
    protected function getProperties(): array
    {
        return [
            new PropertyDto(
                name: 'country',
                description: 'Country of the package to show',
                type: 'string',
                required: true,
            ),
        ];
    }
}

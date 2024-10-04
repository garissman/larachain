<?php

namespace Garissman\LaraChain\Http\Controllers;


use App\Http\Controllers\Controller;
use Exception;
use Garissman\LaraChain\Http\Resources\DocumentResource;
use Garissman\LaraChain\Jobs\Document\ProcessFileJob;
use Garissman\LaraChain\Models\Document;
use Garissman\LaraChain\Structures\Enums\TypesEnum;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return DocumentResource::collection(Document::query()
            ->latest('id')
            ->paginate(25)
            ->withQueryString());
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'files' => ['required'],
        ]);

        try {

            foreach ($request->files as $file) {
                /* @var Symfony\Component\HttpFoundation\File\UploadedFile $file */
                $mimetype = $file->getMimeType();
                $mimeType = TypesEnum::mimeTypeToType($mimetype);

                $document = Document::create([
                    'file_path' => $file->getClientOriginalName(),
                    'type' => $mimeType,
                    'document_driver' => config('larachain.driver'),
                    'embedding_driver' => config('larachain.embedding_driver'),
                ]);
                Storage::put($document->id.'/'.$file->getClientOriginalName(), file_get_contents($file));
                $document->file_path = $document->id.'/'.$file->getClientOriginalName();
                $document->save();
                ProcessFileJob::dispatchSync($document);
            }
            return Response::json(['message' => 'Document successfully uploaded'], 200);
        } catch (Exception $e) {
            return Response::json(['message' => 'Error processing Document: ' . $e->getMessage()], 500);
        }
    }

    public function show(Document $document): AnonymousResourceCollection
    {
        return DocumentResource::collection($document);
    }

    public function update(Request $request, Document $document): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'file' => 'required',
        ]);

        $mimetype = $validated['file']->getMimeType();
        $mimeType = TypesEnum::mimeTypeToType($mimetype);
        Storage::delete($document->file_path);
        $document->update([
            'file_path' => $validated['file']->getClientOriginalName(),
            'type' => $mimeType,
        ]);
        $validated['file']->storeAs(
            path: $document->id,
            name: $validated['file']->getClientOriginalName()
        );
        $document->document_chunks()->delete();
        ProcessFileJob::dispatchAfterResponse($document);
        $document->save();
        return DocumentResource::collection($document);
    }

    public function delete(Document $document): ?bool
    {
        return $document->delete();
    }
}

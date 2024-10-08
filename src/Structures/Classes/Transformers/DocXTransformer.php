<?php

namespace Garissman\LaraChain\Structures\Classes\Transformers;

use Exception;
use Garissman\LaraChain\Jobs\Document\VectorlizeDataJob;
use Garissman\LaraChain\Models\Document;
use Garissman\LaraChain\Models\DocumentChunk;
use Garissman\LaraChain\Structures\Classes\TextChunker;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\Element\Image;
use PhpOffice\PhpWord\Element\ListItem;
use PhpOffice\PhpWord\Element\PageBreak;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextBreak;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\Element\Title;
use PhpOffice\PhpWord\IOFactory;

class DocXTransformer
{
    protected Document $document;

    /**
     * @throws Exception
     */
    public static function handle(Document $document): array
    {

        $filePath = $document->file_path;

        $parser = IOFactory::createReader('Word2007');
        $filePath = Storage::path($document->file_path);

        $wordDocument = $parser->load($filePath);

        $sections = $wordDocument->getSections();

        $content = [];
        $chunks = [];

        foreach ($sections as $section) {
            try {
                $elements = $section->getElements();
                foreach ($elements as $element) {
                    if ($element instanceof Text || $element instanceof TextRun) {
                        $content[] = str($element->getText())->trim()->toString();
                    } elseif ($element instanceof TextBreak) {
                        $content[] = "\n";
                    } elseif ($element instanceof Image) {
                        $content[] = '[Image: ' . $element->getSource() . ']';
                    } elseif ($element instanceof Table) {
                        $content[] = self::processTable($element);
                    } elseif ($element instanceof ListItem) {
                        $content[] = '[ListItem: ' . $element->getText() . ']';
                    } elseif ($element instanceof PageBreak) {
                        $content[] = "\n";
                    } elseif ($element instanceof Title) {
                        $content[] = $element->getText();
                    } else {
                        Log::info('Unhandled Element', [
                            'class' => get_class($element),
                        ]);
                    }
                }
            } catch (Exception $e) {
                Log::error('Error parsing Docx', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $content_flattened = collect($content)->map(
            function ($item) {
                if ($item instanceof TextRun) {
                    return str($item->getText())->trim()->toString();
                }

                return $item;
            }
        )->filter(
            function ($item) {
                return $item !== '';
            }
        )->implode('');
        $document->content = $content_flattened;
        $document->original_content = $content_flattened;
        $document->save();
        $chunked_chunks = TextChunker::handle($content_flattened);
        $page = 1;


        foreach ($chunked_chunks as $chunkSection => $chunkContent) {
            try {
                $DocumentChunk = DocumentChunk::updateOrCreate(
                    [
                        'document_id' => $document->id,
                        'sort_order' => $page,
                        'section_number' => $chunkSection,
                    ],
                    [
                        'guid' => md5($chunkContent),
                        'content' => $chunkContent,
                        'meta_data' => [$chunkContent],
                    ]
                );

                $chunks[] = [
                    new VectorlizeDataJob($DocumentChunk),
                ];
            } catch (Exception $e) {
                Log::error('Error processing Docx', [
                    'error' => $e->getMessage(),
                ]);
            }
        }
        return $chunks;
    }

    private static function processTable(Table $table): string
    {
        $tableContent = [];
        $rows = $table->getRows();
        foreach ($rows as $row) {
            $rowData = self::processRow($row);
            if (!empty($rowData)) {
                $tableContent[] = '[Table Row: ' . implode(', ', $rowData) . ']';
            }
        }

        return implode("\n", $tableContent);
    }

    private static function processRow($row): array
    {
        $rowData = [];
        $cells = $row->getCells();
        foreach ($cells as $cell) {
            $cellElements = $cell->getElements();
            foreach ($cellElements as $cellElement) {
                if ($cellElement instanceof Text) {
                    $rowData[] = str($cellElement->getText())->trim()->toString();
                } elseif ($cellElement instanceof TextRun) {
                    $rowData[] = str($cellElement->getText())->trim()->toString();
                } elseif ($cellElement instanceof Table) {
                    $rowData[] = self::processTable($cellElement);
                }
            }
        }

        return $rowData;
    }
}

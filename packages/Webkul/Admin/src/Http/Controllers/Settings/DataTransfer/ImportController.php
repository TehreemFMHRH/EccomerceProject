<?php

namespace Webkul\Admin\Http\Controllers\Settings\DataTransfer;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Webkul\Admin\DataGrids\Settings\DataTransfer\ImportDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Repositories\ImportRepository;

class ImportController extends Controller
{
    
    protected array $supportedFormats = ['csv', 'xls', 'xlsx', 'xml'];

    
    public function __construct(
        protected ImportRepository $importRepository,
        protected Import $importHelper
    ) {}

    
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(ImportDataGrid::class)->process();
        }

        return view('admin::settings.data-transfer.imports.index');
    }

    
    public function create()
    {
        return view('admin::settings.data-transfer.imports.create', [
            'supportedFormats' => $this->supportedFormats,
        ]);
    }

    
    public function store()
    {
        $importers = implode(',', array_keys(config('importers')));

        $supportedFormats = implode(',', $this->supportedFormats);

        $this->validate(request(), [
            'type'                => 'required|in:'.$importers,
            'action'              => 'required:in:append,delete',
            'validation_strategy' => 'required:in:stop-on-errors,skip-errors',
            'allowed_errors'      => 'required|integer|min:0',
            'field_separator'     => 'required',
            'file'                => 'required|extensions:'.$supportedFormats.'|mimes:'.$supportedFormats,
        ]);

        Event::dispatch('data_transfer.imports.create.before');

        $dat = request()->only([
            'type',
            'action',
            'process_in_queue',
            'validation_strategy',
            'validation_strategy',
            'allowed_errors',
            'field_separator',
            'images_directory_path',
        ]);

        if (! isset($dat['process_in_queue'])) {
            $dat['process_in_queue'] = false;
        } else {
            $dat['process_in_queue'] = true;
        }

        $file = request()->file('file');
        $safeFilename = uniqid().'_'.hash('sha256', $file->getClientOriginalName());
        $extension = $file->guessExtension();

        $import = $this->importRepository->create(
            array_merge(
                [
                    'file_path' => request()->file('file')->storeAs(
                        'imports',
                        $safeFilename.'.'.$extension,
                        'private'
                    ),
                ],
                $dat
            )
        );

        Event::dispatch('data_transfer.imports.create.after', $import);

        session()->flash('success', trans('admin::app.settings.data-transfer.imports.create-success'));

        return redirect()->route('admin.settings.data_transfer.imports.import', $import->id);
    }

    
    public function edit(int $i)
    {
        return view('admin::settings.data-transfer.imports.edit', [
            'import'           => $this->importRepository->findOrFail($i),
            'supportedFormats' => $this->supportedFormats,
        ]);
    }

    
    public function update(int $i)
    {
        $importers = implode(',', array_keys(config('importers')));

        $supportedFormats = implode(',', $this->supportedFormats);

        $import = $this->importRepository->findOrFail($i);

        $this->validate(request(), [
            'type'                => 'required|in:'.$importers,
            'action'              => 'required:in:append,delete',
            'validation_strategy' => 'required:in:stop-on-errors,skip-errors',
            'allowed_errors'      => 'required|integer|min:0',
            'field_separator'     => 'required',
            'file'                => 'extensions:'.$supportedFormats.'|mimes:'.$supportedFormats,
        ]);

        Event::dispatch('data_transfer.imports.update.before');

        $dat = array_merge(
            request()->only([
                'type',
                'action',
                'process_in_queue',
                'validation_strategy',
                'validation_strategy',
                'allowed_errors',
                'field_separator',
                'images_directory_path',
            ]),
            [
                'state'                => 'pending',
                'processed_rows_count' => 0,
                'invalid_rows_count'   => 0,
                'errors_count'         => 0,
                'errors'               => null,
                'error_file_path'      => null,
                'started_at'           => null,
                'completed_at'         => null,
                'summary'              => null,
            ]
        );

        Storage::disk('private')->delete($import->error_file_path ?? '');

        $file = request()->file('file');

        if (
            $file
            && $file->isValid()
        ) {
            $safeFilename = uniqid().'_'.hash('sha256', $file->getClientOriginalName());
            $extension = $file->guessExtension();

            Storage::disk('private')->delete($import->file_path);

            $dat['file_path'] = $file->storeAs(
                'imports',
                $safeFilename.'.'.$extension,
                'private'
            );
        }

        if (! isset($dat['process_in_queue'])) {
            $dat['process_in_queue'] = false;
        }

        $import = $this->importRepository->update($dat, $import->id);

        Event::dispatch('data_transfer.imports.update.after', $import);

        session()->flash('success', trans('admin::app.settings.data-transfer.imports.update-success'));

        return redirect()->route('admin.settings.data_transfer.imports.import', $import->id);
    }

    
    public function destroy($i)
    {
        $import = $this->importRepository->findOrFail($i);

        try {
            Storage::disk('private')->delete($import->file_path);

            Storage::disk('private')->delete($import->error_file_path ?? '');

            $this->importRepository->delete($i);

            return new JsonResponse([
                'message' => trans('admin::app.settings.data-transfer.imports.delete-success'),
            ]);
        } catch (\Exception $e) {
        }

        return response()->json([
            'message' => trans('admin::app.settings.data-transfer.imports.delete-failed'),
        ], 500);
    }

    
    public function import(int $i)
    {
        $import = $this->importRepository->findOrFail($i);

        $isValid = $this->importHelper
            ->setImport($import)
            ->isValid();

        if ($import->state == Import::STATE_LINKING) {
            if ($this->importHelper->isIndexingRequired()) {
                $state = Import::STATE_INDEXING;
            } else {
                $state = Import::STATE_COMPLETED;
            }
        } elseif ($import->state == Import::STATE_INDEXING) {
            $state = Import::STATE_COMPLETED;
        } else {
            $state = Import::STATE_COMPLETED;
        }

        $stats = $this->importHelper->stats($state);

        $import->unsetRelations();

        return view('admin::settings.data-transfer.imports.import', compact('import', 'isValid', 'stats'));
    }

    
    public function validateImport(int $i): JsonResponse
    {
        $import = $this->importRepository->findOrFail($i);

        $isValid = $this->importHelper
            ->setImport($import)
            ->validate();

        return new JsonResponse([
            'is_valid' => $isValid,
            'import'   => $this->importHelper->getImport()->unsetRelations(),
        ]);
    }

    
    public function start(int $i): JsonResponse
    {
        $import = $this->importRepository->findOrFail($i);

        if (! $import->processed_rows_count) {
            return new JsonResponse([
                'message' => trans('admin::app.settings.data-transfer.imports.nothing-to-import'),
            ], 400);
        }

        $this->importHelper->setImport($import);

        if (! $this->importHelper->isValid()) {
            return new JsonResponse([
                'message' => trans('admin::app.settings.data-transfer.imports.not-valid'),
            ], 400);
        }

        if (
            $import->process_in_queue
            && config('queue.default') == 'sync'
        ) {
            return new JsonResponse([
                'message' => trans('admin::app.settings.data-transfer.imports.setup-queue-error'),
            ], 400);
        }

        
        if ($import->state == Import::STATE_VALIDATED) {
            $this->importHelper->started();
        }

        
        $importBatch = $import->batches->where('state', Import::STATE_PENDING)->first();

        if ($importBatch) {
            
            try {
                if ($import->process_in_queue) {
                    $this->importHelper->start();
                } else {
                    $this->importHelper->start($importBatch);
                }
            } catch (\Exception $e) {
                return new JsonResponse([
                    'message' => $e->getMessage(),
                ], 400);
            }
        } else {
            if ($this->importHelper->isLinkingRequired()) {
                $this->importHelper->linking();
            } elseif ($this->importHelper->isIndexingRequired()) {
                $this->importHelper->indexing();
            } else {
                $this->importHelper->completed();
            }
        }

        return new JsonResponse([
            'stats'  => $this->importHelper->stats(Import::STATE_PROCESSED),
            'import' => $this->importHelper->getImport()->unsetRelations(),
        ]);
    }

    
    public function link(int $i): JsonResponse
    {
        $import = $this->importRepository->findOrFail($i);

        if (! $import->processed_rows_count) {
            return new JsonResponse([
                'message' => trans('admin::app.settings.data-transfer.imports.nothing-to-import'),
            ], 400);
        }

        $this->importHelper->setImport($import);

        if (! $this->importHelper->isValid()) {
            return new JsonResponse([
                'message' => trans('admin::app.settings.data-transfer.imports.not-valid'),
            ], 400);
        }

        
        if ($import->state == Import::STATE_PROCESSED) {
            $this->importHelper->linking();
        }

        
        $importBatch = $import->batches->where('state', Import::STATE_PROCESSED)->first();

        
        if ($importBatch) {
            
            try {
                $this->importHelper->link($importBatch);
            } catch (\Exception $e) {
                return new JsonResponse([
                    'message' => $e->getMessage(),
                ], 400);
            }
        } else {
            if ($this->importHelper->isIndexingRequired()) {
                $this->importHelper->indexing();
            } else {
                $this->importHelper->completed();
            }
        }

        return new JsonResponse([
            'stats'  => $this->importHelper->stats(Import::STATE_LINKED),
            'import' => $this->importHelper->getImport()->unsetRelations(),
        ]);
    }

    
    public function indexData(int $i): JsonResponse
    {
        $import = $this->importRepository->findOrFail($i);

        if (! $import->processed_rows_count) {
            return new JsonResponse([
                'message' => trans('admin::app.settings.data-transfer.imports.nothing-to-import'),
            ], 400);
        }

        $this->importHelper->setImport($import);

        if (! $this->importHelper->isValid()) {
            return new JsonResponse([
                'message' => trans('admin::app.settings.data-transfer.imports.not-valid'),
            ], 400);
        }

        
        if ($import->state == Import::STATE_LINKED) {
            $this->importHelper->indexing();
        }

        
        $importBatch = $import->batches->where('state', Import::STATE_LINKED)->first();

        
        if ($importBatch) {
            
            try {
                $this->importHelper->index($importBatch);
            } catch (\Exception $e) {
                return new JsonResponse([
                    'message' => $e->getMessage(),
                ], 400);
            }
        } else {
            
            $this->importHelper->completed();
        }

        return new JsonResponse([
            'stats'  => $this->importHelper->stats(Import::STATE_INDEXED),
            'import' => $this->importHelper->getImport()->unsetRelations(),
        ]);
    }

    
    public function stats(int $i, string $state = Import::STATE_PROCESSED): JsonResponse
    {
        $import = $this->importRepository->findOrFail($i);

        $stats = $this->importHelper
            ->setImport($import)
            ->stats($state);

        return new JsonResponse([
            'stats'  => $stats,
            'import' => $this->importHelper->getImport()->unsetRelations(),
        ]);
    }

    
    public function downloadSample(string $type, string $format)
    {
        $samplePath = config("importers.{$type}.sample_paths.{$format}");

        return Storage::download($samplePath);
    }

    
    public function download(int $i)
    {
        $import = $this->importRepository->findOrFail($i);

        return Storage::disk('private')->download($import->file_path);
    }

    
    public function downloadErrorReport(int $i)
    {
        $import = $this->importRepository->findOrFail($i);

        if (! $import->error_file_path) {
            abort(404);
        }

        return Storage::disk('private')->download($import->error_file_path);
    }
}

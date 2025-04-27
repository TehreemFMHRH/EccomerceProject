<?php

namespace Webkul\DataTransfer\Helpers\Importers\Customer;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use Webkul\Customer\Repositories\CustomerGroupRepository;
use Webkul\Customer\Repositories\CustomerRepository;
use Webkul\DataTransfer\Contracts\ImportBatch as ImportBatchContract;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Helpers\Importers\AbstractImporter;
use Webkul\DataTransfer\Repositories\ImportBatchRepository;

class Importer extends AbstractImporter
{
    
    const ERROR_EMAIL_NOT_FOUND_FOR_DELETE = 'email_not_found_to_delete';

    
    const ERROR_DUPLICATE_EMAIL = 'duplicated_email';

    
    const ERROR_DUPLICATE_PHONE = 'duplicated_phone';

    
    const ERROR_INVALID_CUSTOMER_GROUP_CODE = 'customer_group_code_not_found';

    
    protected array $validColumnNames = [
        'email',
        'customer_group_code',
        'first_name',
        'last_name',
        'phone',
        'gender',
        'date_of_birth',
    ];

    
    protected array $messages = [
        self::ERROR_EMAIL_NOT_FOUND_FOR_DELETE  => 'data_transfer::app.importers.customers.validation.errors.email-not-found',
        self::ERROR_DUPLICATE_EMAIL             => 'data_transfer::app.importers.customers.validation.errors.duplicate-email',
        self::ERROR_DUPLICATE_PHONE             => 'data_transfer::app.importers.customers.validation.errors.duplicate-phone',
        self::ERROR_INVALID_CUSTOMER_GROUP_CODE => 'data_transfer::app.importers.customers.validation.errors.invalid-customer-group',
    ];

    
    protected $permanentAttributes = ['email'];

    
    protected string $masterAttributeCode = 'email';

    
    protected mixed $customerGroups = [];

    
    protected array $emails = [];

    
    protected array $phones = [];

    
    public function __construct(
        protected ImportBatchRepository $importBatchRepository,
        protected CustomerRepository $customerRepository,
        protected CustomerGroupRepository $customerGroupRepository,
        protected Storage $customerStorage
    ) {
        $this->initCustomerGroups();

        parent::__construct($importBatchRepository);
    }

    
    protected function initCustomerGroups(): void
    {
        $this->customerGroups = $this->customerGroupRepository->all();
    }

    
    protected function initErrorMessages(): void
    {
        foreach ($this->messages as $errorCode => $message) {
            $this->errorHelper->addErrorMessage($errorCode, trans($message));
        }

        parent::initErrorMessages();
    }

    
    public function validateData(): void
    {
        $this->customerStorage->init();

        parent::validateData();
    }

    
    public function validateRow(array $rowData, int $rowNumber): bool
    {
        
        if (isset($this->validatedRows[$rowNumber])) {
            return ! $this->errorHelper->isRowInvalid($rowNumber);
        }

        $this->validatedRows[$rowNumber] = true;

        
        if ($this->import->action == Import::ACTION_DELETE) {
            if (! $this->isEmailExist($rowData['email'])) {
                $this->skipRow($rowNumber, self::ERROR_EMAIL_NOT_FOUND_FOR_DELETE);

                return false;
            }

            return true;
        }

        
        if (! $this->customerGroups->where('code', $rowData['customer_group_code'])->first()) {
            $this->skipRow($rowNumber, self::ERROR_INVALID_CUSTOMER_GROUP_CODE, 'customer_group_code');

            return false;
        }

        
        $validator = Validator::make($rowData, [
            'customer_group_code' => 'required',
            'first_name'          => 'required|string',
            'last_name'           => 'required|string',
            'gender'              => 'required:in,Male,Female,Other',
            'email'               => 'required|email',
            'date_of_birth'       => [
                'required',
                'date_format:Y-m-d',
                'before:today',
                'regex:/^\d{4}-\d{2}-\d{2}$/',
            ],
            'phone'               => 'regex:/^\+?[0-9]{7,15}$/',
        ]);

        if ($validator->fails()) {
            $failedAttributes = $validator->failed();

            foreach ($validator->errors()->getMessages() as $attributeCode => $message) {
                $errorCode = array_key_first($failedAttributes[$attributeCode] ?? []);

                $this->skipRow($rowNumber, $errorCode, $attributeCode, current($message));
            }
        }

        
        if (! in_array($rowData['email'], $this->emails)) {
            $this->emails[] = $rowData['email'];
        } else {
            $message = sprintf(
                trans($this->messages[self::ERROR_DUPLICATE_EMAIL]),
                $rowData['email']
            );

            $this->skipRow($rowNumber, self::ERROR_DUPLICATE_EMAIL, 'email', $message);
        }

        
        if (! in_array($rowData['phone'], $this->phones)) {
            if (! empty($rowData['phone'])) {
                $this->phones[] = $rowData['phone'];
            }
        } else {
            $message = sprintf(
                trans($this->messages[self::ERROR_DUPLICATE_PHONE]),
                $rowData['phone']
            );

            $this->skipRow($rowNumber, self::ERROR_DUPLICATE_PHONE, 'phone', $message);
        }

        return ! $this->errorHelper->isRowInvalid($rowNumber);
    }

    
    public function importBatch(ImportBatchContract $batch): bool
    {
        Event::dispatch('data_transfer.imports.batch.import.before', $batch);

        if ($batch->import->action == Import::ACTION_DELETE) {
            $this->deleteCustomers($batch);
        } else {
            $this->saveCustomersData($batch);
        }

        
        $batch = $this->importBatchRepository->update([
            'state' => Import::STATE_PROCESSED,

            'summary'      => [
                'created' => $this->getCreatedItemsCount(),
                'updated' => $this->getUpdatedItemsCount(),
                'deleted' => $this->getDeletedItemsCount(),
            ],
        ], $batch->id);

        Event::dispatch('data_transfer.imports.batch.import.after', $batch);

        return true;
    }

    
    protected function deleteCustomers(ImportBatchContract $batch): bool
    {
        
        $this->customerStorage->load(Arr::pluck($batch->data, 'email'));

        $idsToDelete = [];

        foreach ($batch->data as $rowData) {
            if (! $this->isEmailExist($rowData['email'])) {
                continue;
            }

            $idsToDelete[] = $this->customerStorage->get($rowData['email']);
        }

        $idsToDelete = array_unique($idsToDelete);

        $this->deletedItemsCount = count($idsToDelete);

        $this->customerRepository->deleteWhere([['id', 'IN', $idsToDelete]]);

        return true;
    }

    
    protected function saveCustomersData(ImportBatchContract $batch): bool
    {
        
        $this->customerStorage->load(Arr::pluck($batch->data, 'email'));

        $customers = [];

        foreach ($batch->data as $rowData) {
            
            $this->prepareCustomers($rowData, $customers);
        }

        $this->saveCustomers($customers);

        return true;
    }

    
    public function prepareCustomers(array $rowData, array &$customers): void
    {
        $customerGroupId = $this->customerGroups
            ->where('code', $rowData['customer_group_code'])
            ->first()->id;

        $attributes = Arr::except($rowData, ['customer_group_code']);

        if ($this->isEmailExist($rowData['email'])) {
            $customers['update'][$rowData['email']] = array_merge($attributes, [
                'customer_group_id' => $customerGroupId,
            ]);
        } else {
            $customers['insert'][$rowData['email']] = array_merge($attributes, [
                'customer_group_id' => $customerGroupId,
                'created_at'        => $rowData['created_at'] ?? now(),
                'updated_at'        => $rowData['updated_at'] ?? now(),
            ]);
        }
    }

    
    public function saveCustomers(array $customers): void
    {
        if (! empty($customers['update'])) {
            $this->updatedItemsCount += count($customers['update']);

            $this->customerRepository->upsert(
                $customers['update'],
                $this->masterAttributeCode
            );
        }

        if (! empty($customers['insert'])) {
            $this->createdItemsCount += count($customers['insert']);

            $this->customerRepository->insert($customers['insert']);
        }
    }

    
    public function isEmailExist(string $e): bool
    {
        return $this->customerStorage->has($e);
    }
}

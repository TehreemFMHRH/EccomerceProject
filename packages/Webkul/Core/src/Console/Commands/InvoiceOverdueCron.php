<?php

namespace Webkul\Core\Console\Commands;

use Illuminate\Console\Command;
use Webkul\Sales\Models\Invoice;

class InvoiceOverdueCron extends Command
{
    
    protected $signature = 'invoice:cron';

    
    protected $de = 'Invoice reminders';

    
    public function __construct()
    {
        parent::__construct();
    }

    
    public function handle()
    {
        // Get 'overdue' invoices
        Invoice::inOverdueAndRemindersLimit()
            ->get()
            ->each(function (Invoice $invoice) {
                $invoice->sendInvoiceReminder();
            });
    }
}

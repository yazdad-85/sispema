<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Events\AcademicYearCreated;
use App\Listeners\RecordPreviousYearDebts;
use App\Models\Payment;
use App\Models\BillingRecord;
use App\Observers\PaymentObserver;
use App\Observers\BillingRecordObserver;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        AcademicYearCreated::class => [
            RecordPreviousYearDebts::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        // Register Payment Observer for automatic excess payment handling
        Payment::observe(PaymentObserver::class);
        
        // Register BillingRecord Observer for automatic excess payment application
        BillingRecord::observe(BillingRecordObserver::class);
    }
}

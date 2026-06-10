<?php

declare(strict_types=1);

namespace App\Providers;

use App\Policies\Companies\CompanyPolicy;
use App\Policies\Companies\ProlaboreConfigPolicy;
use App\Policies\Companies\ProlaboreRecordPolicy;
use App\Policies\Invoicing\ClientPolicy;
use App\Policies\Invoicing\InvoicePolicy;
use App\Policies\Invoicing\SimulationBatchPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Src\Companies\Domain\Repositories\CompanyRepository;
use Src\Companies\Domain\Repositories\ProlaboreConfigRepository;
use Src\Companies\Domain\Repositories\ProlaboreRecordRepository;
use Src\Companies\Infrastructure\Persistence\CompanyModel;
use Src\Companies\Infrastructure\Persistence\EloquentCompanyRepository;
use Src\Companies\Infrastructure\Persistence\EloquentProlaboreConfigRepository;
use Src\Companies\Infrastructure\Persistence\EloquentProlaboreRecordRepository;
use Src\Companies\Infrastructure\Persistence\ProlaboreConfigModel;
use Src\Companies\Infrastructure\Persistence\ProlaboreRecordModel;
use Src\Invoicing\Application\TransactionManager;
use Src\Invoicing\Domain\Repositories\ClientRepository;
use Src\Invoicing\Domain\Repositories\InvoiceRepository;
use Src\Invoicing\Domain\Repositories\SimulationBatchRepository;
use Src\Invoicing\Infrastructure\DbTransactionManager;
use Src\Invoicing\Infrastructure\Persistence\ClientModel;
use Src\Invoicing\Infrastructure\Persistence\EloquentClientRepository;
use Src\Invoicing\Infrastructure\Persistence\EloquentInvoiceRepository;
use Src\Invoicing\Infrastructure\Persistence\EloquentSimulationBatchRepository;
use Src\Invoicing\Infrastructure\Persistence\InvoiceModel;
use Src\Invoicing\Infrastructure\Persistence\SimulationBatchModel;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CompanyRepository::class, EloquentCompanyRepository::class);
        $this->app->bind(ProlaboreConfigRepository::class, EloquentProlaboreConfigRepository::class);
        $this->app->bind(ProlaboreRecordRepository::class, EloquentProlaboreRecordRepository::class);

        $this->app->bind(TransactionManager::class, DbTransactionManager::class);

        $this->app->bind(ClientRepository::class, EloquentClientRepository::class);
        $this->app->bind(InvoiceRepository::class, EloquentInvoiceRepository::class);
        $this->app->bind(SimulationBatchRepository::class, EloquentSimulationBatchRepository::class);
    }

    public function boot(): void
    {
        Gate::policy(CompanyModel::class, CompanyPolicy::class);
        Gate::policy(ProlaboreConfigModel::class, ProlaboreConfigPolicy::class);
        Gate::policy(ProlaboreRecordModel::class, ProlaboreRecordPolicy::class);

        Gate::policy(ClientModel::class, ClientPolicy::class);
        Gate::policy(InvoiceModel::class, InvoicePolicy::class);
        Gate::policy(SimulationBatchModel::class, SimulationBatchPolicy::class);

        $this->configureDefaults();
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(app()->isProduction());

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}

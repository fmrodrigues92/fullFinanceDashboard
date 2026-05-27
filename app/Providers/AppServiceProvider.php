<?php

declare(strict_types=1);

namespace App\Providers;

use App\Policies\Companies\CompanyPolicy;
use App\Policies\Companies\ProlaboreConfigPolicy;
use App\Policies\Companies\ProlaboreRecordPolicy;
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

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            CompanyRepository::class,
            EloquentCompanyRepository::class,
        );
        $this->app->bind(
            ProlaboreConfigRepository::class,
            EloquentProlaboreConfigRepository::class,
        );
        $this->app->bind(
            ProlaboreRecordRepository::class,
            EloquentProlaboreRecordRepository::class,
        );
    }

    public function boot(): void
    {
        Gate::policy(CompanyModel::class, CompanyPolicy::class);
        Gate::policy(ProlaboreConfigModel::class, ProlaboreConfigPolicy::class);
        Gate::policy(ProlaboreRecordModel::class, ProlaboreRecordPolicy::class);

        $this->configureDefaults();
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

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

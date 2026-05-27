<?php

declare(strict_types=1);

use Mockery\MockInterface;
use Src\Companies\Application\DTOs\CreateProlaboreConfigInput;
use Src\Companies\Application\UseCases\CreateProlaboreConfigUseCase;
use Src\Companies\Domain\CompanyPartner;
use Src\Companies\Domain\Exceptions\PartnerNotBelongsToCompany;
use Src\Companies\Domain\ProlaboreConfig;
use Src\Companies\Domain\Repositories\CompanyRepository;
use Src\Companies\Domain\Repositories\ProlaboreConfigRepository;
use Src\Companies\Domain\ValueObjects\Cpf;
use Src\Companies\Domain\ValueObjects\ParticipacaoPercentual;

// ─── Setup ──────────────────────────────────────────────────────────────────

beforeEach(function () {
    /** @var MockInterface&CompanyRepository */
    $this->companyRepo = Mockery::mock(CompanyRepository::class);
    /** @var MockInterface&ProlaboreConfigRepository */
    $this->configRepo = Mockery::mock(ProlaboreConfigRepository::class);

    $this->useCase = new CreateProlaboreConfigUseCase($this->configRepo, $this->companyRepo);

    $this->input = new CreateProlaboreConfigInput(
        companyId: 1,
        partnerId: 2,
        userId: 3,
        valor: 3000.0,
    );
});

afterEach(fn () => Mockery::close());

// ─── Sócio pertence à empresa (RN10) ────────────────────────────────────────

it('salva a config quando o sócio pertence à empresa', function () {
    $partner = CompanyPartner::fromPersistence(
        id: 2,
        companyId: 1,
        userId: 3,
        nome: 'João Silva',
        cpf: new Cpf('52998224725'),
        participacao: new ParticipacaoPercentual(100.0),
    );

    $saved = ProlaboreConfig::create(
        companyId: 1,
        partnerId: 2,
        userId: 3,
        valor: 3000.0,
    );

    $this->companyRepo->shouldReceive('findPartner')->with(2, 1)->andReturn($partner);
    $this->configRepo->shouldReceive('save')->once()->andReturn($saved);

    $result = ($this->useCase)($this->input);

    expect($result->valor)->toBe(3000.0)
        ->and($result->partnerId)->toBe(2)
        ->and($result->companyId)->toBe(1);
});

// ─── Sócio não pertence à empresa (RN10) ────────────────────────────────────

it('lança PartnerNotBelongsToCompany quando sócio não pertence à empresa', function () {
    $this->companyRepo->shouldReceive('findPartner')->with(2, 1)->andReturn(null);
    $this->configRepo->shouldNotReceive('save');

    ($this->useCase)($this->input);
})->throws(PartnerNotBelongsToCompany::class);

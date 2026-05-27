<?php

declare(strict_types=1);

use Mockery\MockInterface;
use Src\Companies\Application\DTOs\CreateProlaboreRecordInput;
use Src\Companies\Application\UseCases\CreateProlaboreRecordUseCase;
use Src\Companies\Domain\CompanyPartner;
use Src\Companies\Domain\Exceptions\PartnerNotBelongsToCompany;
use Src\Companies\Domain\ProlaboreRecord;
use Src\Companies\Domain\Repositories\CompanyRepository;
use Src\Companies\Domain\Repositories\ProlaboreRecordRepository;
use Src\Companies\Domain\ValueObjects\Cpf;
use Src\Companies\Domain\ValueObjects\ParticipacaoPercentual;

// ─── Setup ──────────────────────────────────────────────────────────────────

beforeEach(function () {
    /** @var MockInterface&CompanyRepository */
    $this->companyRepo = Mockery::mock(CompanyRepository::class);
    /** @var MockInterface&ProlaboreRecordRepository */
    $this->recordRepo = Mockery::mock(ProlaboreRecordRepository::class);

    $this->useCase = new CreateProlaboreRecordUseCase($this->recordRepo, $this->companyRepo);

    $this->competencia = new DateTimeImmutable('2026-05-01');

    $this->input = new CreateProlaboreRecordInput(
        companyId: 1,
        partnerId: 2,
        userId: 3,
        competencia: $this->competencia,
        valor: 3000.0,
        observacao: 'Maio 2026',
    );
});

afterEach(fn () => Mockery::close());

// ─── Sócio pertence à empresa ────────────────────────────────────────────────

it('salva o recibo quando o sócio pertence à empresa', function () {
    $partner = CompanyPartner::fromPersistence(
        id: 2,
        companyId: 1,
        userId: 3,
        nome: 'João Silva',
        cpf: new Cpf('52998224725'),
        participacao: new ParticipacaoPercentual(100.0),
    );

    $saved = ProlaboreRecord::create(
        companyId: 1,
        partnerId: 2,
        userId: 3,
        competencia: $this->competencia,
        valor: 3000.0,
        observacao: 'Maio 2026',
    );

    $this->companyRepo->shouldReceive('findPartner')->with(2, 1)->andReturn($partner);
    $this->recordRepo->shouldReceive('save')->once()->andReturn($saved);

    $result = ($this->useCase)($this->input);

    expect($result->valor)->toBe(3000.0)
        ->and($result->competencia->format('Y-m-d'))->toBe('2026-05-01')
        ->and($result->observacao)->toBe('Maio 2026');
});

// ─── Sócio não pertence à empresa ───────────────────────────────────────────

it('lança PartnerNotBelongsToCompany quando sócio não pertence à empresa', function () {
    $this->companyRepo->shouldReceive('findPartner')->with(2, 1)->andReturn(null);
    $this->recordRepo->shouldNotReceive('save');

    ($this->useCase)($this->input);
})->throws(PartnerNotBelongsToCompany::class);

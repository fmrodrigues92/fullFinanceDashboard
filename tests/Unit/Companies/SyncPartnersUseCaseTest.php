<?php

declare(strict_types=1);

use Mockery\MockInterface;
use Src\Companies\Application\DTOs\PartnerData;
use Src\Companies\Application\DTOs\SyncPartnersInput;
use Src\Companies\Application\UseCases\SyncPartnersUseCase;
use Src\Companies\Domain\Exceptions\DuplicateCpfInPartnerList;
use Src\Companies\Domain\Exceptions\InvalidCpf;
use Src\Companies\Domain\Exceptions\ParticipacaoSumNotHundred;
use Src\Companies\Domain\Repositories\CompanyRepository;

// ─── Helpers ────────────────────────────────────────────────────────────────

function partner(string $cpf, float $participacao, string $nome = 'Sócio'): PartnerData
{
    return new PartnerData(nome: $nome, cpf: $cpf, participacao: $participacao);
}

function syncInput(array $partners): SyncPartnersInput
{
    return new SyncPartnersInput(companyId: 1, userId: 1, partners: $partners);
}

// ─── Setup ──────────────────────────────────────────────────────────────────

beforeEach(function () {
    /** @var MockInterface&CompanyRepository */
    $this->repo = Mockery::mock(CompanyRepository::class);
    $this->useCase = new SyncPartnersUseCase($this->repo);
});

afterEach(fn () => Mockery::close());

// ─── Lista vazia (RN5) ───────────────────────────────────────────────────────

it('aceita lista vazia sem validar soma e chama syncPartners com array vazio', function () {
    $this->repo->shouldReceive('syncPartners')->once()->with(1, []);

    ($this->useCase)(syncInput([]));
});

// ─── Casos válidos ───────────────────────────────────────────────────────────

it('aceita sócio único com 100%', function () {
    $this->repo->shouldReceive('syncPartners')->once();

    ($this->useCase)(syncInput([partner('52998224725', 100.0)]));
});

it('aceita dois sócios com participações somando exatamente 100%', function () {
    $this->repo->shouldReceive('syncPartners')->once();

    ($this->useCase)(syncInput([
        partner('52998224725', 60.0),
        partner('11144477735', 40.0),
    ]));
});

// ─── Soma ≠ 100% (RN4) ───────────────────────────────────────────────────────

it('lança ParticipacaoSumNotHundred quando soma é diferente de 100%', function () {
    $this->repo->shouldNotReceive('syncPartners');

    ($this->useCase)(syncInput([
        partner('52998224725', 60.0),
        partner('11144477735', 30.0),
    ]));
})->throws(ParticipacaoSumNotHundred::class);

it('a mensagem de ParticipacaoSumNotHundred contém a soma atual', function () {
    try {
        ($this->useCase)(syncInput([
            partner('52998224725', 60.0),
            partner('11144477735', 30.0),
        ]));
    } catch (ParticipacaoSumNotHundred $e) {
        expect($e->getMessage())->toContain('90.00');

        return;
    }

    throw new RuntimeException('Exceção esperada não foi lançada.');
});

// ─── CPF duplicado na lista (RN8) ────────────────────────────────────────────

it('lança DuplicateCpfInPartnerList para CPF repetido antes de checar soma', function () {
    $this->repo->shouldNotReceive('syncPartners');

    ($this->useCase)(syncInput([
        partner('52998224725', 50.0, 'João'),
        partner('52998224725', 50.0, 'João Clone'),
    ]));
})->throws(DuplicateCpfInPartnerList::class);

// ─── CPF inválido (RN3) ──────────────────────────────────────────────────────

it('lança InvalidCpf antes de chegar na validação de soma quando CPF é inválido', function () {
    $this->repo->shouldNotReceive('syncPartners');

    ($this->useCase)(syncInput([partner('00000000000', 100.0)]));
})->throws(InvalidCpf::class);

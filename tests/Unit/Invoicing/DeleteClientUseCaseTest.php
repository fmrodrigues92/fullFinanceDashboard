<?php

declare(strict_types=1);

use Mockery\MockInterface;
use Src\Invoicing\Application\TransactionManager;
use Src\Invoicing\Application\UseCases\DeleteClientUseCase;
use Src\Invoicing\Domain\Client;
use Src\Invoicing\Domain\Repositories\ClientRepository;

// ─── Setup ───────────────────────────────────────────────────────────────────

beforeEach(function () {
    /** @var MockInterface&ClientRepository */
    $this->repo = Mockery::mock(ClientRepository::class);
    /** @var MockInterface&TransactionManager */
    $this->txManager = Mockery::mock(TransactionManager::class);
    $this->txManager->shouldReceive('run')->andReturnUsing(fn (callable $cb) => $cb());

    $this->useCase = new DeleteClientUseCase($this->repo, $this->txManager);

    $this->client = Client::fromPersistence(id: 5, companyId: 1, nome: 'Acme', extId: null);
});

afterEach(fn () => Mockery::close());

// ─── Cliente encontrado ──────────────────────────────────────────────────────

it('soft-deleta o cliente e nulifica client_id nas notas vinculadas', function () {
    $this->repo->shouldReceive('findForCompany')->with(5, 1)->andReturn($this->client);
    $this->repo->shouldReceive('softDelete')->with($this->client)->once();
    $this->repo->shouldReceive('nullifyClientOnInvoices')->with(5)->once();

    ($this->useCase)(clientId: 5, companyId: 1);
});

it('nullifyClientOnInvoices é chamado após softDelete', function () {
    $order = [];

    $this->repo->shouldReceive('findForCompany')->andReturn($this->client);
    $this->repo->shouldReceive('softDelete')->once()->andReturnUsing(function () use (&$order) {
        $order[] = 'softDelete';
    });
    $this->repo->shouldReceive('nullifyClientOnInvoices')->once()->andReturnUsing(function () use (&$order) {
        $order[] = 'nullify';
    });

    ($this->useCase)(clientId: 5, companyId: 1);

    expect($order)->toBe(['softDelete', 'nullify']);
});

// ─── Cliente não encontrado ──────────────────────────────────────────────────

it('não faz nada quando o cliente não pertence à empresa', function () {
    $this->repo->shouldReceive('findForCompany')->with(99, 1)->andReturn(null);
    $this->repo->shouldNotReceive('softDelete');
    $this->repo->shouldNotReceive('nullifyClientOnInvoices');

    ($this->useCase)(clientId: 99, companyId: 1);
});

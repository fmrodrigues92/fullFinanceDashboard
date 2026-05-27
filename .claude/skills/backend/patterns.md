# Padrões de código — Backend

Referência canônica de **forma**. Contexto de exemplo: `Transactions`. Copie estrutura, camadas, nomes,
tipos, named constructors, DIP e o isolamento por `user_id` no repositório — **não** o domínio.

Regra de dependência: Infrastructure → Application → Domain. O Domain não importa nada de Laravel.

```
app/src/Transactions/
  Domain/
    Transaction.php                          (Aggregate — coração do contexto, na raiz)
    ValueObjects/
      Money.php
    Repositories/
      TransactionRepository.php              (interface)
    Exceptions/
      InvalidTransactionAmount.php
  Application/
    UseCases/
      RecordTransactionUseCase.php           (uma intenção por classe, sufixo UseCase)
    DTOs/
      RecordTransactionInput.php
  Infrastructure/
    Persistence/
      TransactionModel.php                   (Eloquent)
      EloquentTransactionRepository.php
app/Http/Controllers/Transactions/TransactionController.php
app/Http/Requests/Transactions/StoreTransactionRequest.php
app/Policies/Transactions/TransactionPolicy.php
```

Pastas no plural agrupam por bloco de construção, então o programador acha pelo conceito:
"onde estão os casos de uso?" → `Application/UseCases/`. "os value objects?" → `Domain/ValueObjects/`.

---

## Domain

**Value Object** (`Domain/ValueObjects/`) — imutável, valida a própria consistência, com igualdade e formatação.
```php
<?php
declare(strict_types=1);

namespace Src\Transactions\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class Money
{
    public function __construct(public int $cents, public string $currency = 'BRL')
    {
        if (strlen($currency) !== 3) {
            throw new InvalidArgumentException('Moeda deve ter 3 letras (ISO 4217).');
        }
    }

    public function equals(self $other): bool
    {
        return $this->cents === $other->cents && $this->currency === $other->currency;
    }

    public function format(): string
    {
        return number_format($this->cents / 100, 2, ',', '.') . ' ' . $this->currency;
    }
}
```

**Aggregate** (`Domain/`) — invariantes nos named constructors; sem framework. `record()` cria; `fromPersistence()` reidrata.
```php
<?php
declare(strict_types=1);

namespace Src\Transactions\Domain;

use DateTimeImmutable;
use Src\Transactions\Domain\Exceptions\InvalidTransactionAmount;
use Src\Transactions\Domain\ValueObjects\Money;

final class Transaction
{
    private function __construct(
        public readonly ?int $id,
        public readonly int $userId,
        public readonly Money $amount,
        public readonly string $description,
        public readonly DateTimeImmutable $occurredAt,
    ) {}

    public static function record(int $userId, Money $amount, string $description, DateTimeImmutable $occurredAt): self
    {
        if ($amount->cents === 0) {
            throw new InvalidTransactionAmount('O valor da transação não pode ser zero.');
        }

        return new self(null, $userId, $amount, $description, $occurredAt);
    }

    public static function fromPersistence(int $id, int $userId, Money $amount, string $description, DateTimeImmutable $occurredAt): self
    {
        return new self($id, $userId, $amount, $description, $occurredAt);
    }
}
```

**Repository (interface)** (`Domain/Repositories/`) — toda busca recebe `userId` (isolamento como contrato).
```php
<?php
declare(strict_types=1);

namespace Src\Transactions\Domain\Repositories;

use Src\Transactions\Domain\Transaction;

interface TransactionRepository
{
    public function save(Transaction $transaction): Transaction;

    public function findForUser(int $id, int $userId): ?Transaction;

    /** @return Transaction[] */
    public function allForUser(int $userId): array;
}
```

**Exceção de domínio** (`Domain/Exceptions/`) — erros de negócio são tipos, não strings soltas.
```php
<?php
declare(strict_types=1);

namespace Src\Transactions\Domain\Exceptions;

use DomainException;

final class InvalidTransactionAmount extends DomainException {}
```

---

## Application

**DTO de entrada** (`Application/DTOs/`) — dados primitivos vindos da borda; readonly.
```php
<?php
declare(strict_types=1);

namespace Src\Transactions\Application\DTOs;

final readonly class RecordTransactionInput
{
    public function __construct(
        public int $userId,
        public int $amountCents,
        public string $currency,
        public string $description,
        public string $occurredAt,
    ) {}
}
```

**Use Case** (`Application/UseCases/`) — sufixo `UseCase`, uma intenção, `__invoke`. Depende da **interface** do
repositório (DIP) e orquestra o domínio.
```php
<?php
declare(strict_types=1);

namespace Src\Transactions\Application\UseCases;

use DateTimeImmutable;
use Src\Transactions\Application\DTOs\RecordTransactionInput;
use Src\Transactions\Domain\Repositories\TransactionRepository;
use Src\Transactions\Domain\Transaction;
use Src\Transactions\Domain\ValueObjects\Money;

final readonly class RecordTransactionUseCase
{
    public function __construct(private TransactionRepository $repository) {}

    public function __invoke(RecordTransactionInput $input): Transaction
    {
        $transaction = Transaction::record(
            $input->userId,
            new Money($input->amountCents, $input->currency),
            $input->description,
            new DateTimeImmutable($input->occurredAt),
        );

        return $this->repository->save($transaction);
    }
}
```

---

## Infrastructure

**Model Eloquent** (`Infrastructure/Persistence/`) — detalhe de persistência; vive no contexto, não em `app/Models`.
```php
<?php
declare(strict_types=1);

namespace Src\Transactions\Infrastructure\Persistence;

use Illuminate\Database\Eloquent\Model;

final class TransactionModel extends Model
{
    protected $table = 'transactions';
    protected $guarded = [];
}
```

**Repositório Eloquent** (`Infrastructure/Persistence/`) — implementa a interface; mapeia ↔ domínio;
**todo query filtra por `user_id`**.
```php
<?php
declare(strict_types=1);

namespace Src\Transactions\Infrastructure\Persistence;

use DateTimeImmutable;
use Src\Transactions\Domain\Repositories\TransactionRepository;
use Src\Transactions\Domain\Transaction;
use Src\Transactions\Domain\ValueObjects\Money;

final class EloquentTransactionRepository implements TransactionRepository
{
    public function save(Transaction $t): Transaction
    {
        $model = TransactionModel::query()->updateOrCreate(
            ['id' => $t->id],
            [
                'user_id' => $t->userId,
                'amount_cents' => $t->amount->cents,
                'currency' => $t->amount->currency,
                'description' => $t->description,
                'occurred_at' => $t->occurredAt->format('Y-m-d H:i:s'),
            ],
        );

        return $this->toDomain($model);
    }

    public function findForUser(int $id, int $userId): ?Transaction
    {
        $model = TransactionModel::query()
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    /** @return Transaction[] */
    public function allForUser(int $userId): array
    {
        return TransactionModel::query()
            ->where('user_id', $userId)
            ->latest('occurred_at')
            ->get()
            ->map(fn (TransactionModel $m) => $this->toDomain($m))
            ->all();
    }

    private function toDomain(TransactionModel $m): Transaction
    {
        return Transaction::fromPersistence(
            (int) $m->id,
            (int) $m->user_id,
            new Money((int) $m->amount_cents, (string) $m->currency),
            (string) $m->description,
            new DateTimeImmutable((string) $m->occurred_at),
        );
    }
}
```

---

## Borda HTTP

**Form Request** (`app/Http/Requests/Transactions/`) — validação de entrada.

Use o Artisan via Sail para criar a request:

```bash
./vendor/bin/sail artisan make:request Transactions/StoreTransactionRequest
```

```php
<?php
declare(strict_types=1);

namespace App\Http\Requests\Transactions;

use Illuminate\Foundation\Http\FormRequest;

final class StoreTransactionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'amount_cents' => ['required', 'integer', 'not_in:0'],
            'currency' => ['required', 'string', 'size:3'],
            'description' => ['required', 'string', 'max:255'],
            'occurred_at' => ['required', 'date'],
        ];
    }
}
```

**Policy** (`app/Policies/Transactions/`) — autorização por propriedade (`user_id`).

Use o Artisan via Sail para criar a policy:

```bash
./vendor/bin/sail artisan make:policy Transactions/TransactionPolicy
```

```php
<?php
declare(strict_types=1);

namespace App\Policies\Transactions;

use App\Models\User;
use Src\Transactions\Infrastructure\Persistence\TransactionModel;

final class TransactionPolicy
{
    public function view(User $user, TransactionModel $t): bool
    {
        return $t->user_id === $user->id;
    }

    public function update(User $user, TransactionModel $t): bool
    {
        return $t->user_id === $user->id;
    }

    public function delete(User $user, TransactionModel $t): bool
    {
        return $t->user_id === $user->id;
    }
}
```

**Controller fino** (`app/Http/Controllers/Transactions/`) — traduz HTTP ↔ use case; sem regra de negócio.
**Duas saídas pelo header:** Inertia (página/redirect) por padrão; JSON quando o cliente pede.
```php
<?php
declare(strict_types=1);

namespace App\Http\Controllers\Transactions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transactions\StoreTransactionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Src\Transactions\Application\DTOs\RecordTransactionInput;
use Src\Transactions\Application\UseCases\RecordTransactionUseCase;
use Src\Transactions\Domain\Repositories\TransactionRepository;
use Src\Transactions\Domain\Transaction;

final class TransactionController extends Controller
{
    // GET — página Inertia ou JSON, conforme o header Accept.
    public function index(Request $request, TransactionRepository $repository): InertiaResponse|JsonResponse
    {
        $payload = array_map(
            $this->present(...),
            $repository->allForUser((int) $request->user()->id),
        );

        return $request->expectsJson()
            ? response()->json($payload)
            : Inertia::render('Transactions/Index', ['transactions' => $payload]);
    }

    // POST — JSON devolve o recurso (201); Inertia segue o redirect com flash.
    public function store(StoreTransactionRequest $request, RecordTransactionUseCase $record): JsonResponse|RedirectResponse
    {
        $data = $request->validated();

        $transaction = $record(new RecordTransactionInput(
            userId: (int) $request->user()->id,
            amountCents: (int) $data['amount_cents'],
            currency: (string) $data['currency'],
            description: (string) $data['description'],
            occurredAt: (string) $data['occurred_at'],
        ));

        return $request->expectsJson()
            ? response()->json($this->present($transaction), 201)
            : redirect()->route('transactions.index')->with('success', 'Transação registrada.');
    }

    private function present(Transaction $t): array
    {
        return [
            'id' => $t->id,
            'amount' => $t->amount->format(),
            'description' => $t->description,
        ];
    }
}
```
> **Header → formato:** `$request->expectsJson()` é **false** para requisições Inertia (mandam `X-Inertia`),
> que recebem `Inertia::render`/redirect; clientes com `Accept: application/json` recebem JSON.
> O controller continua fino — só decide o formato.

---

## Ligação (wiring Laravel)

**Bind interface → implementação** (`app/Providers/AppServiceProvider::register`):
```php
$this->app->bind(
    \Src\Transactions\Domain\Repositories\TransactionRepository::class,
    \Src\Transactions\Infrastructure\Persistence\EloquentTransactionRepository::class,
);
```

**Rota** (`routes/web.php`):
```php
Route::group([
    'as' => '',
    'prefix' => '',
    'middleware' => ['auth'],
], function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::group([
        'prefix' => 'transactions',
        'as' => 'transactions.',
    ], function () {
        Route::get('/', [TransactionController::class, 'index'])->name('index');
        Route::post('/', [TransactionController::class, 'store'])->name('store');
    });
});
```

**Migration** — sempre `user_id` e/ou `company_id` + índice por usuário.
```php
Schema::create('transactions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->integer('amount_cents');
    $table->string('currency', 3);
    $table->string('description');
    $table->timestamp('occurred_at');
    $table->timestamps();
    $table->softDelete();
    $table->index(['user_id', 'occurred_at']);
});
```

---

## Testes

**Unit (Domain)** — regra de negócio + Value Object. _(Pest, `tests/Unit/Transactions/`)_
```php
<?php
declare(strict_types=1);

use Src\Transactions\Domain\Exceptions\InvalidTransactionAmount;
use Src\Transactions\Domain\Transaction;
use Src\Transactions\Domain\ValueObjects\Money;

it('rejeita transação com valor zero', function () {
    Transaction::record(1, new Money(0), 'Teste', new DateTimeImmutable());
})->throws(InvalidTransactionAmount::class);

it('formata o valor', function () {
    expect((new Money(123456))->format())->toBe('1.234,56 BRL');
});
```

**Feature (HTTP + isolamento)** — fluxo de aceite e o caso multi-usuário. _(Pest, `tests/Feature/Transactions/`)_
```php
<?php
declare(strict_types=1);

use App\Models\User;

it('registra a transação do usuário autenticado', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson('/transactions', [
        'amount_cents' => 5000,
        'currency' => 'BRL',
        'description' => 'Mercado',
        'occurred_at' => '2026-05-23 10:00:00',
    ])->assertCreated();

    $this->assertDatabaseHas('transactions', [
        'user_id' => $user->id,
        'amount_cents' => 5000,
    ]);
});

it('não expõe transação de outro usuário', function () {
    // GET/PUT/DELETE de recurso alheio deve retornar 403/404. Obrigatório em todo contexto.
})->todo();
```

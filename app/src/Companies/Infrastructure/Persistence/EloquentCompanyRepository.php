<?php

declare(strict_types=1);

namespace Src\Companies\Infrastructure\Persistence;

use Illuminate\Support\Facades\DB;
use Src\Companies\Domain\Company;
use Src\Companies\Domain\CompanyPartner;
use Src\Companies\Domain\Enums\RegimeTributario;
use Src\Companies\Domain\Repositories\CompanyRepository;
use Src\Companies\Domain\ValueObjects\Cnpj;
use Src\Companies\Domain\ValueObjects\Cpf;
use Src\Companies\Domain\ValueObjects\ParticipacaoPercentual;

final class EloquentCompanyRepository implements CompanyRepository
{
    public function save(Company $company): Company
    {
        $data = [
            'user_id' => $company->userId,
            'razao_social' => $company->razaoSocial,
            'nome_fantasia' => $company->nomeFantasia,
            'cnpj' => $company->cnpj->value,
            'regime_tributario' => $company->regimeTributario->value,
        ];

        if ($company->id !== null) {
            $model = CompanyModel::query()
                ->where('id', $company->id)
                ->where('user_id', $company->userId)
                ->firstOrFail();
            $model->update($data);
        } else {
            $model = CompanyModel::query()->create($data);
        }

        return $this->toDomain($model);
    }

    public function findForUser(int $id, int $userId): ?Company
    {
        $model = CompanyModel::query()
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    /** @return Company[] */
    public function allForUser(int $userId): array
    {
        return CompanyModel::query()
            ->where('user_id', $userId)
            ->orderBy('razao_social')
            ->get()
            ->map(fn (CompanyModel $m) => $this->toDomain($m))
            ->all();
    }

    public function delete(Company $company): void
    {
        CompanyModel::query()
            ->where('id', $company->id)
            ->where('user_id', $company->userId)
            ->delete();
    }

    /** @param CompanyPartner[] $partners */
    public function syncPartners(int $companyId, array $partners): void
    {
        DB::transaction(function () use ($companyId, $partners): void {
            CompanyPartnerModel::query()
                ->where('company_id', $companyId)
                ->delete();

            if (count($partners) === 0) {
                return;
            }

            $now = now()->toDateTimeString();

            $rows = array_map(fn (CompanyPartner $p) => [
                'company_id' => $p->companyId,
                'user_id' => $p->userId,
                'nome' => $p->nome,
                'cpf' => $p->cpf->value,
                'participacao' => $p->participacao->value,
                'created_at' => $now,
                'updated_at' => $now,
            ], $partners);

            CompanyPartnerModel::query()->insert($rows);
        });
    }

    /** @return CompanyPartner[] */
    public function partnersForCompany(int $companyId): array
    {
        return CompanyPartnerModel::query()
            ->where('company_id', $companyId)
            ->orderBy('nome')
            ->get()
            ->map(fn (CompanyPartnerModel $m) => $this->toPartnerDomain($m))
            ->all();
    }

    public function findPartner(int $partnerId, int $companyId): ?CompanyPartner
    {
        $model = CompanyPartnerModel::query()
            ->where('id', $partnerId)
            ->where('company_id', $companyId)
            ->first();

        return $model ? $this->toPartnerDomain($model) : null;
    }

    private function toDomain(CompanyModel $m): Company
    {
        return Company::fromPersistence(
            id: (int) $m->id,
            userId: (int) $m->user_id,
            razaoSocial: (string) $m->razao_social,
            nomeFantasia: (string) $m->nome_fantasia,
            cnpj: new Cnpj((string) $m->cnpj),
            regimeTributario: RegimeTributario::from((string) $m->regime_tributario),
        );
    }

    private function toPartnerDomain(CompanyPartnerModel $m): CompanyPartner
    {
        return CompanyPartner::fromPersistence(
            id: (int) $m->id,
            companyId: (int) $m->company_id,
            userId: (int) $m->user_id,
            nome: (string) $m->nome,
            cpf: new Cpf((string) $m->cpf),
            participacao: new ParticipacaoPercentual((float) $m->participacao),
        );
    }
}

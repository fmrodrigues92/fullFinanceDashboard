import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Pencil, Trash2 } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { PaginationNav } from '@/components/ui/pagination-nav';
import companies from '@/routes/companies';
import type { Client, PaginationMeta } from '@/types/invoicing';

interface PageProps {
    company: { id: number };
    clients: Client[];
    pagination: PaginationMeta;
    [key: string]: unknown;
}

interface ClientForm {
    nome: string;
    ext_id: string;
}

export default function ClientsIndex({
    company,
    clients,
    pagination,
}: PageProps) {
    const [createOpen, setCreateOpen] = useState(false);
    const [editingClient, setEditingClient] = useState<Client | null>(null);
    const [deletingId, setDeletingId] = useState<number | null>(null);

    const createForm = useForm<ClientForm>({ nome: '', ext_id: '' });
    const editForm = useForm<ClientForm>({ nome: '', ext_id: '' });

    const handleCreate = (e: FormEvent) => {
        e.preventDefault();
        createForm.post(companies.clients.store({ company: company.id }).url, {
            preserveScroll: true,
            onSuccess: () => {
                setCreateOpen(false);
                createForm.reset();
            },
        });
    };

    const openEdit = (client: Client) => {
        editForm.setData({ nome: client.nome, ext_id: client.ext_id ?? '' });
        setEditingClient(client);
    };

    const handleEdit = (e: FormEvent) => {
        e.preventDefault();
        if (!editingClient) return;
        editForm.put(
            companies.clients.update({
                company: company.id,
                client: editingClient.id,
            }).url,
            {
                preserveScroll: true,
                onSuccess: () => setEditingClient(null),
            },
        );
    };

    const goToPage = (page: number) => {
        router.get(
            companies.clients.index({ company: company.id }).url,
            { page: String(page), per_page: String(pagination.per_page) },
            { preserveScroll: true },
        );
    };

    const changePerPage = (perPage: number) => {
        router.get(
            companies.clients.index({ company: company.id }).url,
            { page: '1', per_page: String(perPage) },
            { preserveScroll: true, preserveState: true },
        );
    };

    const handleDelete = (clientId: number) => {
        router.delete(
            companies.clients.destroy({
                company: company.id,
                client: clientId,
            }).url,
            {
                preserveScroll: true,
                onSuccess: () => setDeletingId(null),
            },
        );
    };

    return (
        <>
            <Head title="Clientes" />

            <div className="flex flex-col gap-6 p-6">
                <div className="flex items-center justify-between">
                    <Button asChild variant="ghost" size="sm" className="-ml-2">
                        <Link
                            href={companies.show({ company: company.id }).url}
                        >
                            <ArrowLeft className="mr-1 h-4 w-4" />
                            Empresa
                        </Link>
                    </Button>

                    <Dialog open={createOpen} onOpenChange={setCreateOpen}>
                        <DialogTrigger asChild>
                            <Button>Novo Cliente</Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Novo Cliente</DialogTitle>
                            </DialogHeader>
                            <form onSubmit={handleCreate} className="space-y-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="create-nome">Nome</Label>
                                    <Input
                                        id="create-nome"
                                        value={createForm.data.nome}
                                        onChange={(e) =>
                                            createForm.setData(
                                                'nome',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Empresa Cliente Ltda"
                                    />
                                    <InputError
                                        message={createForm.errors.nome}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="create-extid">
                                        ID Externo{' '}
                                        <span className="text-muted-foreground">
                                            (opcional)
                                        </span>
                                    </Label>
                                    <Input
                                        id="create-extid"
                                        value={createForm.data.ext_id}
                                        onChange={(e) =>
                                            createForm.setData(
                                                'ext_id',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="CLI-001"
                                    />
                                    <InputError
                                        message={createForm.errors.ext_id}
                                    />
                                </div>
                                <DialogFooter>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() => setCreateOpen(false)}
                                    >
                                        Cancelar
                                    </Button>
                                    <Button
                                        type="submit"
                                        disabled={createForm.processing}
                                    >
                                        Criar
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>

                <div>
                    <h1 className="text-2xl font-semibold">Clientes</h1>
                    <p className="text-sm text-muted-foreground">
                        Clientes vinculados às notas fiscais desta empresa
                    </p>
                </div>

                {clients.length === 0 ? (
                    <div className="rounded-xl border border-dashed p-12 text-center">
                        <p className="font-medium">Nenhum cliente cadastrado</p>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Cadastre clientes para vinculá-los às notas fiscais.
                        </p>
                    </div>
                ) : (
                    <div className="rounded-xl border">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b bg-muted/50">
                                    <th className="px-4 py-3 text-left font-medium">
                                        Nome
                                    </th>
                                    <th className="px-4 py-3 text-left font-medium">
                                        ID Externo
                                    </th>
                                    <th className="px-4 py-3 text-right font-medium">
                                        Ações
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {clients.map((client) => (
                                    <tr
                                        key={client.id}
                                        className="border-b last:border-0 hover:bg-muted/30"
                                    >
                                        <td className="px-4 py-3 font-medium">
                                            {client.nome}
                                        </td>
                                        <td className="px-4 py-3 font-mono text-muted-foreground">
                                            {client.ext_id ?? '—'}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <div className="flex items-center justify-end gap-2">
                                                <Dialog
                                                    open={
                                                        editingClient?.id ===
                                                        client.id
                                                    }
                                                    onOpenChange={(open) => {
                                                        if (open)
                                                            openEdit(client);
                                                        else
                                                            setEditingClient(
                                                                null,
                                                            );
                                                    }}
                                                >
                                                    <DialogTrigger asChild>
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                        >
                                                            <Pencil className="h-4 w-4" />
                                                        </Button>
                                                    </DialogTrigger>
                                                    <DialogContent>
                                                        <DialogHeader>
                                                            <DialogTitle>
                                                                Editar —{' '}
                                                                {client.nome}
                                                            </DialogTitle>
                                                        </DialogHeader>
                                                        <form
                                                            onSubmit={
                                                                handleEdit
                                                            }
                                                            className="space-y-4"
                                                        >
                                                            <div className="grid gap-2">
                                                                <Label htmlFor="edit-nome">
                                                                    Nome
                                                                </Label>
                                                                <Input
                                                                    id="edit-nome"
                                                                    value={
                                                                        editForm
                                                                            .data
                                                                            .nome
                                                                    }
                                                                    onChange={(
                                                                        e,
                                                                    ) =>
                                                                        editForm.setData(
                                                                            'nome',
                                                                            e
                                                                                .target
                                                                                .value,
                                                                        )
                                                                    }
                                                                />
                                                                <InputError
                                                                    message={
                                                                        editForm
                                                                            .errors
                                                                            .nome
                                                                    }
                                                                />
                                                            </div>
                                                            <div className="grid gap-2">
                                                                <Label htmlFor="edit-extid">
                                                                    ID Externo
                                                                </Label>
                                                                <Input
                                                                    id="edit-extid"
                                                                    value={
                                                                        editForm
                                                                            .data
                                                                            .ext_id
                                                                    }
                                                                    onChange={(
                                                                        e,
                                                                    ) =>
                                                                        editForm.setData(
                                                                            'ext_id',
                                                                            e
                                                                                .target
                                                                                .value,
                                                                        )
                                                                    }
                                                                />
                                                                <InputError
                                                                    message={
                                                                        editForm
                                                                            .errors
                                                                            .ext_id
                                                                    }
                                                                />
                                                            </div>
                                                            <DialogFooter>
                                                                <Button
                                                                    type="button"
                                                                    variant="outline"
                                                                    onClick={() =>
                                                                        setEditingClient(
                                                                            null,
                                                                        )
                                                                    }
                                                                >
                                                                    Cancelar
                                                                </Button>
                                                                <Button
                                                                    type="submit"
                                                                    disabled={
                                                                        editForm.processing
                                                                    }
                                                                >
                                                                    Salvar
                                                                </Button>
                                                            </DialogFooter>
                                                        </form>
                                                    </DialogContent>
                                                </Dialog>

                                                <Dialog
                                                    open={
                                                        deletingId === client.id
                                                    }
                                                    onOpenChange={(open) =>
                                                        setDeletingId(
                                                            open
                                                                ? client.id
                                                                : null,
                                                        )
                                                    }
                                                >
                                                    <DialogTrigger asChild>
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            className="text-destructive hover:text-destructive"
                                                        >
                                                            <Trash2 className="h-4 w-4" />
                                                        </Button>
                                                    </DialogTrigger>
                                                    <DialogContent>
                                                        <DialogHeader>
                                                            <DialogTitle>
                                                                Remover cliente?
                                                            </DialogTitle>
                                                        </DialogHeader>
                                                        <p className="text-sm text-muted-foreground">
                                                            <strong>
                                                                {client.nome}
                                                            </strong>{' '}
                                                            será removido. As
                                                            notas vinculadas
                                                            perdem o vínculo com
                                                            este cliente.
                                                        </p>
                                                        <DialogFooter>
                                                            <Button
                                                                variant="outline"
                                                                onClick={() =>
                                                                    setDeletingId(
                                                                        null,
                                                                    )
                                                                }
                                                            >
                                                                Cancelar
                                                            </Button>
                                                            <Button
                                                                variant="destructive"
                                                                onClick={() =>
                                                                    handleDelete(
                                                                        client.id,
                                                                    )
                                                                }
                                                            >
                                                                Remover
                                                            </Button>
                                                        </DialogFooter>
                                                    </DialogContent>
                                                </Dialog>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                        <PaginationNav
                            pagination={pagination}
                            onPage={goToPage}
                            onPerPage={changePerPage}
                        />
                    </div>
                )}
            </div>
        </>
    );
}

ClientsIndex.layout = ({
    company,
}: {
    company: { id: number };
    [key: string]: unknown;
}) => ({
    breadcrumbs: [
        { title: 'Empresas', href: companies.index().url },
        {
            title: 'Empresa',
            href: companies.show({ company: company?.id ?? 0 }).url,
        },
        {
            title: 'Clientes',
            href: companies.clients.index({ company: company?.id ?? 0 }).url,
        },
    ],
});

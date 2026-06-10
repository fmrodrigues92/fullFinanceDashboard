import {
    ChevronLeft,
    ChevronRight,
    ChevronsLeft,
    ChevronsRight,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';
import type { PaginationMeta } from '@/types/invoicing';

const PER_PAGE_OPTIONS = [10, 25, 50, 100] as const;

interface Props {
    pagination: PaginationMeta;
    onPage: (page: number) => void;
    onPerPage: (perPage: number) => void;
    className?: string;
}

function pageRange(current: number, last: number): (number | '...')[] {
    if (last <= 7) {
        return Array.from({ length: last }, (_, i) => i + 1);
    }

    const pages: (number | '...')[] = [1];

    if (current > 3) pages.push('...');

    const start = Math.max(2, current - 1);
    const end = Math.min(last - 1, current + 1);
    for (let i = start; i <= end; i++) pages.push(i);

    if (current < last - 2) pages.push('...');

    pages.push(last);

    return pages;
}

export function PaginationNav({ pagination, onPage, onPerPage, className }: Props) {
    const { current_page, last_page, total, per_page } = pagination;

    const from = total === 0 ? 0 : (current_page - 1) * per_page + 1;
    const to = Math.min(current_page * per_page, total);

    return (
        <div
            className={cn(
                'flex flex-wrap items-center justify-between gap-4 border-t px-4 py-3 text-sm',
                className,
            )}
        >
            {/* Left: per-page select + record count */}
            <div className="flex items-center gap-3">
                <div className="flex items-center gap-2 text-muted-foreground">
                    <span>Linhas por página</span>
                    <Select
                        value={String(per_page)}
                        onValueChange={(v) => onPerPage(Number(v))}
                    >
                        <SelectTrigger className="h-8 w-20">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent side="top">
                            {PER_PAGE_OPTIONS.map((n) => (
                                <SelectItem key={n} value={String(n)}>
                                    {n}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <span className="text-muted-foreground">
                    {total === 0 ? (
                        'Nenhum registro'
                    ) : (
                        <>
                            <span className="font-medium text-foreground">
                                {from}–{to}
                            </span>{' '}
                            de{' '}
                            <span className="font-medium text-foreground">
                                {total}
                            </span>{' '}
                            registro{total !== 1 ? 's' : ''}
                        </>
                    )}
                </span>
            </div>

            {/* Right: page navigation */}
            <div className="flex items-center gap-1">
                <Button
                    variant="ghost"
                    size="icon"
                    className="h-8 w-8"
                    disabled={current_page <= 1}
                    onClick={() => onPage(1)}
                    title="Primeira página"
                >
                    <ChevronsLeft className="h-4 w-4" />
                </Button>

                <Button
                    variant="ghost"
                    size="icon"
                    className="h-8 w-8"
                    disabled={current_page <= 1}
                    onClick={() => onPage(current_page - 1)}
                    title="Página anterior"
                >
                    <ChevronLeft className="h-4 w-4" />
                </Button>

                {last_page > 1 &&
                    pageRange(current_page, last_page).map((page, idx) =>
                        page === '...' ? (
                            <span
                                key={`ellipsis-${idx}`}
                                className="px-1 text-muted-foreground select-none"
                            >
                                …
                            </span>
                        ) : (
                            <Button
                                key={page}
                                variant={page === current_page ? 'outline' : 'ghost'}
                                size="icon"
                                className={cn(
                                    'h-8 w-8 tabular-nums',
                                    page === current_page && 'font-semibold',
                                )}
                                onClick={() => onPage(page)}
                                aria-current={page === current_page ? 'page' : undefined}
                            >
                                {page}
                            </Button>
                        ),
                    )}

                {last_page <= 1 && (
                    <span className="px-3 text-muted-foreground tabular-nums">
                        {current_page} / {last_page}
                    </span>
                )}

                <Button
                    variant="ghost"
                    size="icon"
                    className="h-8 w-8"
                    disabled={current_page >= last_page}
                    onClick={() => onPage(current_page + 1)}
                    title="Próxima página"
                >
                    <ChevronRight className="h-4 w-4" />
                </Button>

                <Button
                    variant="ghost"
                    size="icon"
                    className="h-8 w-8"
                    disabled={current_page >= last_page}
                    onClick={() => onPage(last_page)}
                    title="Última página"
                >
                    <ChevronsRight className="h-4 w-4" />
                </Button>
            </div>
        </div>
    );
}

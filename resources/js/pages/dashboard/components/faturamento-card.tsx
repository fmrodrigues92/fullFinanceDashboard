import { useState } from 'react';
import { ChevronDown, ChevronUp } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { Separator } from '@/components/ui/separator';
import type { FaturamentoData } from '@/types/dashboard';
import { brl } from '../lib/format';

interface Props {
    faturamento: FaturamentoData;
}

export function FaturamentoCard({ faturamento }: Props) {
    const [open, setOpen] = useState(false);

    return (
        <Collapsible open={open} onOpenChange={setOpen}>
            <Card>
                <CardHeader className="pb-0">
                    <div className="flex items-center justify-between">
                        <CardTitle className="text-base">Faturamento</CardTitle>
                        {faturamento.is_simulado && (
                            <span className="inline-flex items-center rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground">
                                Estimado
                            </span>
                        )}
                    </div>
                </CardHeader>
                <CardContent className="space-y-3">
                    <div className="flex items-end justify-between">
                        <div>
                            <p className="text-2xl font-bold">
                                {brl(faturamento.total)}
                            </p>
                            <p className="text-xs text-muted-foreground">
                                {faturamento.notas_emitidas} nota(s) emitida(s)
                            </p>
                        </div>
                        <CollapsibleTrigger asChild>
                            <Button
                                variant="ghost"
                                size="sm"
                                className="text-xs text-muted-foreground"
                            >
                                {open ? (
                                    <>
                                        Ocultar{' '}
                                        <ChevronUp className="ml-1 h-3 w-3" />
                                    </>
                                ) : (
                                    <>
                                        Detalhes{' '}
                                        <ChevronDown className="ml-1 h-3 w-3" />
                                    </>
                                )}
                            </Button>
                        </CollapsibleTrigger>
                    </div>
                    <CollapsibleContent>
                        <Separator className="mb-3" />
                        <table className="w-full text-sm">
                            <tbody>
                                {faturamento.itens.map((item) => (
                                    <tr
                                        key={item.tipo}
                                        className="border-b last:border-0"
                                    >
                                        <td className="py-1.5">
                                            <Badge
                                                variant="outline"
                                                className="text-xs capitalize"
                                            >
                                                {item.tipo}
                                            </Badge>
                                        </td>
                                        <td className="py-1.5 text-center text-xs text-muted-foreground">
                                            {item.quantidade} nota(s)
                                        </td>
                                        <td className="py-1.5 text-right font-medium">
                                            {brl(item.valor)}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </CollapsibleContent>
                </CardContent>
            </Card>
        </Collapsible>
    );
}

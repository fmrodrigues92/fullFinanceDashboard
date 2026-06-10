import { useState } from 'react';
import { ChevronDown, ChevronUp } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { Separator } from '@/components/ui/separator';
import type { GastosData } from '@/types/dashboard';
import { brl } from '../lib/format';

interface Props {
    gastos: GastosData;
}

export function GastosCard({ gastos }: Props) {
    const [open, setOpen] = useState(false);

    return (
        <Collapsible open={open} onOpenChange={setOpen}>
            <Card>
                <CardHeader className="pb-0">
                    <CardTitle className="text-base">
                        Gastos da Empresa
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-3">
                    <div className="flex items-end justify-between">
                        <div>
                            <p className="text-2xl font-bold">
                                {brl(gastos.total)}
                            </p>
                            <p className="text-xs text-muted-foreground">
                                {gastos.itens.length} categoria(s)
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
                                {gastos.itens.map((item) => (
                                    <tr
                                        key={item.categoria}
                                        className="border-b last:border-0"
                                    >
                                        <td className="py-1.5">
                                            <p className="font-medium">
                                                {item.categoria}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {item.descricao}
                                            </p>
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

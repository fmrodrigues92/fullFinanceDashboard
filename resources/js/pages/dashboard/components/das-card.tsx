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
import type { DasData } from '@/types/dashboard';
import { brl } from '../lib/format';

interface Props {
    das: DasData;
}

export function DasCard({ das }: Props) {
    const [open, setOpen] = useState(false);

    return (
        <Collapsible open={open} onOpenChange={setOpen}>
            <Card>
                <CardHeader className="pb-0">
                    <div className="flex items-center justify-between">
                        <CardTitle className="text-base">
                            DAS — Simples Nacional
                        </CardTitle>
                        <Badge
                            variant={
                                das.status === 'pago'
                                    ? 'default'
                                    : 'destructive'
                            }
                            className="text-xs"
                        >
                            {das.status === 'pago' ? 'Pago' : 'Pendente'}
                        </Badge>
                    </div>
                </CardHeader>
                <CardContent className="space-y-3">
                    <div className="flex items-end justify-between">
                        <div>
                            <p className="text-2xl font-bold">
                                {brl(das.valor)}
                            </p>
                            <p className="text-xs text-muted-foreground">
                                Vencimento: {das.vencimento}
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
                                {das.detalhes.map((d) => (
                                    <tr
                                        key={d.descricao}
                                        className="border-b last:border-0"
                                    >
                                        <td className="py-1.5 text-muted-foreground">
                                            {d.descricao}
                                        </td>
                                        <td className="py-1.5 text-right font-medium">
                                            {brl(d.valor)}
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

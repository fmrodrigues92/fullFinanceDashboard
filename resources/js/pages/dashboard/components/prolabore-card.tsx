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
import type { ProlaboreData } from '@/types/dashboard';
import { brl } from '../lib/format';

interface Props {
    prolabore: ProlaboreData;
}

export function ProlaboreCard({ prolabore }: Props) {
    const [open, setOpen] = useState(false);

    return (
        <Collapsible open={open} onOpenChange={setOpen}>
            <Card>
                <CardHeader className="pb-0">
                    <CardTitle className="text-base">Pró-labore</CardTitle>
                </CardHeader>
                <CardContent className="space-y-3">
                    <div className="flex items-end justify-between">
                        <div>
                            <p className="text-2xl font-bold">
                                {brl(prolabore.total)}
                            </p>
                            <p className="text-xs text-muted-foreground">
                                {prolabore.socios.length} sócio(s)
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
                                {prolabore.socios.map((s) => (
                                    <tr
                                        key={s.nome}
                                        className="border-b last:border-0"
                                    >
                                        <td className="py-1.5 text-muted-foreground">
                                            {s.nome}
                                        </td>
                                        <td className="py-1.5 text-center">
                                            <Badge
                                                variant={
                                                    s.status === 'pago'
                                                        ? 'default'
                                                        : 'outline'
                                                }
                                                className="text-xs"
                                            >
                                                {s.status === 'pago'
                                                    ? 'Pago'
                                                    : 'Pendente'}
                                            </Badge>
                                        </td>
                                        <td className="py-1.5 text-right font-medium">
                                            {brl(s.valor)}
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

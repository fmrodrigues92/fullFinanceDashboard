import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import type { MonthItem } from '../lib/months';

interface Props {
    months: MonthItem[];
    selectedKey: string;
    onSelect: (key: string) => void;
}

export function CompetenciaNav({ months, selectedKey, onSelect }: Props) {
    return (
        <div className="flex gap-2 overflow-x-auto pb-1">
            {months.map((m) => (
                <Button
                    key={m.key}
                    variant={m.key === selectedKey ? 'default' : 'outline'}
                    size="sm"
                    className={cn(
                        'min-w-[76px] shrink-0 text-xs',
                        m.key === selectedKey && 'font-semibold',
                    )}
                    onClick={() => onSelect(m.key)}
                >
                    {m.label}
                </Button>
            ))}
        </div>
    );
}

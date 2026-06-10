const MONTHS_PT = [
    'Jan',
    'Fev',
    'Mar',
    'Abr',
    'Mai',
    'Jun',
    'Jul',
    'Ago',
    'Set',
    'Out',
    'Nov',
    'Dez',
];

export interface MonthItem {
    year: number;
    month: number;
    label: string;
    key: string; // 'YYYY-MM'
}

export function generateMonths(today: Date): MonthItem[] {
    const items: MonthItem[] = [];
    for (let offset = -6; offset <= 6; offset++) {
        const d = new Date(today.getFullYear(), today.getMonth() + offset, 1);
        items.push({
            year: d.getFullYear(),
            month: d.getMonth(),
            label: `${MONTHS_PT[d.getMonth()]}/${d.getFullYear()}`,
            key: `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`,
        });
    }
    return items;
}

export function todayMonthKey(today: Date): string {
    return `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}`;
}

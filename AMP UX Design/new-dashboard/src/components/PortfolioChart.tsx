import { TrendingUp } from 'lucide-react';

const STATUS_BARS = [
  { label: 'Construction', count: 2, color: '#0969DA' },
  { label: 'Permitting',   count: 2, color: '#D4A72C' },
  { label: 'Submitted',    count: 1, color: '#8250DF' },
  { label: 'Closeout',     count: 1, color: '#1A7F37' },
  { label: 'On Hold',      count: 1, color: '#8C959F' },
  { label: 'NTP',          count: 1, color: '#CF222E' },
];

const TOTAL = STATUS_BARS.reduce((s, b) => s + b.count, 0);

interface PortfolioChartProps {
  total: number;
}

export function PortfolioChart({ total }: PortfolioChartProps) {
  return (
    <div className="bg-white border border-[#D1D9E0] rounded-xl flex flex-col shadow-sm">
      {/* Header */}
      <div className="flex items-center justify-between px-4 py-3 border-b border-[#EEF1F4]">
        <div className="flex items-center gap-2">
          <TrendingUp size={15} className="text-[#1A7F37]" />
          <span className="text-sm font-semibold text-[#1A1F2E]">Portfolio Snapshot</span>
        </div>
        <span className="text-xs text-[#656D76] font-medium">{total} total</span>
      </div>

      {/* Stacked bar */}
      <div className="px-4 pt-4 pb-2">
        <div className="h-2.5 rounded-full flex overflow-hidden gap-0.5">
          {STATUS_BARS.map(bar => (
            <div
              key={bar.label}
              className="h-full rounded-full"
              style={{
                width: `${(bar.count / TOTAL) * 100}%`,
                backgroundColor: bar.color,
              }}
              title={`${bar.label}: ${bar.count}`}
            />
          ))}
        </div>
      </div>

      {/* Legend rows */}
      <div className="px-4 pb-4 space-y-2 mt-2">
        {STATUS_BARS.map(bar => (
          <div key={bar.label} className="flex items-center gap-2">
            <div className="w-2 h-2 rounded-full shrink-0" style={{ backgroundColor: bar.color }} />
            <span className="text-xs text-[#656D76] flex-1">{bar.label}</span>
            <div className="flex items-center gap-2">
              <div className="w-16 h-1.5 bg-[#EEF1F4] rounded-full overflow-hidden">
                <div
                  className="h-full rounded-full"
                  style={{
                    width: `${(bar.count / TOTAL) * 100}%`,
                    backgroundColor: bar.color,
                  }}
                />
              </div>
              <span className="text-xs text-[#1A1F2E] font-semibold w-4 text-right tabular-nums">
                {bar.count}
              </span>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}

import { AlertTriangle, FileText, CheckCircle2, ChevronRight } from 'lucide-react';
import type { ActionItem, UrgencyLevel } from '../data/mockProjects';

const TYPE_ICON = {
  form: FileText,
  approval: CheckCircle2,
  review: CheckCircle2,
  brief: FileText,
};

const URGENCY_ROW: Record<UrgencyLevel, string> = {
  critical: 'border-l-[#CF222E] bg-[#FFF5F5]',
  warning:  'border-l-[#D4A72C] bg-[#FFFDF0]',
  ok:       'border-l-[#D1D9E0] bg-transparent',
  hold:     'border-l-[#D1D9E0] bg-transparent',
};

const URGENCY_DUE: Record<UrgencyLevel, string> = {
  critical: 'text-[#CF222E] font-semibold',
  warning:  'text-[#9A6700] font-medium',
  ok:       'text-[#656D76]',
  hold:     'text-[#8C959F]',
};

const URGENCY_ICON: Record<UrgencyLevel, string> = {
  critical: 'text-[#CF222E]',
  warning:  'text-[#9A6700]',
  ok:       'text-[#8C959F]',
  hold:     'text-[#8C959F]',
};

interface ActionPanelProps {
  items: ActionItem[];
}

export function ActionPanel({ items }: ActionPanelProps) {
  const urgent = items.filter(i => i.urgency === 'critical' || i.urgency === 'warning');
  const rest = items.filter(i => i.urgency === 'ok');

  return (
    <div className="bg-white border border-[#D1D9E0] rounded-xl flex flex-col shadow-sm">
      {/* Header */}
      <div className="flex items-center justify-between px-4 py-3 border-b border-[#EEF1F4]">
        <div className="flex items-center gap-2">
          <AlertTriangle size={15} className="text-[#9A6700]" />
          <span className="text-sm font-semibold text-[#1A1F2E]">Action Required</span>
          <span className="text-xs bg-[#FFF8C5] text-[#9A6700] border border-[#D4A72C40] px-2 py-0.5 rounded-full font-bold">
            {items.length}
          </span>
        </div>
        <button className="text-xs text-[#656D76] hover:text-[#0969DA] transition-colors font-medium">
          View all
        </button>
      </div>

      {/* Items */}
      <div className="flex-1 overflow-y-auto divide-y divide-[#EEF1F4]">
        {[...urgent, ...rest].map(item => {
          const Icon = TYPE_ICON[item.type];
          return (
            <div
              key={item.id}
              className={`flex items-center gap-3 px-4 py-2.5 border-l-2 hover:bg-[#F6F8FA] cursor-pointer transition-colors group ${URGENCY_ROW[item.urgency]}`}
            >
              <Icon size={14} className={`shrink-0 ${URGENCY_ICON[item.urgency]}`} />
              <div className="flex-1 min-w-0">
                <div className="text-xs text-[#1A1F2E] font-medium truncate">{item.label}</div>
                <div className="text-[11px] text-[#656D76]">{item.siteName} · {item.projectId}</div>
              </div>
              <span className={`text-[11px] shrink-0 ${URGENCY_DUE[item.urgency]}`}>
                {item.dueLabel}
              </span>
              <ChevronRight size={14} className="text-[#D1D9E0] group-hover:text-[#8C959F] transition-colors shrink-0" />
            </div>
          );
        })}
      </div>
    </div>
  );
}
